<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\User;
use App\Services\Notifications\EmailNotificationService;
use App\Helpers\NotificationHelper;

class UserController extends Controller
{
    public function registerStudent(Request $request)
    {
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'email' => 'required|email|unique:users,email',
                'mobile' => 'required|unique:users,mobile',
                'password' => 'required|min:6|max:15',
                'referral_code' => 'nullable|string|max:50',

            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ]);
            }
            $referrer = null;
            if ($request->referral_code) {
                $referrer = DB::table('users')
                    ->where('referral_code', strtoupper($request->referral_code))
                    ->first();
                if (!$referrer) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Invalid referral code'
                    ]);
                }
            }
            $namePrefix = strtoupper(
                substr(preg_replace('/[^A-Za-z]/', '', $request->name), 0, 4)
            );
            do {
                $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                $randomString = '';
                for ($i = 0; $i < 5; $i++) {
                    $randomString .= $characters[rand(0, strlen($characters) - 1)];
                }
                $myReferralCode = $namePrefix . $randomString;
            } while (
                DB::table('users')
                ->where('referral_code', $myReferralCode)
                ->exists()
            );
            $userId = DB::table('users')
                ->insertGetId([
                    'name' => $request->name,
                    'email' => $request->email,
                    'mobile' => $request->mobile,
                    'password' => bcrypt($request->password),
                    'role' => 'student',
                    'referral_code' => $myReferralCode,
                    'referred_by' => $referrer?->id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            DB::table('student_profiles')
                ->insert([
                    'user_id' => $userId,
                    'looking_for' => $request->looking_for,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            if ($referrer) {
                DB::table('users')
                    ->where('id', $referrer->id)
                    ->increment('referral_count');
            }



            if ($request->looking_for === 'creator') {
                $userType = 'creator';
            } else {
                $userType = 'student';
            }







            DB::commit();



            $user = User::where('id', $userId)->first();

            app(EmailNotificationService::class)->sendVerificationEmail($user);




            //  SendWelcomeEmailJob::dispatch(
            //                 $request->email,
            //                 $request->name,
            //                 $myReferralCode,
            //                 $userType
            //             );


            return response()->json([
                'status' => true,
                'message' => 'Candidate Registration successful..!',
                'data' => [
                    'referral_code' => $myReferralCode
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Candidate Registration Error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Candidate Registration failed: Server error'
            ]);
        }
    }
    public function updateProfile(Request $request)
    {
        DB::beginTransaction();
        try {
            // Log::info('Update Profile API called', ['request' => $request->all()]);
            $token = $request->cookie('auth_token');
            if (!$token) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated'
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
            $validator = Validator::make($request->all(), [
                'looking_for' => 'nullable|string',
                'srn' => 'nullable|string|max:10|unique:student_profiles,srn,' . $user->id . ',user_id',
                'city' => 'nullable|string',
                'gender' => 'nullable|string',
                'passing_month' => 'nullable|string',
                'ca_status' => 'nullable|string',
                'articleship_status' => 'nullable|string',
                'preferred_location' => 'nullable|string',
                'preferred_locations_json' => 'nullable|string',
                'preferred_categories' => 'nullable|array',
                'preferred_categories.*' => 'nullable|string',
                'it_oc_status' => 'nullable|string',
                'exposure_type' => 'nullable|string',
                'core_department' => 'nullable|string',
                'attempts' => 'nullable|string',
                'linkedin_url' => 'nullable|string',
                'portfolio_url' => 'nullable|string',
                'instagram_url' => 'nullable|string',
                'website_url' => 'nullable|string',
                'qualification' => 'nullable|string|max:100',
                'availability_status' => 'nullable|string|max:100',
                'current_firm_id' => 'nullable',
                'current_firm_name' => 'nullable|string',
                'experience_years' => 'nullable|string',
                'industry_worked_in'    => 'nullable|array',
                'industry_worked_in.*'  => 'nullable|string',
                'experience_department'   => 'nullable|array',
                'experience_department.*' => 'nullable|string',
                'why_should_hire_you' => 'nullable|string',
                'current_ctc' => 'nullable|string',
                'expected_ctc' => 'nullable|string',
                'show_in_directory' => 'nullable|boolean',
                'is_creator' => 'nullable|boolean',
                'resume_path' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'marksheet_path' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ]);
            }
            $existingProfile = DB::table('student_profiles')
                ->where('user_id', $user->id)->first();

            // ── Business-logic validation ──────────────────────────────────────
            $lookingForNorm = strtolower(trim($request->looking_for ?? ''));
            $caStatusNorm   = strtolower(trim($request->ca_status   ?? ''));

            // 1. Professional status required for articleship flow
            if ($lookingForNorm === 'articleship' && empty(trim($request->ca_status ?? ''))) {
                DB::rollBack();
                return response()->json([
                    'status'  => false,
                    'message' => 'Please select your professional status.',
                ]);
            }

            // 2. Core domain required for Cases A (inter-both), C (semi-qualified), D (qualified)
            $needsCoreDept = in_array($lookingForNorm, ['semi-qualified', 'qualified'])
                || ($lookingForNorm === 'articleship'
                    && in_array($caStatusNorm, ['inter-both', 'inter both groups passed']));
            if ($needsCoreDept && empty(trim($request->core_department ?? ''))) {
                DB::rollBack();
                return response()->json([
                    'status'  => false,
                    'message' => 'Please select your core domain.',
                ]);
            }

            // 3. Exposure preference: domain-wise mode must have at least one domain selected
            $needsExposure = in_array($lookingForNorm, ['semi-qualified', 'qualified'])
                || ($lookingForNorm === 'articleship'
                    && in_array($caStatusNorm, ['inter-both', 'inter both groups passed']));
            if ($needsExposure && $request->has('exposure_type')) {
                $exposureRaw   = trim($request->exposure_type ?? '');
                if ($exposureRaw !== 'overall') {
                    $exposureParts = array_values(array_filter(
                        array_map('trim', explode(',', $exposureRaw))
                    ));
                    if (empty($exposureParts)) {
                        DB::rollBack();
                        return response()->json([
                            'status'  => false,
                            'message' => 'Please select at least one preferred domain.',
                        ]);
                    }
                }
            }

            // 4. Resume required only when the Upload Docs section is shown in the wizard:
            //    Semi-Qualified, Qualified, or Articleship with Inter-Both status.
            //    (Mirrors the frontend buildSections() visibility rule — see $needsCoreDept above.)
            $hasExistingResume = !empty($existingProfile->resume_path ?? null);
            $resumeRequired    = in_array($lookingForNorm, ['semi-qualified', 'qualified'])
                || ($lookingForNorm === 'articleship'
                    && in_array($caStatusNorm, ['inter-both', 'inter both groups passed']));
            if ($resumeRequired && !$hasExistingResume && !$request->hasFile('resume_path')) {
                DB::rollBack();
                return response()->json([
                    'status'  => false,
                    'message' => 'Please upload your resume.',
                ]);
            }
            // ── End business-logic validation ──────────────────────────────────

            $resumePath = null;
            $marksheetPath = null;
            if ($request->hasFile('resume_path')) {
                $resume = $request->file('resume_path');
                $resumeName =
                    time() . '_resume.' .
                    $resume->getClientOriginalExtension();
                $resumePath = $resume->storeAs(
                    'resumes',
                    $resumeName,
                    'public'
                );
            }
            if ($request->hasFile('marksheet_path')) {
                $marksheet = $request->file('marksheet_path');
                $marksheetName =
                    time() . '_marksheet.' .
                    $marksheet->getClientOriginalExtension();
                $marksheetPath = $marksheet->storeAs(
                    'marksheets',
                    $marksheetName,
                    'public'
                );
            }
            $registrationType = 'provisional';
            if (
                in_array(
                    strtolower(trim($request->looking_for ?? '')),
                    ['semi-qualified', 'qualified']
                )
            ) {
                $registrationType = 'confirm';
            } elseif (
                strtolower(trim($request->looking_for ?? '')) === 'articleship'
            ) {
                $caStatus =
                    strtolower(trim($request->ca_status ?? ''));
                if (
                    in_array(
                        $caStatus,
                        [
                            'inter-both',
                            'inter both groups passed',
                            'doing-articleship',
                            'doing articleship'
                        ]
                    )
                ) {
                    $registrationType = 'confirm';
                }
            }


            Log::info($registrationType);
            $preferredLocations = [];
            if (!empty($request->preferred_locations_json)) {
                $preferredLocations =
                    json_decode($request->preferred_locations_json, true) ?? [];
            } elseif (!empty($request->preferred_location)) {
                $preferredLocations =
                    explode(',', $request->preferred_location);
            }
            $exposureTypes = [];
            if (!empty($request->exposure_type)) {
                $exposureTypes =
                    explode(',', $request->exposure_type);
            }
            $profileData = [
                'user_id' => $user->id,
                'looking_for' => $request->looking_for,
                'srn' => $request->srn,
                'address' => $request->city,
                'city' => $request->city,
                'gender' => $request->gender,
                'passing_month' => $request->passing_month,
                'ca_status' => $request->ca_status,
                'registration_type' => $registrationType,
                'articleship_status' => $request->articleship_status,
                'preferred_location' => json_encode($preferredLocations),
                'preferred_categories' => $request->has('preferred_categories')
                    ? json_encode($request->preferred_categories ?? [])
                    : ($existingProfile->preferred_categories ?? null),
                'it_oc_status' => $request->it_oc_status,
                'exposure_type' => json_encode($exposureTypes),
                'core_department' => $request->core_department,
                'attempts' => $request->attempts,
                'linkedin_url' => $request->linkedin_url,
                'portfolio_url' => $request->portfolio_url,
                'instagram_url' => $request->instagram_url,
                'website_url' => $request->website_url,
                'qualification' => $request->qualification,
                'availability_status' => $request->availability_status,
                'current_firm_id' => $request->current_firm_id,
                'current_firm_name' => $request->current_firm_name,
                'experience_years' => $request->experience_years,
                'industry_worked_in' => !empty($request->industry_worked_in)
                    ? json_encode(array_values(array_filter(
                        is_array($request->industry_worked_in)
                            ? $request->industry_worked_in
                            : [],
                        fn($v) => is_string($v) && trim($v) !== ''
                    )))
                    : null,
                'experience_department' => !empty($request->experience_department)
                    ? json_encode(array_values(array_filter(
                        is_array($request->experience_department)
                            ? $request->experience_department
                            : [],
                        fn($v) => is_string($v) && trim($v) !== ''
                    )))
                    : null,
                'why_should_hire_you' => $request->why_should_hire_you,
                'current_ctc' => $request->current_ctc,
                'expected_ctc' => $request->expected_ctc,
                'show_in_directory' => true,
                'is_creator' => $request->has('is_creator')
                    ? (bool)$request->is_creator
                    : ($existingProfile->is_creator ?? false),
                'updated_at' => now(),
            ];
            if ($resumePath) {
                $profileData['resume_path'] = $resumePath;
            }
            if ($marksheetPath) {
                $profileData['marksheet_path'] = $marksheetPath;
            }
            if ($existingProfile) {
                DB::table('student_profiles')
                    ->where('user_id', $user->id)
                    ->update($profileData);
            } else {
                $profileData['created_at'] = now();
                DB::table('student_profiles')
                    ->insert($profileData);
            }

            $wasAlreadyCompleted = (bool) ($user->profile_completed ?? false);

            $isProfileComplete = false;
            $resumeExists =
                $resumePath ||
                !empty($existingProfile->resume_path ?? null);
            $preferredLocationExists =
                !empty($preferredLocations);
            // Gender is optional in the wizard — do not gate completion on it.
            $basicInfoComplete =
                !empty($request->city);
            // if ($request->looking_for === 'articleship') {
            //     $isProfileComplete =
            //         $basicInfoComplete &&
            //         !empty($request->srn) &&
            //         $preferredLocationExists &&
            //         $resumeExists;
            //     if ($registrationType === 'confirm') {
            //         $isProfileComplete =
            //             $isProfileComplete &&
            //             !empty($request->it_oc_status) &&
            //             !empty($request->exposure_type) &&
            //             !empty($request->core_department);
            //     }
            // }

            if ($request->looking_for === 'articleship') {

                // Preferred location + resume are only shown for Inter-Both (Case A).
                // Doing-Articleship (Case B) shows neither, so it must skip them even though
                // its registration_type is 'confirm'.
                $skipLocationAndResume =
                    in_array($request->ca_status, [
                        'inter-g2',
                        'doing-articleship',
                        'doing articleship',
                        'inter-g1',
                        'pursuing-inter',
                        'foundation'
                    ]);

                $isProfileComplete =
                    $basicInfoComplete &&
                    !empty($request->srn);

                if (!$skipLocationAndResume) {
                    $isProfileComplete =
                        $isProfileComplete &&
                        $preferredLocationExists &&
                        $resumeExists;
                }

                // Inter-Both (Case A) wizard requires exposure, core domain and attempts.
                // IT/OC is shown but optional in the wizard, so it is NOT gated here.
                if ($registrationType === 'confirm' && !$skipLocationAndResume) {
                    $isProfileComplete =
                        $isProfileComplete &&
                        !empty($request->exposure_type) &&
                        !empty($request->core_department) &&
                        !empty($request->attempts);
                }

                // Doing-Articleship status (Case B) also collects the current articleship firm.
                if (in_array($request->ca_status, ['doing-articleship', 'doing articleship'])) {
                    $isProfileComplete =
                        $isProfileComplete &&
                        !empty($request->current_firm_name);
                }
            } elseif (
                strtolower(trim($request->looking_for ?? '')) === 'doing-articleship'
            ) {
                // Case B — wizard collects basic info, srn and the current articleship firm only.
                $isProfileComplete =
                    $basicInfoComplete &&
                    !empty($request->srn) &&
                    !empty($request->current_firm_name);
            } elseif (
                in_array(
                    $request->looking_for,
                    ['semi-qualified', 'qualified']
                )
            ) {
                $isProfileComplete =
                    $basicInfoComplete &&
                    !empty($request->srn) &&
                    // !empty($request->experience_years) &&
                    $preferredLocationExists &&
                    $resumeExists;
            } elseif ($request->looking_for === 'creator') {
                $prefCatsArr = $request->has('preferred_categories')
                    ? ($request->preferred_categories ?? [])
                    : json_decode($existingProfile->preferred_categories ?? '[]', true);
                $hasPrefCats = is_array($prefCatsArr) && count($prefCatsArr) > 0;
                $isProfileComplete =
                    !empty($request->city) &&
                    !empty($request->qualification) &&
                    !empty($request->availability_status) &&
                    !empty(trim($request->why_should_hire_you ?? '')) &&
                    !empty($request->experience_years) &&
                    $hasPrefCats;
            }


            // Extend completion check: students who opted into creator also need creator fields done
            $isCreatorOptin = $request->has('is_creator')
                ? (bool)$request->is_creator
                : (bool)($existingProfile->is_creator ?? false);

            if ($isCreatorOptin && $request->looking_for !== 'creator') {
                $prefCatsArr = $request->has('preferred_categories')
                    ? ($request->preferred_categories ?? [])
                    : json_decode($existingProfile->preferred_categories ?? '[]', true);
                $hasPrefCats = is_array($prefCatsArr) && count($prefCatsArr) > 0;
                $isCreatorFieldsComplete =
                    !empty($request->qualification) &&
                    !empty($request->availability_status) &&
                    !empty(trim($request->why_should_hire_you ?? '')) &&
                    !empty($request->experience_years) &&
                    $hasPrefCats;
                $isProfileComplete = $isProfileComplete && $isCreatorFieldsComplete;
            }

            Log::info($request->ca_status);
            Log::info($isProfileComplete);
            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'profile_completed' => $isProfileComplete ? 1 : 0,
                    'updated_at' => now()
                ]);

            // Determine whether to show the apply-limit awareness modal
            $showModal = false;
            if (
                $isProfileComplete &&
                !$wasAlreadyCompleted &&
                $request->looking_for !== 'creator'
            ) {
                $freshProfile = DB::table('student_profiles')->where('user_id', $user->id)->first();
                $dismissed = (bool) ($freshProfile->apply_limit_modal_dismissed ?? false);
                if (!$dismissed) {
                    $showModal = true;
                }
            }

            DB::commit();
            return response()->json([
                'status'               => true,
                'message'              => 'Profile updated successfully',
                'profile_completed'    => $isProfileComplete ? 1 : 0,
                'show_apply_limit_modal' => $showModal,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Profile Update Error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Profile update failed: Server error'
            ]);
        }
    }
    // ─────────────────────────────────────────────────────────────────────────
    // POST /dismiss-apply-limit-modal
    // ─────────────────────────────────────────────────────────────────────────

    public function dismissApplyLimitModal(Request $request)
    {
        try {
            $token = $request->cookie('auth_token');
            if (!$token) {
                return response()->json(['status' => false, 'message' => 'Unauthenticated'], 401);
            }
            $user = DB::table('users')->where('api_token', $token)->where('is_deleted', false)->first();
            if (!$user) {
                return response()->json(['status' => false, 'message' => 'Invalid token'], 401);
            }

            DB::table('student_profiles')
                ->where('user_id', $user->id)
                ->update(['apply_limit_modal_dismissed' => 1, 'updated_at' => now()]);

            return response()->json(['status' => true, 'message' => 'Dismissed.']);
        } catch (\Exception $e) {
            Log::error('UserController@dismissApplyLimitModal: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /account/request-deletion  (student only)
    // Schedules a 30-day soft delete: withdraws active applications, cancels
    // upcoming interviews (notifying firms), and logs the student out.
    // Logging in again within 30 days cancels the request (see AuthController@login).
    // ─────────────────────────────────────────────────────────────────────────
    public function requestAccountDeletion(Request $request)
    {
        $token = $request->cookie('auth_token');
        if (!$token) {
            return response()->json(['status' => false, 'message' => 'Unauthenticated'], 401);
        }
        $user = DB::table('users')
            ->where('api_token', $token)
            ->where('is_deleted', false)
            ->first();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Invalid token'], 401);
        }
        // Students only — never affect firm or admin accounts.
        if ($user->role !== 'student') {
            return response()->json([
                'status'  => false,
                'message' => 'Account deletion is only available for student accounts.',
            ], 403);
        }

        DB::beginTransaction();
        try {
            $now         = now();
            $scheduledAt = $now->copy()->addDays(30);

            // 1. Schedule the deletion.
            DB::table('users')->where('id', $user->id)->update([
                'deletion_requested_at' => $now,
                'scheduled_deletion_at' => $scheduledAt,
                'updated_at'            => $now,
            ]);

            // 2. Withdraw active applications + cancel upcoming interviews; notify firms.
            $activeApps = DB::table('applications')
                ->leftJoin('jobs', 'applications.job_id', '=', 'jobs.id')
                ->leftJoin('firm_profiles', 'jobs.firm_id', '=', 'firm_profiles.id')
                ->where('applications.student_id', $user->id)
                ->where(function ($q) {
                    $q->whereNull('applications.recruiter_status')
                        ->orWhereNotIn('applications.recruiter_status', ['Rejected', 'Withdrawn by Candidate']);
                })
                ->select(
                    'applications.id',
                    'applications.job_id',
                    'applications.interview_date',
                    'jobs.title as job_title',
                    'jobs.firm_id as firm_id',
                    'firm_profiles.user_id as firm_user_id'
                )
                ->get();

            foreach ($activeApps as $app) {
                $hasFutureInterview = !empty($app->interview_date)
                    && strtotime((string) $app->interview_date) > time();

                $update = [
                    'recruiter_status' => 'Withdrawn by Candidate',
                    'updated_at'       => $now,
                ];
                if ($hasFutureInterview) {
                    $update['student_interview_response'] = 'Withdrawn';
                }
                DB::table('applications')->where('id', $app->id)->update($update);

                // Notify the firm (only if a notification target exists).
                if (!empty($app->firm_user_id)) {
                    NotificationHelper::create(
                        (int) $app->firm_user_id,
                        $hasFutureInterview ? 'Interview cancelled' : 'Application withdrawn',
                        $hasFutureInterview
                            ? $user->name . ' deleted their account; their interview for "' . ($app->job_title ?? 'a job') . '" has been cancelled.'
                            : $user->name . ' withdrew their application for "' . ($app->job_title ?? 'a job') . '".'
                    );
                }

                // Firm-visible tracking entry.
                if (!empty($app->firm_id)) {
                    DB::table('recruiter_actions')->insert([
                        'firm_id'        => $app->firm_id,
                        'student_id'     => $user->id,
                        'visible_to'     => 'firm',
                        'job_id'         => $app->job_id,
                        'application_id' => $app->id,
                        'action_type'    => $hasFutureInterview ? 'interview_cancelled_by_candidate' : 'application_withdrawn',
                        'action_status'  => $hasFutureInterview ? 'cancelled' : 'withdrawn',
                        'title'          => $hasFutureInterview ? 'Interview Cancelled by Candidate' : 'Application Withdrawn by Candidate',
                        'message'        => $hasFutureInterview
                            ? 'The candidate cancelled their scheduled interview after deleting their account.'
                            : 'The candidate withdrew their application after deleting their account.',
                        'action_date'    => $hasFutureInterview ? $app->interview_date : null,
                        'created_at'     => $now,
                    ]);
                }
            }

            // 3. Log the student out (invalidate token + sessions).
            DB::table('users')->where('id', $user->id)->update([
                'api_token'        => null,
                'token_expires_at' => null,
            ]);
            DB::table('user_sessions')->where('user_id', $user->id)->delete();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('requestAccountDeletion Error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Could not process account deletion. Please try again.',
            ], 500);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Your account has been scheduled for deletion. Log in again within 30 days to cancel and restore it.',
            'data'    => ['scheduled_deletion_at' => $scheduledAt->toDateTimeString()],
        ])->cookie('auth_token', '', -1);
    }

    public function updateDirectoryVisibility(Request $request)
    {
        try {
            $token = $request->cookie('auth_token');
            if (!$token) {
                return response()->json(['status' => false, 'message' => 'Unauthenticated'], 401);
            }
            $user = DB::table('users')
                ->where('api_token', $token)
                ->where('is_deleted', false)
                ->first();
            if (!$user) {
                return response()->json(['status' => false, 'message' => 'Invalid token'], 401);
            }

            $validator = Validator::make($request->all(), [
                'show_in_directory' => 'required|boolean',
            ]);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
            }

            DB::table('student_profiles')
                ->where('user_id', $user->id)
                ->update([
                    'show_in_directory' => (bool) $request->show_in_directory,
                    'updated_at'        => now(),
                ]);

            return response()->json([
                'status'            => true,
                'message'           => 'Directory visibility updated',
                'show_in_directory' => (bool) $request->show_in_directory,
            ]);
        } catch (\Exception $e) {
            Log::error('updateDirectoryVisibility: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    public function getProfile(Request $request)
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
            $userId = $user->id;
            $profile = DB::table('student_profiles')
                ->where('user_id', $userId)
                ->first();
            return response()->json([
                'status' => true,
                'data' => [
                    'email_verified_at' => $user->email_verified_at,
                    'user' => $userId,
                    'profile' => $profile
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Get Profile Error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Unexpected server error..!'
            ]);
        }
    }
    public function updateProfileImage(Request $request)
    {
        try {
            $user = $request->attributes->get('auth_user');
            $userId = $user->id;
            $request->validate([
                'profile_image' => 'required|image',
            ]);
            $imagePath = null;
            if ($request->hasFile('profile_image')) {
                $file = $request->file('profile_image');
                $filename =
                    time() . '_profile.' .
                    $file->getClientOriginalExtension();
                $file->move(
                    public_path('storage/profile'),
                    $filename
                );
                $imagePath =
                    asset('storage/profile/' . $filename);
                DB::table('users')
                    ->where('id', $userId)
                    ->update([
                        'profile_image' => 'profile/' . $filename,
                        'updated_at' => now(),
                    ]);
            }
            return response()->json([
                'status' => true,
                'message' => 'Profile image updated successfully',
                'profile_image' => $imagePath,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
    public function trackRecruiterAction(Request $request, $studentId = null)
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
            if ($user->role !== 'firm') {
                return response()->json([
                    'status' => false,
                    'message' => 'Only recruiters can perform this action'
                ], 403);
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
            $student = DB::table('users')
                ->where('id', $studentId)
                ->where('role', 'student')
                ->where('is_deleted', false)
                ->first();
            if (!$student) {
                return response()->json([
                    'status' => false,
                    'message' => 'Student not found'
                ], 404);
            }
            $actionType = $request->action_type;
            $title = '';
            $message = '';
            $actionStatus = '';
            switch ($actionType) {
                case 'profile_viewed':
                    $title = 'Profile viewed';
                    $message = $firm->firm_name . ' viewed your profile.';
                    $actionStatus = 'viewed';
                    break;
                case 'shortlisted':
                    $title = 'Profile shortlisted';
                    $message = $firm->firm_name . ' shortlisted your profile.';
                    $actionStatus = 'shortlisted';
                    break;
                case 'candidate_rejected':
                    $title = 'Profile rejected';
                    $message = $firm->firm_name . ' rejected your profile.';
                    $actionStatus = 'rejected';
                    break;
                case 'candidate_saved':
                    $title = 'Profile saved';
                    $message = $firm->firm_name . ' saved your profile.';
                    $actionStatus = 'saved';
                    break;
            }
            $alreadyExists = DB::table('recruiter_actions')
                ->where('firm_id', $firm->id)
                ->where('student_id', $studentId)
                ->where('action_type', $actionType)
                ->where('created_at', '>=', now()->subHours(24))
                ->first();
            if (!$alreadyExists) {
                DB::table('recruiter_actions')
                    ->insert([
                        'firm_id' => $firm->id,
                        'student_id' => $studentId,
                        'action_type' => $actionType,
                        'title' => $title,
                        'message' => $message,
                        'visible_to' => 'student',
                        'action_status' => $actionStatus,
                        'created_at' => now(),
                    ]);
            }
            return response()->json([
                'status' => true,
                'message' => 'Recruiter action tracked successfully',
            ]);
        } catch (\Exception $e) {
            Log::error(
                'Track Recruiter Action API Error',
                [
                    'message' => $e->getMessage(),
                    'line' => $e->getLine(),
                    'file' => $e->getFile(),
                ]
            );
            return response()->json([
                'status' => false,
                'message' => 'Unexpected server error while tracking recruiter action.',
            ], 500);
        }
    }
    public function reportStudentProfile(Request $request)
    {
        DB::beginTransaction();
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
            $validator = Validator::make($request->all(), [
                'student_id' => 'required|exists:users,id',
                'reason' => 'required|string|max:100',
                'remarks' => 'nullable|string|max:1000',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first(),
                ]);
            }
            $alreadyReported = DB::table('reported_profiles')
                ->where('student_id', $request->student_id)
                ->where('reported_by', $user->id)
                ->exists();
            if ($alreadyReported) {
                return response()->json([
                    'status' => false,
                    'message' => 'You have already reported this profile.'
                ]);
            }
            DB::table('reported_profiles')->insert([
                'student_id' => $request->student_id,
                'reported_by' => $user->id,
                'reason' => $request->reason,
                'remarks' => $request->remarks,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Profile reported successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Report Profile Error', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong.'
            ]);
        }
    }

    /**
     *  Mail Services
     */



    public function sendVerificationLink(Request $request)
    {

        try {

            $token = $request->cookie('auth_token');
            if (!$token) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }
            $user = User::where('api_token', $token)
                ->where('is_deleted', false)
                ->first();
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid token'
                ], 401);
            }
            Log::alert('Send Verification Link API called', ['user_id' => $user->id, 'email' => $user->email]);
            if ($user->hasVerifiedEmail()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Email already verified.',
                ], 422);
            }
            app(EmailNotificationService::class)->sendVerificationEmail($user);
            return response()->json([
                'status' => true,
                'message' => 'Verification link sent successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error('Send Verification Link API Error', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            return response()->json([
                'status' => false,
                'message' => 'Unexpected server error while sending verification link.',
            ], 500);
        }
    }




    public function verificationStatus(Request $request)
    {
        try {
            $token = $request->cookie('auth_token');

            if (!$token) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }

            $user = User::where('api_token', $token)
                ->where('is_deleted', false)
                ->first();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid token',
                ], 401);
            }





            return response()->json([
                'status' => true,
                'verified' => !is_null($user->email_verified_at),
                'email_verified_at' => $user->email_verified_at,
                'email' => $user->email,
                'looking_for' => $user->looking_for,
            ]);
        } catch (\Exception $e) {
            Log::error('UserController@verificationStatus: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }




    public function verify(Request $request, $id, $hash)
    {

        if (! $request->hasValidSignature()) {

            return redirect()->away(
                env('FRONTEND_URL')
                    . '/email-verification-result?status=failed'
            );
        }

        $user = User::find($id);

        if (! $user) {

            return redirect()->away(
                env('FRONTEND_URL')
                    . '/email-verification-result?status=failed'
            );
        }

        if (! hash_equals(
            sha1($user->email),
            $hash
        )) {

            return redirect()->away(
                env('FRONTEND_URL')
                    . '/email-verification-result?status=failed'
            );
        }

        if (is_null($user->email_verified_at)) {

            $user->email_verified_at = now();
            $user->save();

            $role = $user->role;
            $myReferralCode = $user->referral_code;

            if ($role === 'firm') {
                $userType = 'firm';
            } else {


                $lookingFor = DB::table('student_profiles')->where('user_id', $user->id)->value('looking_for');


                if ($lookingFor === 'creator') {
                    $userType = 'creator';
                } else {
                    $userType = 'student';
                }
            }

            app(EmailNotificationService::class)->sendWelcomeEmail(
                $user->email,
                $user->name,
                $myReferralCode,
                $userType,
                120
            );
        }

        return redirect()->away(
            env('FRONTEND_URL')
                . '/email-verification-result?status=success'
        );
    }
}
