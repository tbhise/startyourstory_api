<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Notifications\EmailNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Helpers\NotificationHelper;
use App\Helpers\SubscriptionHelper;
use App\Helpers\WalletHelper;


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
            $user = DB::table('users')
                ->where('api_token', $token)
                ->where('is_deleted', false)
                ->first();
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
        | Wallet check — free quota or sufficient balance
        |--------------------------------------------------------------------------
        */
            $isFree = WalletHelper::isFreeApplication($user->id);
            if (!$isFree && !WalletHelper::hasEnoughBalance($user->id)) {
                return response()->json([
                    'status'               => false,
                    'message'              => 'Free application limit reached. Please recharge your wallet or upgrade your plan to continue applying.',
                    'insufficient_balance' => true,
                    'free_limit_reached'   => true,
                    'application_fee'      => WalletHelper::APPLICATION_FEE,
                ]);
            }

            $applicationId = DB::table('applications')->insertGetId([
                'job_id'                => $id,
                'student_id'            => $user->id,
                'status'                => 'Applied',
                'is_free_application'   => $isFree ? 1 : 0,
                'application_fee'       => $isFree ? 0.00 : WalletHelper::APPLICATION_FEE,
                'applied_at'            => now(),
                'updated_at'            => now(),
            ]);

            if ($isFree) {
                WalletHelper::incrementFreeUsage($user->id);
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
                    $job->title . '.'
            );
            return response()->json([
                'status' => true,
                'message' => 'Job applied successfully'
            ]);
        } catch (\Exception $e) {
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
            $user = DB::table('users')
                ->where('api_token', $token)
                ->where('is_deleted', false)
                ->first();
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
            $user = DB::table('users')
                ->where('api_token', $token)
                ->where('is_deleted', false)
                ->first();
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
                ->select(
                    // application
                    'applications.id as application_id',
                    'applications.recruiter_status',
                    'applications.applied_at',
                    'applications.interview_date',
                    'applications.interview_mode',
                    'applications.interview_note',
                    'applications.student_interview_response',
                    'applications.student_response_note',
                    'applications.recruiter_notes',
                    // job
                    'jobs.*',
                    // firm
                    'firm_profiles.firm_name',
                    'firm_profiles.logo_path'
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
                    'interview_note' =>
                    $job->interview_note,
                    'student_interview_response' =>
                    $job->student_interview_response,
                    'student_response_note' =>
                    $job->student_response_note,
                    'recruiter_notes' =>
                    $job->recruiter_notes,
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
            $user = DB::table('users')
                ->where('api_token', $token)
                ->where('is_deleted', false)
                ->first();
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

            $user = DB::table('users')
                ->where('api_token', $token)
                ->where('is_deleted', false)
                ->first();

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
                    'applications.interview_note',
                    'applications.student_interview_response',
                    'applications.student_response_note',
                    'applications.recruiter_notes',
                    'applications.is_visible_to_firm',
                    'users.name',
                    'users.email',
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

                    'student_id' => $isLocked
                        ? 'locked'
                        : (string) $item->student_id,

                    'recruiter_status' => $item->recruiter_status ?? 'Applied',

                    'applied_at' => $item->applied_at
                        ? date('d M Y', strtotime($item->applied_at))
                        : null,

                    'interview_date' => $item->interview_date,

                    'interview_mode' => $item->interview_mode,

                    'interview_note' => $item->interview_note,

                    'student_interview_response' => $item->student_interview_response,

                    'student_response_note' => $item->student_response_note,

                    'recruiter_notes' => $item->recruiter_notes,

                    'is_locked' => $isLocked,

                    'student' => [

                        'id' => $isLocked
                            ? 'locked'
                            : (string) $item->student_id,

                        'name' => $isLocked
                            ? 'Premium Candidate'
                            : $item->name,

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

                                [
                                    $item->core_department,
                                    $item->industry_worked_in,
                                ],

                                !empty($item->experience_department)
                                    ? (
                                        is_array($item->experience_department)

                                        ? $item->experience_department

                                        : (
                                            json_decode(
                                                $item->experience_department,
                                                true
                                            ) ?: []
                                        )
                                    )
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
            $user = DB::table('users')
                ->where('api_token', $token)
                ->where('is_deleted', false)
                ->first();
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

            // Wallet: release hold on rejection
            if ($status === 'Rejected') {
                WalletHelper::release((int) $applicationId, 'rejected');
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
                'interview_mode' =>
                'required|string|in:Physical,Telephonic,To Be Discussed',
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
            $user = DB::table('users')
                ->where('api_token', $token)
                ->where('is_deleted', false)
                ->first();
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

                        [
                            $updatedApplication->core_department,
                            $updatedApplication->industry_worked_in,
                        ],

                        !empty($updatedApplication->experience_department)
                            ? (
                                is_array($updatedApplication->experience_department)

                                ? $updatedApplication->experience_department

                                : (
                                    json_decode(
                                        $updatedApplication->experience_department,
                                        true
                                    ) ?: []
                                )
                            )
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
            $user = DB::table('users')
                ->where('api_token', $token)
                ->where('is_deleted', false)
                ->first();
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
                ->select(
                    'recruiter_actions.*',
                    'firm_profiles.firm_name',
                    'firm_profiles.logo_path',
                    'jobs.title as job_title',
                    'applications.interview_date',
                    'applications.interview_mode',
                    'applications.interview_note',
                    'applications.student_interview_response'
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
                    $item->interview_date,
                    'interview_mode' =>
                    $item->interview_mode,
                    'interview_note' =>
                    $item->interview_note,
                    'student_response' =>
                    $item->student_interview_response,
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
            $user = DB::table('users')
                ->where('api_token', $token)
                ->where('is_deleted', false)
                ->first();
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
            }
            /*
        |--------------------------------------------------------------------------
        | Accepted
        |--------------------------------------------------------------------------
        */
            if ($request->response === 'Accepted') {
                $updateData['recruiter_status'] =
                    'Interview Confirmed';
            }
            /*
        |--------------------------------------------------------------------------
        | Rejected
        |--------------------------------------------------------------------------
        */
            if ($request->response === 'Rejected') {
                $updateData['recruiter_status'] =
                    'Interview Rejected';
            }
            DB::table('applications')
                ->where('id', $applicationId)
                ->update($updateData);

            // Wallet: consume hold when student accepts interview
            if ($request->response === 'Accepted') {
                WalletHelper::consume((int) $applicationId);
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
                        $viewUrl     = "{$base}/firm/applications";
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
            $user = DB::table('users')
                ->where('api_token', $token)
                ->where('is_deleted', false)
                ->first();
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

            $updated = DB::table('applications')->where('id', $applicationId)->first();

            return response()->json([
                'status'  => true,
                'message' => 'Reschedule accepted. Interview confirmed for the proposed date.',
                'data'    => [
                    'application' => $updated,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Accept Reschedule Error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }
}
