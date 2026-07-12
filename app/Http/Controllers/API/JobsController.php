<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Notifications\EmailNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Helpers\AuthHelper;
use App\Helpers\FirmActivityHelper;
use App\Helpers\FreeActionsHelper;
use App\Helpers\NotificationHelper;
use App\Helpers\SubscriptionHelper;
use App\Helpers\WalletHelper;
use App\Helpers\SysCoinHelper;
use App\Exceptions\InsufficientFundsException;
use App\Jobs\SendUserPushJob;
use App\Services\ActivityTracker;
use App\Enums\ActivityType;
use Illuminate\Database\QueryException;


class JobsController extends Controller
{
    public function applyJob(Request $request, $id)
    {
        try {
            $token = $request->cookie('auth_token');
            if (!$token) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }
            $user = AuthHelper::resolveUser($request);
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid token'
                ], 401);
            }
            if ($user->role !== 'student') {
                return response()->json([
                    'status' => false,
                    'message' => 'Only students can apply for jobs'
                ], 403);
            }
            $lookingFor = DB::table('student_profiles')->where('user_id', $user->id)->value('looking_for');
            if ($lookingFor === 'creator') {
                return response()->json([
                    'status' => false,
                    'message' => 'Creator students cannot apply for jobs.'
                ], 403);
            }
            $job = DB::table('jobs')
                ->where('id', $id)
                ->where('is_active', 1)
                ->first();
            if (!$job) {
                return response()->json([
                    'status' => false,
                    'message' => 'Job not found'
                ], 404);
            }
            $alreadyApplied = DB::table('applications')
                ->where('job_id', $id)
                ->where('student_id', $user->id)
                ->exists();
            if ($alreadyApplied) {
                return response()->json([
                    'status' => false,
                    'message' => 'You already applied for this job'
                ], 409);
            }

            /*
        |--------------------------------------------------------------------------
        | Payment tier — Free → SYS Coins → Wallet money (never mixed)
        |--------------------------------------------------------------------------
        */
            $isFree    = WalletHelper::isFreeApplication($user->id);
            $useCoins  = !$isFree && SysCoinHelper::hasEnoughCoins($user->id);                 // ≥ 50 coins
            $useWallet = !$isFree && !$useCoins && WalletHelper::hasEnoughBalance($user->id);  // ≥ ₹49

            if (!$isFree && !$useCoins && !$useWallet) {
                return response()->json([
                    'status'               => false,
                    'message'              => 'Free application limit reached. Please recharge your wallet or upgrade your plan to continue applying.',
                    'insufficient_balance' => true,
                    'free_limit_reached'   => true,
                    'application_fee'      => WalletHelper::APPLICATION_FEE,
                ]);
            }

            // Spending REAL wallet money requires explicit confirmation — never deduct
            // silently. (Free applications and SYS Coins proceed without a prompt.)
            if ($useWallet && !$request->boolean('confirm_wallet')) {
                return response()->json([
                    'status'                        => false,
                    'requires_payment_confirmation' => true,
                    'payment_source'                => 'wallet',
                    'application_fee'               => WalletHelper::APPLICATION_FEE,
                    'coin_balance'                  => SysCoinHelper::getBalance($user->id),
                    'message'                       => 'Not enough SYS Coins. Use ₹' . WalletHelper::APPLICATION_FEE . ' from your wallet to apply?',
                ]);
            }

            $paymentSource = $isFree ? 'free' : ($useCoins ? 'sys_coin' : 'wallet');

            DB::beginTransaction();
            $applicationId = DB::table('applications')->insertGetId([
                'job_id'                => $id,
                'student_id'            => $user->id,
                'status'                => 'Applied',
                'is_free_application'   => $isFree ? 1 : 0,
                'application_fee'       => $useWallet ? WalletHelper::APPLICATION_FEE : 0.00,
                'payment_source'        => $paymentSource,
                'applied_at'            => now(),
                'updated_at'            => now(),
            ]);

            if ($isFree) {
                WalletHelper::incrementFreeUsage($user->id);
            } elseif ($useCoins) {
                $coinHoldId = SysCoinHelper::hold($user->id, $applicationId, $job->id);
                DB::table('applications')
                    ->where('id', $applicationId)
                    ->update(['coin_hold_id' => $coinHoldId]);
            } else {
                $holdId = WalletHelper::hold($user->id, $applicationId, $job->id);
                DB::table('applications')
                    ->where('id', $applicationId)
                    ->update(['wallet_hold_id' => $holdId]);
            }
            $firm = DB::table('firm_profiles')
                ->where('id', $job->firm_id)
                ->first();
            NotificationHelper::create(
                $firm->user_id,
                'New application received',
                $user->name .
                    ' applied for ' .
                    $job->title . '.',
                false, // explicit richer push dispatched below
                '/firm-jobs/' . $id . '/applications'
            );
            // Push notification (additive layer — queued on the database driver,
            // so the job row commits atomically with this transaction).
            SendUserPushJob::dispatch(
                (int) $firm->user_id,
                $user->name . ' applied for ' . $job->title,
                'Review the application.',
                '/firm-jobs/' . $id . '/applications'
            );
            DB::commit();

            // Activity log (async, non-blocking — never affects the application).
            ActivityTracker::log(ActivityTracker::STUDENT, $user->id, ActivityType::JOB_APPLIED, [
                'application_id' => (int) $applicationId,
                'job_id'         => (int) $job->id,
                'job_title'      => $job->title,
                'firm_id'        => (int) $job->firm_id,
                'firm_name'      => $firm->firm_name ?? null,
                'payment_source' => $paymentSource,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Job applied successfully'
            ]);
        } catch (InsufficientFundsException $e) {
            // Lost the race for the last bit of balance/coins — fail cleanly, no charge.
            DB::rollBack();
            return response()->json([
                'status'               => false,
                'message'              => 'Insufficient balance. Please recharge your wallet to continue applying.',
                'insufficient_balance' => true,
                'application_fee'      => WalletHelper::APPLICATION_FEE,
            ]);
        } catch (QueryException $e) {
            DB::rollBack();
            // 1062 = duplicate key on uq_application_job_student — a concurrent /
            // double-submitted request already created the application. Treat as the
            // existing "already applied" case (no second application, no second hold).
            if (($e->errorInfo[1] ?? null) === 1062) {
                return response()->json([
                    'status'  => false,
                    'message' => 'You already applied for this job'
                ], 409);
            }
            Log::error("Apply Job API Error (query) : " . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong',
            ], 500);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Apply Job API Error : " . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
            ], 500);
        }
    }
    public function saveJob(Request $request, $id)
    {
        try {
            /*
        |--------------------------------------------------------------------------
        | GET USER FROM TOKEN
        |--------------------------------------------------------------------------
        */
            $token = $request->cookie('auth_token');
            if (!$token) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }
            $user = AuthHelper::resolveUser($request);
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid token'
                ], 401);
            }
            /*
        |--------------------------------------------------------------------------
        | STUDENT ONLY
        |--------------------------------------------------------------------------
        */
            if ($user->role !== 'student') {
                return response()->json([
                    'status' => false,
                    'message' => 'Only students can save jobs'
                ], 403);
            }
            /*
        |--------------------------------------------------------------------------
        | CHECK JOB EXISTS
        |--------------------------------------------------------------------------
        */
            $job = DB::table('jobs')
                ->where('id', $id)
                ->where('is_active', true)
                ->first();
            if (!$job) {
                return response()->json([
                    'status' => false,
                    'message' => 'Job not found'
                ], 404);
            }
            /*
        |--------------------------------------------------------------------------
        | CHECK ALREADY SAVED
        |--------------------------------------------------------------------------
        */
            $alreadySaved = DB::table('saved_jobs')
                ->where('student_id', $user->id)
                ->where('job_id', $id)
                ->exists();
            /*
        |--------------------------------------------------------------------------
        | UNSAVE IF ALREADY SAVED
        |--------------------------------------------------------------------------
        */
            if ($alreadySaved) {
                DB::table('saved_jobs')
                    ->where('student_id', $user->id)
                    ->where('job_id', $id)
                    ->delete();
                return response()->json([
                    'status' => true,
                    'saved' => false,
                    'message' => 'Job removed from saved jobs'
                ]);
            }
            /*
        |--------------------------------------------------------------------------
        | SAVE JOB
        |--------------------------------------------------------------------------
        */
            DB::table('saved_jobs')->insert([
                'student_id' => $user->id,
                'job_id' => $id,
            ]);
            /*
        |--------------------------------------------------------------------------
        | SUCCESS RESPONSE
        |--------------------------------------------------------------------------
        */
            return response()->json([
                'status' => true,
                'saved' => true,
                'message' => 'Job saved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Save Job API Error', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            return response()->json([
                'status' => false,
                'message' => 'Unexpected server error while saving job.',
            ], 500);
        }
    }
    public function getAppliedJobs(Request $request)
    {
        try {
            /*
        |--------------------------------------------------------------------------
        | GET USER FROM TOKEN
        |--------------------------------------------------------------------------
        */
            $token = $request->cookie('auth_token');
            if (!$token) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }
            $user = AuthHelper::resolveUser($request);
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid token'
                ], 401);
            }
            /*
        |--------------------------------------------------------------------------
        | STUDENT ONLY
        |--------------------------------------------------------------------------
        */
            if ($user->role !== 'student') {
                return response()->json([
                    'status' => false,
                    'message' => 'Only students can access applied jobs'
                ], 403);
            }
            /*
        |--------------------------------------------------------------------------
        | FETCH APPLIED JOBS
        |--------------------------------------------------------------------------
        */
            $query = DB::table('applications')
                ->join(
                    'jobs',
                    'applications.job_id',
                    '=',
                    'jobs.id'
                )
                ->join(
                    'firm_profiles',
                    'jobs.firm_id',
                    '=',
                    'firm_profiles.id'
                )
                ->leftJoin(
                    'users as firm_users',
                    'firm_profiles.user_id',
                    '=',
                    'firm_users.id'
                )
                ->select(
                    // application
                    'applications.id as application_id',
                    'applications.recruiter_status',
                    'applications.applied_at',
                    'applications.interview_date',
                    'applications.interview_mode',
                    'applications.interview_location',
                    'applications.interview_note',
                    'applications.student_interview_response',
                    'applications.student_response_note',
                    'applications.recruiter_notes',
                    'applications.interview_reschedule_count',
                    // job
                    'jobs.*',
                    // firm
                    'firm_profiles.firm_name',
                    'firm_profiles.logo_path',
                    // firm contact — exposed to the student ONLY when confirmed
                    'firm_users.email as firm_email',
                    'firm_users.mobile as firm_mobile'
                )
                ->where(
                    'applications.student_id',
                    $user->id
                )
                ->where(function ($q) {
                    $q->whereNull('applications.recruiter_status')
                        ->orWhere(
                            'applications.recruiter_status',
                            '!=',
                            'Rejected'
                        );
                })
                ->orderBy(
                    'applications.applied_at',
                    'desc'
                );
            /*
        |--------------------------------------------------------------------------
        | PAGINATION
        |--------------------------------------------------------------------------
        */
            $jobs = $query->paginate(10);
            /*
        |--------------------------------------------------------------------------
        | FORMAT RESPONSE
        |--------------------------------------------------------------------------
        */
            $data = collect($jobs->items())->map(function ($job) {
                return [
                    /*
                |--------------------------------------------------------------------------
                | Application Data
                |--------------------------------------------------------------------------
                */
                    'application_id' =>
                    $job->application_id,
                    'recruiter_status' =>
                    $job->recruiter_status ?? 'Applied',
                    'applied_at' =>
                    $job->applied_at
                        ? date(
                            'd M Y g:i A',
                            strtotime($job->applied_at)
                        )
                        : null,
                    'interview_date' =>
                    $job->interview_date,
                    'interview_mode' =>
                    $job->interview_mode,
                    'interview_location' =>
                    $job->interview_location,
                    // Contact sharing (2026-07-11): the firm's contact is visible
                    // ONLY while the interview is confirmed — it re-hides on any
                    // later status change (rejected/cancelled/expired).
                    'firm_contact' =>
                    $job->recruiter_status === 'Interview Confirmed'
                        ? [
                            'name'   => $job->firm_name,
                            'email'  => $job->firm_email,
                            'mobile' => $job->firm_mobile,
                        ]
                        : null,
                    'interview_note' =>
                    $job->interview_note,
                    'student_interview_response' =>
                    $job->student_interview_response,
                    'student_response_note' =>
                    $job->student_response_note,
                    'recruiter_notes' =>
                    $job->recruiter_notes,
                    'interview_reschedule_count' =>
                    (int) ($job->interview_reschedule_count ?? 0),
                    /*
                |--------------------------------------------------------------------------
                | Job Data
                |--------------------------------------------------------------------------
                */
                    'id' =>
                    $job->id,
                    'firm_id' =>
                    $job->firm_id,
                    'firm_name' =>
                    $job->firm_name,
                    'firm_logo_path' =>
                    $job->logo_path
                        ? asset('/storage/' . $job->logo_path)
                        : null,
                    'title' =>
                    $job->title,
                    'location' =>
                    $job->location,
                    'type' =>
                    $job->type,
                    'salary' =>
                    $job->salary,
                    'description' =>
                    $job->description,
                    'department' =>
                    $job->department,
                    'work_mode' =>
                    $job->work_mode,
                    'experience_level' =>
                    $job->experience_level,
                    'openings' =>
                    $job->openings,
                    'required_skills' =>
                    json_decode(
                        $job->required_skills,
                        true
                    ) ?? [],
                    'benefits' =>
                    $job->benefits,
                    'required_qualification' =>
                    $job->required_qualification,
                    'application_deadline' =>
                    $job->application_deadline,
                    'status' =>
                    $job->status,
                    'is_active' =>
                    $job->is_active,
                    'is_applied' =>
                    true,
                    'created_at' =>
                    $job->created_at
                        ? date(
                            'd M Y g:i A',
                            strtotime($job->created_at)
                        )
                        : null,
                ];
            });
            /*
        |--------------------------------------------------------------------------
        | RESPONSE
        |--------------------------------------------------------------------------
        */
            return response()->json([
                'status' => true,
                'message' =>
                'Applied jobs fetched successfully',
                'data' => [
                    'jobs' =>
                    $data,
                    'current_page' =>
                    $jobs->currentPage(),
                    'last_page' =>
                    $jobs->lastPage(),
                    'per_page' =>
                    $jobs->perPage(),
                    'total' =>
                    $jobs->total(),
                    'next_page_url' =>
                    $jobs->nextPageUrl(),
                    'prev_page_url' =>
                    $jobs->previousPageUrl(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Get Applied Jobs API Error', [
                'message' =>
                $e->getMessage(),
                'line' =>
                $e->getLine(),
                'file' =>
                $e->getFile(),
            ]);
            return response()->json([
                'status' => false,
                'message' =>
                'Unexpected server error while fetching applied jobs.',
            ], 500);
        }
    }
    public function getSavedJobs(Request $request)
    {
        try {
            /*
        |--------------------------------------------------------------------------
        | GET USER FROM TOKEN
        |--------------------------------------------------------------------------
        */
            $token = $request->cookie('auth_token');
            if (!$token) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }
            $user = AuthHelper::resolveUser($request);
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid token'
                ], 401);
            }
            /*
        |--------------------------------------------------------------------------
        | STUDENT ONLY
        |--------------------------------------------------------------------------
        */
            if ($user->role !== 'student') {
                return response()->json([
                    'status' => false,
                    'message' => 'Only students can access saved jobs'
                ], 403);
            }
            /*
        |--------------------------------------------------------------------------
        | FETCH SAVED JOBS
        |--------------------------------------------------------------------------
        */
            $query = DB::table('saved_jobs')
                ->join('jobs', 'saved_jobs.job_id', '=', 'jobs.id')
                ->join('firm_profiles', 'jobs.firm_id', '=', 'firm_profiles.id')
                ->select(
                    'saved_jobs.id as saved_job_id',
                    'jobs.*',
                    'firm_profiles.firm_name',
                    'firm_profiles.logo_path'
                )
                ->where('saved_jobs.student_id', $user->id)
                ->orderBy('saved_jobs.id', 'desc');
            /*
        |--------------------------------------------------------------------------
        | PAGINATION
        |--------------------------------------------------------------------------
        */
            $jobs = $query->paginate(10);
            $appliedJobIds = [];
            if ($user && $user->role === 'student') {
                $appliedJobIds = DB::table('applications')
                    ->where('student_id', $user->id)
                    ->pluck('job_id')
                    ->toArray();
            }
            /*
        |--------------------------------------------------------------------------
        | FORMAT RESPONSE
        |--------------------------------------------------------------------------
        */
            $data = collect($jobs->items())->map(function ($job) use ($appliedJobIds) {
                return [
                    'saved_job_id' => $job->saved_job_id,
                    'id' => $job->id,
                    'firm_id' => $job->firm_id,
                    'firm_name' => $job->firm_name,
                    'firm_logo_path' => $job->logo_path
                        ? asset('/storage/' . $job->logo_path)
                        : null,
                    'title' => $job->title,
                    'location' => $job->location,
                    'type' => $job->type,
                    'salary' => $job->salary,
                    'description' => $job->description,
                    'department' => $job->department,
                    'work_mode' => $job->work_mode,
                    'experience_level' => $job->experience_level,
                    'openings' => $job->openings,
                    'required_skills' =>
                    json_decode($job->required_skills, true) ?? [],
                    'benefits' => $job->benefits,
                    'required_qualification' =>
                    $job->required_qualification,
                    'application_deadline' =>
                    $job->application_deadline,
                    'status' => $job->status,
                    'is_active' => $job->is_active,
                    'is_saved' => true,
                    'is_applied' => in_array($job->id, $appliedJobIds),
                    'created_at' => $job->created_at
                        ? date('d M Y g:i A', strtotime($job->created_at))
                        : null,
                ];
            });
            /*
        |--------------------------------------------------------------------------
        | RESPONSE
        |--------------------------------------------------------------------------
        */
            return response()->json([
                'status' => true,
                'message' => 'Saved jobs fetched successfully',
                'data' => [
                    'jobs' => $data,
                    'current_page' => $jobs->currentPage(),
                    'last_page' => $jobs->lastPage(),
                    'per_page' => $jobs->perPage(),
                    'total' => $jobs->total(),
                    'next_page_url' => $jobs->nextPageUrl(),
                    'prev_page_url' => $jobs->previousPageUrl(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Get Saved Jobs API Error', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            return response()->json([
                'status' => false,
                'message' => 'Unexpected server error while fetching saved jobs.',
            ], 500);
        }
    }

    public function getApplications(Request $request, $jobId = null)
    {
        try {

            $token = $request->cookie('auth_token');

            if (!$token) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            $user = AuthHelper::resolveUser($request);

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid token'
                ], 401);
            }

            $firm = DB::table('firm_profiles')
                ->where('user_id', $user->id)
                ->first();

            if (!$firm) {
                return response()->json([
                    'status' => false,
                    'message' => 'Firm profile not found'
                ], 404);
            }

            $job = DB::table('jobs')
                ->where('id', $jobId)
                ->where('firm_id', $firm->id)
                ->first();

            if (!$job) {
                return response()->json([
                    'status' => false,
                    'message' => 'Job not found or access denied'
                ], 403);
            }

            $isPremium = SubscriptionHelper::isPremiumFirm($firm->id);

            $applicationLimit = $isPremium ? 999999 : 2;

            $alreadyVisibleApplications = 0;

            if (!$isPremium) {

                $alreadyVisibleApplications = DB::table('applications as a')
                    ->join('jobs as j', 'j.id', '=', 'a.job_id')
                    ->where('j.firm_id', $firm->id)
                    ->where('a.is_visible_to_firm', 1)
                    ->count();
            }

            $query = DB::table('applications')
                ->join('users', 'applications.student_id', '=', 'users.id')
                ->leftJoin('student_profiles', 'student_profiles.user_id', '=', 'users.id')
                ->join('jobs', 'applications.job_id', '=', 'jobs.id')
                ->select(
                    'applications.id',
                    'applications.job_id',
                    'applications.student_id',
                    'applications.recruiter_status',
                    'applications.status',
                    'applications.applied_at',
                    'applications.shortlisted_at',
                    'applications.rejected_at',
                    'applications.interview_requested_at',
                    'applications.interview_responded_at',
                    'applications.selected_at',
                    'applications.interview_date',
                    'applications.interview_mode',
                    'applications.interview_location',
                    'applications.interview_note',
                    'applications.student_interview_response',
                    'applications.student_response_note',
                    'applications.recruiter_notes',
                    'applications.is_visible_to_firm',
                    'users.name',
                    'users.email',
                    'users.mobile',
                    'users.profile_image',
                    'student_profiles.city',
                    'student_profiles.gender',
                    'student_profiles.ca_status',
                    'student_profiles.registration_type',
                    'student_profiles.articleship_status',
                    'student_profiles.core_department',
                    'student_profiles.attempts',
                    'student_profiles.experience_years',
                    'student_profiles.industry_worked_in',
                    'student_profiles.experience_department',
                    'student_profiles.resume_path',
                    'student_profiles.linkedin_url',
                    'student_profiles.portfolio_url',
                    'jobs.title as job_title',
                    'jobs.department as job_department',
                    'jobs.location as job_location',
                    'users.email_verified_at',
                )
                ->where('applications.job_id', $jobId)
                ->orderBy('applications.applied_at', 'desc');

            $totalApplications = (clone $query)->count();

            $applications = $query->get();

            $formatted = $applications->map(function ($item) use (
                $isPremium,
                $applicationLimit,
                &$alreadyVisibleApplications
            ) {

                if ($isPremium) {

                    $isLocked = false;
                } else {

                    if ($item->is_visible_to_firm) {

                        $isLocked = false;
                    } else {

                        if ($alreadyVisibleApplications < $applicationLimit) {

                            DB::table('applications')
                                ->where('id', $item->id)
                                ->update([
                                    'is_visible_to_firm' => 1
                                ]);

                            $alreadyVisibleApplications++;

                            $isLocked = false;
                        } else {

                            $isLocked = true;
                        }
                    }
                }

                return [

                    'id' => (string) $item->id,

                    'job_id' => (string) $item->job_id,

                    'student_id' => (string) $item->student_id,

                    'recruiter_status' => $item->recruiter_status ?? 'Applied',

                    'applied_at' => $item->applied_at
                        ? date('d M Y', strtotime($item->applied_at))
                        : null,

                    'interview_date' => $item->interview_date,

                    'interview_mode' => $item->interview_mode,

                    'interview_location' => $item->interview_location,

                    'interview_note' => $item->interview_note,

                    // Contact sharing (2026-07-11): the candidate's contact is
                    // visible ONLY while the interview is confirmed — it re-hides
                    // on any later status change (rejected/cancelled/expired).
                    'interview_contact' => $item->recruiter_status === 'Interview Confirmed'
                        ? [
                            'name'   => $item->name,
                            'email'  => $item->email,
                            'mobile' => $item->mobile,
                        ]
                        : null,

                    'student_interview_response' => $item->student_interview_response,

                    'student_response_note' => $item->student_response_note,

                    'recruiter_notes' => $item->recruiter_notes,

                    // Distinguish WHY a rejected application is rejected so the
                    // Rejected tab can show the right reason. A student declining a
                    // scheduled interview also sets recruiter_status='Rejected', but
                    // leaves student_interview_response='Rejected' — that's the tell.
                    'rejection_reason' => $item->recruiter_status === 'Rejected'
                        ? ($item->student_interview_response === 'Rejected'
                            ? 'Candidate declined interview invitation.'
                            : 'Rejected by Firm.')
                        : null,

                    'is_locked' => $isLocked,

                    'student' => [

                        'id' => (string) $item->student_id,

                        'name' => $item->name,

                        'email' => $isLocked
                            ? null
                            : $item->email,

                        'city' => $isLocked
                            ? null
                            : $item->city,

                        'qualification' => $isLocked
                            ? null
                            : $item->ca_status,

                        'preferred_department' => $isLocked
                            ? null
                            : $item->core_department,

                        'experience' => $isLocked
                            ? null
                            : $item->experience_years,

                        'profile_photo' => $isLocked
                            ? null
                            : (
                                !empty($item->profile_image)
                                ? asset('/storage/' . $item->profile_image)
                                : null
                            ),

                        'resume_path' => $isLocked
                            ? null
                            : (
                                !empty($item->resume_path)
                                ? asset('/storage/' . $item->resume_path)
                                : null
                            ),

                        'is_verified' => !$isLocked && !empty($item->email_verified_at),

                        'skills' => $isLocked
                            ? []
                            : array_values(array_filter(array_merge(
                                // core_department may be JSON array string or plain string
                                is_array(json_decode($item->core_department ?? '', true))
                                    ? json_decode($item->core_department, true)
                                    : (empty($item->core_department) ? [] : [$item->core_department]),
                                // industry_worked_in is now a JSON array
                                !empty($item->industry_worked_in)
                                    ? (is_array(json_decode($item->industry_worked_in ?? '', true))
                                        ? array_values(array_filter(json_decode($item->industry_worked_in, true)))
                                        : (trim($item->industry_worked_in ?? '') !== '' ? [$item->industry_worked_in] : []))
                                    : [],
                                // experience_department may be doubly-encoded; strip stray quotes/brackets
                                !empty($item->experience_department)
                                    ? array_values(array_filter(array_map(
                                        fn($v) => trim(trim((string) $v, '"[]')),
                                        is_array($item->experience_department)
                                            ? $item->experience_department
                                            : (json_decode($item->experience_department, true) ?: [])
                                    )))
                                    : []
                            ))),
                    ],

                    'job' => [

                        'id' => (string) $item->job_id,

                        'title' => $item->job_title,

                        'department' => $item->job_department,

                        'location' => $item->job_location,
                    ],
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'Applications fetched successfully',
                'data' => [
                    'job' => [
                        'id' => (string) $job->id,
                        'title' => $job->title,
                    ],
                    'applications' => $formatted,
                    'total' => $totalApplications,
                    'visible_unlocks' => $alreadyVisibleApplications,
                    'locked_applications' => $formatted
                        ->where('is_locked', true)
                        ->count(),
                    'is_premium' => $isPremium,
                ]
            ]);
        } catch (\Exception $e) {

            Log::error('Get Applications API Error', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Unexpected server error while fetching applications.',
            ]);
        }
    }


    public function updateApplicationStatus(Request $request, $applicationId = null)
    {
        try {
            /*
        |--------------------------------------------------------------------------
        | Validate Request
        |--------------------------------------------------------------------------
        */
            $request->validate([
                'recruiter_status' =>
                'required|string|in:Shortlisted,Rejected,Selected'
            ]);
            /*
        |--------------------------------------------------------------------------
        | Authenticate Firm
        |--------------------------------------------------------------------------
        */
            $token = $request->cookie('auth_token');
            if (!$token) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }
            $user = AuthHelper::resolveUser($request);
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid token'
                ], 401);
            }
            /*
        |--------------------------------------------------------------------------
        | Get Firm
        |--------------------------------------------------------------------------
        */
            $firm = DB::table('firm_profiles')
                ->where('user_id', $user->id)
                ->first();
            if (!$firm) {
                return response()->json([
                    'status' => false,
                    'message' => 'Firm profile not found'
                ], 404);
            }
            /*
        |--------------------------------------------------------------------------
        | Get Application + Verify Ownership
        |--------------------------------------------------------------------------
        */
            $application = DB::table('applications')
                ->join(
                    'jobs',
                    'applications.job_id',
                    '=',
                    'jobs.id'
                )
                ->select(
                    'applications.*',
                    'jobs.firm_id',
                    'jobs.title as job_title'
                )
                ->where('applications.id', $applicationId)
                ->first();
            if (!$application) {
                return response()->json([
                    'status' => false,
                    'message' => 'Application not found'
                ], 404);
            }
            /*
        |--------------------------------------------------------------------------
        | Verify Job Belongs To Current Firm
        |--------------------------------------------------------------------------
        */
            if ((int) $application->firm_id !== (int) $firm->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Access denied'
                ], 403);
            }
            /*
        |--------------------------------------------------------------------------
        | Prevent Duplicate Status Update
        |--------------------------------------------------------------------------
        */
            $status = $request->recruiter_status;
            if ($application->recruiter_status === $status) {
                return response()->json([
                    'status' => false,
                    'message' => 'Application already marked as ' . $status,
                ]);
            }
            /*
        |--------------------------------------------------------------------------
        | Free Action Limit Check (Save = Shortlisted consumes a free action)
        |--------------------------------------------------------------------------
        */
            if ($status === 'Shortlisted') {
                $freeCheck = FreeActionsHelper::canPerformFreeAction($firm->id);
                if (!$freeCheck['allowed']) {
                    return response()->json([
                        'status'  => false,
                        'reason'  => 'free_limit_reached',
                        'message' => $freeCheck['message'],
                    ], 403);
                }
            }
            /*
        |--------------------------------------------------------------------------
        | Prepare Update Data
        |--------------------------------------------------------------------------
        */
            $updateData = [
                'recruiter_status' => $status,
                'updated_at' => now(),
            ];
            if ($status === 'Shortlisted') {
                $updateData['shortlisted_at'] = now();
            }
            if ($status === 'Rejected') {
                $updateData['rejected_at'] = now();
            }
            if ($status === 'Selected') {
                $updateData['selected_at'] = now();
            }
            /*
        |--------------------------------------------------------------------------
        | Update Application
        |--------------------------------------------------------------------------
        */
            DB::table('applications')
                ->where('id', $applicationId)
                ->update($updateData);

            // Wallet / SYS Coins: release hold on rejection (each no-ops if not the
            // currency that paid for this application).
            if ($status === 'Rejected') {
                WalletHelper::release((int) $applicationId, 'rejected');
                SysCoinHelper::release((int) $applicationId, 'rejected');
            }

            /*
        |--------------------------------------------------------------------------
        | Prevent Duplicate Recruiter Actions
        |--------------------------------------------------------------------------
        */
            $existingAction = DB::table('recruiter_actions')
                ->where('firm_id', $firm->id)
                ->where('student_id', $application->student_id)
                ->where('application_id', $application->id)
                ->where('action_type', strtolower($status))
                ->first();
            /*
        |--------------------------------------------------------------------------
        | Insert Recruiter Action
        |--------------------------------------------------------------------------
        */
            if (!$existingAction) {
                DB::table('recruiter_actions')->insert([
                    'firm_id' => $firm->id,
                    'student_id' => $application->student_id,
                    'visible_to' => 'student',
                    'job_id' => $application->job_id,
                    'application_id' => $application->id,
                    'action_type' => strtolower($status),
                    'title' => match ($status) {
                        'Shortlisted' =>
                        'You were shortlisted',
                        'Rejected' =>
                        'Application not progressing',
                        'Selected' =>
                        'Selected for the role',
                        default => $status,
                    },
                    'message' => match ($status) {
                        'Shortlisted' =>
                        'Your profile matched recruiter requirements.',
                        'Rejected' =>
                        'The recruiter decided not to proceed further.',
                        'Selected' =>
                        'Congratulations! You have been selected.',
                        default => null,
                    },
                    'action_status' => strtolower($status),
                    'created_at' => now(),
                ]);
            }
            /*
        |--------------------------------------------------------------------------
        | Fetch Updated Application
        |--------------------------------------------------------------------------
        */
            $updatedApplication = DB::table('applications')
                ->where('id', $applicationId)
                ->first();
            /*
        |--------------------------------------------------------------------------
        | Success Response
        |--------------------------------------------------------------------------
        */
            return response()->json([
                'status' => true,
                'message' => 'Application status updated successfully',
                'data' => [
                    'application_id' =>
                    (string) $application->id,
                    'recruiter_status' =>
                    $status,
                    'application' =>
                    $updatedApplication,
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Update Application Status API Error', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            return response()->json([
                'status' => false,
                'message' =>
                'Unexpected server error while updating application status.',
            ]);
        }
    }
    public function scheduleInterview(Request $request, $applicationId = null)
    {
        try {
            /*
        |--------------------------------------------------------------------------
        | Validate Request
        |--------------------------------------------------------------------------
        */
            $request->validate([
                'interview_date' =>
                'required|date',
                // Online/Offline are the current UI values; older modes stay valid
                // so historical data and legacy clients keep working.
                'interview_mode' =>
                'required|string|in:Online,Offline,Physical,Telephonic,To Be Discussed',
                'interview_location' =>
                'nullable|string|max:500',
                'interview_note' =>
                'nullable|string',
            ]);
            /*
        |--------------------------------------------------------------------------
        | Authenticate Firm
        |--------------------------------------------------------------------------
        */
            $token = $request->cookie('auth_token');
            if (!$token) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }
            $user = AuthHelper::resolveUser($request);
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid token'
                ], 401);
            }
            /*
        |--------------------------------------------------------------------------
        | Get Firm
        |--------------------------------------------------------------------------
        */
            $firm = DB::table('firm_profiles')
                ->where('user_id', $user->id)
                ->first();
            if (!$firm) {
                return response()->json([
                    'status' => false,
                    'message' => 'Firm profile not found'
                ], 404);
            }
            /*
        |--------------------------------------------------------------------------
        | Get Application + Verify Ownership
        |--------------------------------------------------------------------------
        */
            $application = DB::table('applications')
                ->join(
                    'jobs',
                    'applications.job_id',
                    '=',
                    'jobs.id'
                )
                ->select(
                    'applications.*',
                    'jobs.firm_id',
                    'jobs.title as job_title'
                )
                ->where('applications.id', $applicationId)
                ->first();
            if (!$application) {
                return response()->json([
                    'status' => false,
                    'message' => 'Application not found'
                ], 404);
            }
            /*
        |--------------------------------------------------------------------------
        | Verify Job Ownership
        |--------------------------------------------------------------------------
        */
            if ((int) $application->firm_id !== (int) $firm->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Access denied'
                ], 403);
            }
            /*
        |--------------------------------------------------------------------------
        | Scheduling Gate (Phase 2, 2026-07-08)
        |--------------------------------------------------------------------------
        | Credit is consumed at STUDENT confirmation (respondInterview 'Accepted'),
        | not here. Scheduling is gated by distinct in-flight + confirmed
        | candidates so a firm can't queue more interviews than its free limit
        | could ever confirm. Re-requesting for an already-committed candidate is
        | allowed.
        */
            $freeCheck = FreeActionsHelper::canScheduleInterview($firm->id, (int) $application->student_id);
            if (!$freeCheck['allowed']) {
                return response()->json([
                    'status'  => false,
                    'reason'  => 'free_limit_reached',
                    'message' => $freeCheck['message'],
                ], 403);
            }

            /*
        |--------------------------------------------------------------------------
        | Prevent Duplicate Interview Request
        |--------------------------------------------------------------------------
        */
            if (
                $application->recruiter_status === 'Interview Requested'
                &&
                $application->student_interview_response === 'Pending'
            ) {
                return response()->json([
                    'status' => false,
                    'message' => 'Interview request already pending'
                ]);
            }
            /*
        |--------------------------------------------------------------------------
        | Update Application
        |--------------------------------------------------------------------------
        */
            DB::beginTransaction();
            DB::table('applications')
                ->where('id', $applicationId)
                ->update([
                    'recruiter_status' =>
                    'Interview Requested',
                    'interview_requested_at' =>
                    now(),
                    'interview_date' =>
                    $request->interview_date,
                    'interview_mode' =>
                    $request->interview_mode,
                    'interview_location' =>
                    $request->interview_location,
                    'interview_note' =>
                    $request->interview_note,
                    'student_interview_response' =>
                    'Pending',
                    'updated_at' =>
                    now(),
                ]);
            /*
        |--------------------------------------------------------------------------
        | Prevent Duplicate Recruiter Action
        |--------------------------------------------------------------------------
        */
            $existingAction = DB::table('recruiter_actions')
                ->where('firm_id', $firm->id)
                ->where('student_id', $application->student_id)
                ->where('application_id', $application->id)
                ->where('action_type', 'interview_requested')
                ->where('action_status', 'pending')
                ->first();
            /*
        |--------------------------------------------------------------------------
        | Insert Recruiter Action
        |--------------------------------------------------------------------------
        */
            if (!$existingAction) {
                // Push notification (additive layer — queued, atomic with this transaction).
                SendUserPushJob::dispatch(
                    (int) $application->student_id,
                    $firm->firm_name . ' invited you for an interview',
                    date('D, d M Y \a\t h:i A', strtotime($request->interview_date))
                        . ' · ' . $request->interview_mode . ' — tap to respond.',
                    '/recruiter-actions',
                    [],
                    'interview_app_' . $application->id // app-flow interview thread tag
                );
                DB::table('recruiter_actions')->insert([
                    'firm_id' =>
                    $firm->id,
                    'student_id' =>
                    $application->student_id,
                    'visible_to' => 'student',
                    'job_id' =>
                    $application->job_id,
                    'application_id' =>
                    $application->id,
                    'action_type' =>
                    'interview_requested',
                    'title' =>
                    'Interview requested',
                    'message' =>
                    'The recruiter invited you for an interview.',
                    'action_status' =>
                    'pending',
                    'action_date' =>
                    $request->interview_date,
                    'action_note' =>
                    $request->interview_note,
                    'meta' => json_encode([
                        'interview_mode' =>
                        $request->interview_mode,
                    ]),
                    'created_at' =>
                    now(),
                ]);
            }
            /*
        |--------------------------------------------------------------------------
        | Fetch Updated Full Application
        |--------------------------------------------------------------------------
        */
            $updatedApplication = DB::table('applications')
                ->join(
                    'users',
                    'applications.student_id',
                    '=',
                    'users.id'
                )
                ->leftJoin(
                    'student_profiles',
                    'student_profiles.user_id',
                    '=',
                    'users.id'
                )
                ->join(
                    'jobs',
                    'applications.job_id',
                    '=',
                    'jobs.id'
                )
                ->select(
                    // application
                    'applications.*',
                    // user
                    'users.name',
                    'users.email',
                    'users.profile_image',
                    // student profile
                    'student_profiles.city',
                    'student_profiles.ca_status',
                    'student_profiles.core_department',
                    'student_profiles.experience_years',
                    'student_profiles.resume_path',
                    'student_profiles.industry_worked_in',
                    'student_profiles.experience_department',
                    // job
                    'jobs.title as job_title',
                    'jobs.department as job_department',
                    'jobs.location as job_location',
                )
                ->where('applications.id', $applicationId)
                ->first();
            /*
        |--------------------------------------------------------------------------
        | Format Response
        |--------------------------------------------------------------------------
        */
            $formattedApplication = [
                'id' =>
                (string) $updatedApplication->id,
                'job_id' =>
                (string) $updatedApplication->job_id,
                'student_id' =>
                (string) $updatedApplication->student_id,
                'recruiter_status' =>
                $updatedApplication->recruiter_status,
                'applied_at' =>
                $updatedApplication->applied_at,
                'interview_date' =>
                $updatedApplication->interview_date,
                'interview_mode' =>
                $updatedApplication->interview_mode,
                'interview_note' =>
                $updatedApplication->interview_note,
                'student_interview_response' =>
                $updatedApplication->student_interview_response,
                'recruiter_notes' =>
                $updatedApplication->recruiter_notes,
                'student' => [
                    'id' =>
                    (string) $updatedApplication->student_id,
                    'name' =>
                    $updatedApplication->name,
                    'email' =>
                    $updatedApplication->email,
                    'city' =>
                    $updatedApplication->city,
                    'qualification' =>
                    $updatedApplication->ca_status,
                    'preferred_department' =>
                    $updatedApplication->core_department,
                    'experience' =>
                    $updatedApplication->experience_years,
                    'profile_photo' =>
                    !empty($updatedApplication->profile_image)
                        ? asset('/storage/' . $updatedApplication->profile_image)
                        : null,
                    'resume_path' =>
                    !empty($updatedApplication->resume_path)
                        ? asset('/storage/' . $updatedApplication->resume_path)
                        : null,
                    'skills' => array_values(array_filter(array_merge(
                        // core_department may be JSON array string or plain string
                        is_array(json_decode($updatedApplication->core_department ?? '', true))
                            ? json_decode($updatedApplication->core_department, true)
                            : (empty($updatedApplication->core_department) ? [] : [$updatedApplication->core_department]),
                        // industry_worked_in is now a JSON array
                        !empty($updatedApplication->industry_worked_in)
                            ? (is_array(json_decode($updatedApplication->industry_worked_in ?? '', true))
                                ? array_values(array_filter(json_decode($updatedApplication->industry_worked_in, true)))
                                : (trim($updatedApplication->industry_worked_in ?? '') !== '' ? [$updatedApplication->industry_worked_in] : []))
                            : [],
                        // experience_department may be doubly-encoded; strip stray quotes/brackets
                        !empty($updatedApplication->experience_department)
                            ? array_values(array_filter(array_map(
                                fn($v) => trim(trim((string) $v, '"[]')),
                                is_array($updatedApplication->experience_department)
                                    ? $updatedApplication->experience_department
                                    : (json_decode($updatedApplication->experience_department, true) ?: [])
                            )))
                            : []
                    ))),
                ],
                'job' => [
                    'id' =>
                    (string) $updatedApplication->job_id,
                    'title' =>
                    $updatedApplication->job_title,
                    'department' =>
                    $updatedApplication->job_department,
                    'location' =>
                    $updatedApplication->job_location,
                ],
            ];
            /*
        |--------------------------------------------------------------------------
        | Send Interview Scheduled Email (queued)
        |--------------------------------------------------------------------------
        */
            try {
                $interviewDateFormatted = date('D, d M Y \a\t h:i A', strtotime($request->interview_date));
                app(EmailNotificationService::class)->sendInterviewScheduled(
                    $updatedApplication->email,
                    $updatedApplication->name,
                    $firm->firm_name,
                    $updatedApplication->job_title,
                    $interviewDateFormatted,
                    $request->interview_mode,
                    $request->interview_note,
                    (int) $applicationId
                );
            } catch (\Throwable $e) {
                Log::error('Failed to queue interview scheduled email', [
                    'application_id' => $applicationId,
                    'error' => $e->getMessage(),
                ]);
            }
            /*
        |--------------------------------------------------------------------------
        | Success Response
        |--------------------------------------------------------------------------
        */
            DB::commit();

            // Activity log (async, non-blocking — never affects interview scheduling).
            ActivityTracker::log(ActivityTracker::FIRM, $user->id, ActivityType::INTERVIEW_SCHEDULED, [
                'application_id' => (int) $application->id,
                'job_id'         => (int) $application->job_id,
                'student_id'     => (int) $application->student_id,
                'interview_date' => $request->interview_date,
            ]);
            // Firm Activity Center feed (non-blocking).
            FirmActivityHelper::log(
                $firm->id,
                FirmActivityHelper::INTERVIEW_SCHEDULED,
                'Scheduled Interview with ' . $updatedApplication->name . ' for "' . $updatedApplication->job_title . '"'
            );

            return response()->json([
                'status' => true,
                'message' =>
                'Interview request sent successfully',
                'data' => [
                    'application' =>
                    $formattedApplication,
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Schedule Interview API Error', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            return response()->json([
                'status' => false,
                'message' =>
                'Unexpected server error while scheduling interview.',
            ]);
        }
    }
    public function getRecruiterActions(Request $request)
    {
        try {
            /*
        |--------------------------------------------------------------------------
        | GET USER FROM TOKEN
        |--------------------------------------------------------------------------
        */
            $token = $request->cookie('auth_token');
            if (!$token) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }
            $user = AuthHelper::resolveUser($request);
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid token'
                ], 401);
            }
            /*
        |--------------------------------------------------------------------------
        | STUDENT ONLY
        |--------------------------------------------------------------------------
        */
            if ($user->role !== 'student') {
                return response()->json([
                    'status' => false,
                    'message' => 'Only students can access recruiter actions'
                ], 403);
            }
            /*
        |--------------------------------------------------------------------------
        | FETCH ACTIONS
        |--------------------------------------------------------------------------
        */
            $actions = DB::table('recruiter_actions')
                ->join(
                    'firm_profiles',
                    'recruiter_actions.firm_id',
                    '=',
                    'firm_profiles.id'
                )
                ->leftJoin(
                    'jobs',
                    'recruiter_actions.job_id',
                    '=',
                    'jobs.id'
                )
                ->leftJoin(
                    'applications',
                    'recruiter_actions.application_id',
                    '=',
                    'applications.id'
                )
                ->leftJoin(
                    'interview_invites',
                    'recruiter_actions.interview_invite_id',
                    '=',
                    'interview_invites.id'
                )
                ->leftJoin(
                    'users as firm_users',
                    'firm_profiles.user_id',
                    '=',
                    'firm_users.id'
                )
                ->select(
                    'recruiter_actions.*',
                    'firm_profiles.firm_name',
                    'firm_profiles.logo_path',
                    'firm_users.email as firm_email',
                    'firm_users.mobile as firm_mobile',
                    'jobs.title as job_title',
                    'applications.interview_date',
                    'applications.interview_mode',
                    'applications.interview_location',
                    'applications.interview_note',
                    'applications.student_interview_response',
                    'applications.interview_reschedule_count',
                    'applications.recruiter_status as application_recruiter_status',
                    'interview_invites.invite_status',
                    'interview_invites.interview_status',
                    'interview_invites.interview_date as invite_interview_date',
                    'interview_invites.interview_mode as invite_interview_mode',
                    'interview_invites.interview_location as invite_interview_location',
                    'interview_invites.interview_note as invite_interview_note',
                    'interview_invites.student_interview_response as invite_student_response',
                    'interview_invites.reschedule_count as invite_reschedule_count'
                )
                ->where(
                    'recruiter_actions.student_id',
                    $user->id
                )
                // ->where(function ($q) {
                //     $q->where(
                //         'recruiter_actions.visible_to',
                //         'student'
                //     )
                //         ->orWhere(
                //             'recruiter_actions.visible_to',
                //             'both'
                //         );
                // })
                ->where('recruiter_actions.visible_to', 'student')
                ->orderBy(
                    'recruiter_actions.created_at',
                    'desc'
                )
                ->get();
            /*
        |--------------------------------------------------------------------------
        | FORMAT RESPONSE
        |--------------------------------------------------------------------------
        */
            $formatted = $actions->map(function ($item) {
                return [
                    'id' =>
                    (string) $item->id,
                    'type' =>
                    $item->action_type,
                    'title' =>
                    $item->title,
                    'message' =>
                    $item->message,
                    'action_status' =>
                    $item->action_status,
                    'created_at' =>
                    $item->created_at,
                    'read' =>
                    (bool) $item->is_read,
                    /*
                |--------------------------------------------------------------------------
                | Firm
                |--------------------------------------------------------------------------
                */
                    'firm_id' =>
                    (string) $item->firm_id,
                    'firm_name' =>
                    $item->firm_name,
                    'firm_logo' =>
                    !empty($item->logo_path)
                        ? asset('/storage/' . $item->logo_path)
                        : null,
                    /*
                |--------------------------------------------------------------------------
                | Job
                |--------------------------------------------------------------------------
                */
                    'job_id' =>
                    !empty($item->job_id)
                        ? (string) $item->job_id
                        : null,
                    'job_title' =>
                    $item->job_title,
                    /*
                |--------------------------------------------------------------------------
                | Application
                |--------------------------------------------------------------------------
                */
                    'application_id' =>
                    !empty($item->application_id)
                        ? (string) $item->application_id
                        : null,
                    /*
                |--------------------------------------------------------------------------
                | Interview
                |--------------------------------------------------------------------------
                */
                    'interview_date' =>
                    $item->action_type === 'interview_invite'
                        ? $item->invite_interview_date
                        : $item->interview_date,
                    'interview_mode' =>
                    $item->action_type === 'interview_invite'
                        ? $item->invite_interview_mode
                        : $item->interview_mode,
                    'interview_location' =>
                    $item->action_type === 'interview_invite'
                        ? $item->invite_interview_location
                        : $item->interview_location,
                    'interview_note' =>
                    $item->action_type === 'interview_invite'
                        ? $item->invite_interview_note
                        : $item->interview_note,
                    // Contact sharing (2026-07-11): the firm's contact is visible
                    // ONLY while this action's interview is currently confirmed —
                    // it re-hides on any later status change.
                    'firm_contact' =>
                    (
                        ($item->action_type === 'interview_invite' && ($item->interview_status ?? null) === 'confirmed')
                        || (!empty($item->application_id) && ($item->application_recruiter_status ?? null) === 'Interview Confirmed')
                    )
                        ? [
                            'name'   => $item->firm_name,
                            'email'  => $item->firm_email,
                            'mobile' => $item->firm_mobile,
                        ]
                        : null,
                    'student_response' =>
                    $item->action_type === 'interview_invite'
                        ? $item->invite_student_response
                        : $item->student_interview_response,
                    // Invite-specific (null for non-invite actions)
                    'interview_invite_id' =>
                    !empty($item->interview_invite_id)
                        ? (string) $item->interview_invite_id
                        : null,
                    'invite_status' =>
                    $item->invite_status ?? null,
                    'interview_status' =>
                    $item->interview_status ?? null,
                    // How many times the student has requested a reschedule for
                    // this interview (max 1 allowed — UI hides the button after).
                    'reschedule_count' =>
                    $item->action_type === 'interview_invite'
                        ? (int) ($item->invite_reschedule_count ?? 0)
                        : (int) ($item->interview_reschedule_count ?? 0),
                ];
            });
            /*
        |--------------------------------------------------------------------------
        | Unread Count
        |--------------------------------------------------------------------------
        */
            $unreadCount = DB::table('recruiter_actions')
                ->where(
                    'student_id',
                    $user->id
                )
                ->where(function ($q) {
                    $q->whereNull('is_read')
                        ->orWhere('is_read', false);
                })
                ->count();
            /*
        |--------------------------------------------------------------------------
        | RESPONSE
        |--------------------------------------------------------------------------
        */
            return response()->json([
                'status' => true,
                'message' =>
                'Recruiter actions fetched successfully',
                'data' => [
                    'actions' =>
                    $formatted,
                    'unread_count' =>
                    $unreadCount,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Get Recruiter Actions API Error', [
                'message' =>
                $e->getMessage(),
                'line' =>
                $e->getLine(),
                'file' =>
                $e->getFile(),
            ]);
            return response()->json([
                'status' => false,
                'message' =>
                'Unexpected server error while fetching recruiter actions.',
            ], 500);
        }
    }
    public function respondInterview(Request $request, $applicationId = null)
    {
        try {
            /*
        |--------------------------------------------------------------------------
        | Validate Request
        |--------------------------------------------------------------------------
        */
            $request->validate([
                'response' =>
                'required|string|in:Accepted,Rejected,Reschedule Requested',
                'reschedule_date' =>
                'nullable|date',
                'reschedule_note' =>
                'nullable|string',
            ]);
            /*
        |--------------------------------------------------------------------------
        | Authenticate Student
        |--------------------------------------------------------------------------
        */
            $token = $request->cookie('auth_token');
            if (!$token) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }
            $user = AuthHelper::resolveUser($request);
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid token'
                ], 401);
            }
            /*
        |--------------------------------------------------------------------------
        | Student Only
        |--------------------------------------------------------------------------
        */
            if ($user->role !== 'student') {
                return response()->json([
                    'status' => false,
                    'message' => 'Only students can respond to interviews'
                ], 403);
            }
            /*
        |--------------------------------------------------------------------------
        | Fetch Application
        |--------------------------------------------------------------------------
        */
            $application = DB::table('applications')
                ->select('applications.*', 'jobs.firm_id')
                ->join('jobs', 'applications.job_id', '=', 'jobs.id')
                ->where('applications.id', $applicationId)
                ->where('applications.student_id', $user->id)
                ->first();
            if (!$application) {
                return response()->json([
                    'status' => false,
                    'message' => 'Application not found'
                ], 404);
            }
            /*
        |--------------------------------------------------------------------------
        | Prevent Duplicate Response
        |--------------------------------------------------------------------------
        */
            if (
                !empty($application->student_interview_response)
                &&
                $application->student_interview_response !== 'Pending'
                &&
                $request->response !== 'Reschedule Requested'
            ) {
                return response()->json([
                    'status' => false,
                    'message' => 'Interview already responded'
                ]);
            }
            /*
        |--------------------------------------------------------------------------
        | Max ONE reschedule request (Phase 2)
        |--------------------------------------------------------------------------
        */
            if (
                $request->response === 'Reschedule Requested'
                && (int) ($application->interview_reschedule_count ?? 0) >= 1
            ) {
                return response()->json([
                    'status'  => false,
                    'reason'  => 'reschedule_limit',
                    'message' => 'You have already requested a reschedule once. Please accept or reject the interview.',
                ], 409);
            }
            /*
        |--------------------------------------------------------------------------
        | Update Application
        |--------------------------------------------------------------------------
        */
            $updateData = [
                'student_interview_response' =>
                $request->response,
                'student_response_note' =>
                $request->reschedule_note,
                'interview_responded_at' =>
                now(),
                'updated_at' =>
                now(),
            ];
            /*
        |--------------------------------------------------------------------------
        | Reschedule Requested
        |--------------------------------------------------------------------------
        */
            if ($request->response === 'Reschedule Requested') {
                $updateData['interview_date'] =
                    $request->reschedule_date;
                $updateData['interview_reschedule_count'] =
                    (int) ($application->interview_reschedule_count ?? 0) + 1;
            }
            /*
        |--------------------------------------------------------------------------
        | Accepted — CONSUME the interview credit (single consumption point for
        | the applications flow; set once, never cleared).
        |--------------------------------------------------------------------------
        */
            if ($request->response === 'Accepted') {
                $updateData['recruiter_status'] =
                    'Interview Confirmed';
                if (empty($application->interview_credit_consumed_at)) {
                    $updateData['interview_credit_consumed_at'] = now();
                }
            }
            /*
        |--------------------------------------------------------------------------
        | Rejected
        |--------------------------------------------------------------------------
        */
            if ($request->response === 'Rejected') {
                // Land the application in the firm's existing "Rejected" tab. The tab
                // filters on recruiter_status === 'Rejected' exactly, so the previous
                // 'Interview Rejected' value was never picked up (root cause). The
                // separate student_interview_response = 'Rejected' (set above) is what
                // lets the UI show the reason "Candidate declined interview invitation"
                // instead of a plain firm rejection.
                $updateData['recruiter_status'] = 'Rejected';
                $updateData['rejected_at']      = now();
            }
            DB::beginTransaction();
            DB::table('applications')
                ->where('id', $applicationId)
                ->update($updateData);

            // Wallet / SYS Coins: consume hold when student accepts interview (each
            // no-ops if not the currency that paid for this application).
            if ($request->response === 'Accepted') {
                WalletHelper::consume((int) $applicationId);
                SysCoinHelper::consume((int) $applicationId);
            }

            /*
        |--------------------------------------------------------------------------
        | Recruiter Action Type
        |--------------------------------------------------------------------------
        */
            $actionType = match ($request->response) {
                'Accepted' =>
                'interview_confirmed',
                'Rejected' =>
                'interview_rejected',
                'Reschedule Requested' =>
                'interview_reschedule_received',
                default =>
                'message',
            };
            /*
        |--------------------------------------------------------------------------
        | Insert Recruiter Action
        |--------------------------------------------------------------------------
        */
            DB::table('recruiter_actions')->insert([
                'firm_id' =>
                $application->firm_id,
                'student_id' =>
                $application->student_id,
                'visible_to' => 'firm',
                'job_id' =>
                $application->job_id,
                'application_id' =>
                $application->id,
                'action_type' =>
                $actionType,
                'title' => match ($request->response) {
                    'Accepted' =>
                    'Interview accepted',
                    'Rejected' =>
                    'Interview rejected',
                    'Reschedule Requested' =>
                    'Reschedule requested',
                    default =>
                    'Interview update',
                },
                'message' => match ($request->response) {
                    'Accepted' =>
                    'Student accepted interview invitation.',
                    'Rejected' =>
                    'Student rejected interview invitation.',
                    'Reschedule Requested' =>
                    'Student requested interview reschedule.',
                    default =>
                    null,
                },
                'action_status' =>
                strtolower($request->response),
                'action_date' =>
                $request->reschedule_date,
                'action_note' =>
                $request->reschedule_note,
                'created_at' =>
                now(),
            ]);
            // Push notification (additive layer — queued, atomic with this transaction).
            // Covers all three student responses, incl. reschedule requests.
            $pushFirmUserId = DB::table('firm_profiles')
                ->where('id', $application->firm_id)
                ->value('user_id');
            if ($pushFirmUserId) {
                [$pushTitle, $pushBody] = match ($request->response) {
                    'Accepted' => [
                        $user->name . ' confirmed the interview',
                        $application->interview_date
                            ? 'Interview on ' . date('D, d M Y \a\t h:i A', strtotime($application->interview_date)) . '.'
                            : 'The interview is locked in.',
                    ],
                    'Rejected' => [
                        $user->name . ' rejected the interview',
                        'The interview will not go ahead.',
                    ],
                    'Reschedule Requested' => [
                        $user->name . ' requested a new interview time',
                        $request->reschedule_date
                            ? 'Proposed: ' . date('D, d M Y', strtotime($request->reschedule_date))
                            : 'Review the reschedule request.',
                    ],
                    default => [null, null],
                };
                if ($pushTitle) {
                    SendUserPushJob::dispatch(
                        (int) $pushFirmUserId,
                        $pushTitle,
                        $pushBody,
                        '/firm-applications',
                        [],
                        'interview_app_' . $application->id // replaces older notifications for this interview
                    );
                }
            }
            /*
        |--------------------------------------------------------------------------
        | Send Firm Notification Email (queued)
        |--------------------------------------------------------------------------
        */
            if (in_array($request->response, ['Accepted', 'Rejected'], true)) {
                try {
                    $firmUser = DB::table('firm_profiles')
                        ->join('users', 'firm_profiles.user_id', '=', 'users.id')
                        ->where('firm_profiles.id', $application->firm_id)
                        ->select('users.email as firm_email', 'firm_profiles.firm_name')
                        ->first();

                    $jobRecord = DB::table('jobs')
                        ->where('id', $application->job_id)
                        ->select('title')
                        ->first();

                    if ($firmUser && $jobRecord) {
                        $base        = config('app.frontend_url', 'https://startyourstory.in');
                        $viewUrl     = "{$base}/firm-jobs";
                        $emailSvc    = app(EmailNotificationService::class);

                        if ($request->response === 'Accepted') {
                            $interviewDateFormatted = date(
                                'D, d M Y \a\t h:i A',
                                strtotime($application->interview_date)
                            );
                            $emailSvc->sendInterviewAccepted(
                                $firmUser->firm_email,
                                $user->name,
                                $jobRecord->title,
                                $interviewDateFormatted,
                                $application->interview_mode ?? '',
                                $viewUrl
                            );
                        } else {
                            $emailSvc->sendInterviewRejected(
                                $firmUser->firm_email,
                                $user->name,
                                $jobRecord->title,
                                $viewUrl
                            );
                        }
                    }
                } catch (\Throwable $e) {
                    Log::error('Failed to queue interview response email', [
                        'application_id' => $applicationId,
                        'response'       => $request->response,
                        'error'          => $e->getMessage(),
                    ]);
                }
            }
            /*
        |--------------------------------------------------------------------------
        | Success
        |--------------------------------------------------------------------------
        */
            DB::commit();

            // Activity log (async, non-blocking) — only the accept is a tracked action.
            if ($request->response === 'Accepted') {
                ActivityTracker::log(ActivityTracker::STUDENT, $user->id, ActivityType::INTERVIEW_ACCEPTED, [
                    'application_id' => (int) $application->id,
                    'job_id'         => (int) $application->job_id,
                    'firm_id'        => (int) $application->firm_id,
                ]);
            }

            return response()->json([
                'status' => true,
                'message' =>
                'Interview response submitted successfully',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Respond Interview API Error', [
                'message' =>
                $e->getMessage(),
                'line' =>
                $e->getLine(),
                'file' =>
                $e->getFile(),
            ]);
            return response()->json([
                'status' => false,
                'message' =>
                'Unexpected server error while responding to interview.',
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /applications/{id}/accept-reschedule
    // Firm accepts student's proposed reschedule date
    // ─────────────────────────────────────────────────────────────────────────
    public function acceptReschedule(Request $request, $applicationId)
    {
        try {
            $token = $request->cookie('auth_token');
            if (!$token) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }
            $user = AuthHelper::resolveUser($request);
            if (!$user) {
                return response()->json(['status' => false, 'message' => 'Invalid token'], 401);
            }

            $firm = DB::table('firm_profiles')->where('user_id', $user->id)->first();
            if (!$firm) {
                return response()->json(['status' => false, 'message' => 'Firm profile not found'], 403);
            }

            $application = DB::table('applications')
                ->join('jobs', 'applications.job_id', '=', 'jobs.id')
                ->select('applications.*', 'jobs.firm_id', 'jobs.title as job_title')
                ->where('applications.id', $applicationId)
                ->first();

            if (!$application) {
                return response()->json(['status' => false, 'message' => 'Application not found'], 404);
            }
            if ((int) $application->firm_id !== (int) $firm->id) {
                return response()->json(['status' => false, 'message' => 'Access denied'], 403);
            }
            if ($application->student_interview_response !== 'Reschedule Requested') {
                return response()->json(['status' => false, 'message' => 'No pending reschedule request to accept'], 422);
            }

            DB::beginTransaction();
            DB::table('applications')->where('id', $applicationId)->update([
                'recruiter_status'            => 'Interview Scheduled',
                'student_interview_response'  => 'Pending',
                'reschedule_accepted_at'      => now(),
                'updated_at'                  => now(),
            ]);

            DB::table('recruiter_actions')->insert([
                'firm_id'          => $firm->id,
                'student_id'       => $application->student_id,
                'visible_to'       => 'student',
                'job_id'           => $application->job_id,
                'application_id'   => $application->id,
                'action_type'      => 'reschedule_accepted',
                'title'            => 'Reschedule accepted',
                'message'          => 'The firm accepted your proposed interview date.',
                'action_status'    => 'accepted',
                'action_date'      => $application->interview_date,
                'created_at'       => now(),
            ]);

            // Push notification (additive layer — queued, atomic with this transaction).
            SendUserPushJob::dispatch(
                (int) $application->student_id,
                $firm->firm_name . ' accepted your new interview date',
                'Your proposed interview date has been confirmed.',
                '/recruiter-actions',
                [],
                'interview_app_' . $application->id // replaces older notifications for this interview
            );

            // Email to student
            try {
                $student = DB::table('users')->where('id', $application->student_id)->first();
                if ($student && $application->interview_date) {
                    $formatted = date('D, d M Y', strtotime($application->interview_date));
                    app(EmailNotificationService::class)->sendInterviewRescheduleAccepted(
                        $student->email,
                        $student->name,
                        $firm->firm_name,
                        $application->job_title,
                        $formatted,
                        $application->interview_note
                    );
                }
            } catch (\Throwable $e) {
                Log::error('Failed to send reschedule-accepted email', [
                    'application_id' => $applicationId,
                    'error'          => $e->getMessage(),
                ]);
            }

            DB::commit();
            $updated = DB::table('applications')->where('id', $applicationId)->first();

            return response()->json([
                'status'  => true,
                'message' => 'Reschedule accepted. Interview confirmed for the proposed date.',
                'data'    => [
                    'application' => $updated,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Accept Reschedule Error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }
}
