<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

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
                'password' => 'required|min:6|max:10',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ]);
            }
            $userId = DB::table('users')->insertGetId([
                'name' => $request->name,
                'email' => $request->email,
                'mobile' => $request->mobile,
                'password' => bcrypt($request->password),
                'role' => 'student',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            DB::table('student_profiles')->insert([
                'user_id' => $userId,
                'looking_for' => $request->looking_for,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Candidate Registration successfull..!'
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
            $validator = Validator::make($request->all(), [
                'looking_for' => 'nullable|string',
                'srn' => 'nullable|string',
                'city' => 'nullable|string',
                'gender' => 'nullable|string',
                'passing_month' => 'nullable|string',
                'ca_status' => 'nullable|string',
                'articleship_status' => 'nullable|string',
                'preferred_location' => 'nullable|string',
                'preferred_locations_json' => 'nullable|string',
                'it_oc_status' => 'nullable|string',
                'exposure_type' => 'nullable|string',
                'core_department' => 'nullable|string',
                'attempts' => 'nullable|string',
                'linkedin_url' => 'nullable|string',
                'portfolio_url' => 'nullable|string',
                'current_firm_id' => 'nullable',
                'current_firm_name' => 'nullable|string',
                'experience_years' => 'nullable|string',
                'industry_worked_in' => 'nullable|string',
                'experience_department' => 'nullable|string',
                'why_should_hire_you' => 'nullable|string',
                'current_ctc' => 'nullable|string',
                'expected_ctc' => 'nullable|string',
                'resume_path' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'marksheet_path' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ]);
            }
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
            $existingProfile = DB::table('student_profiles')
                ->where('user_id', $user->id)->first();
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
                'it_oc_status' => $request->it_oc_status,
                'exposure_type' => json_encode($exposureTypes),
                'core_department' => $request->core_department,
                'attempts' => $request->attempts,
                'linkedin_url' => $request->linkedin_url,
                'portfolio_url' => $request->portfolio_url,
                'current_firm_id' => $request->current_firm_id,
                'current_firm_name' => $request->current_firm_name,
                'experience_years' => $request->experience_years,
                'industry_worked_in' => $request->industry_worked_in,
                'experience_department' => $request->experience_department,
                'why_should_hire_you' => $request->why_should_hire_you,
                'current_ctc' => $request->current_ctc,
                'expected_ctc' => $request->expected_ctc,
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
            $isProfileComplete = false;
            if ($request->looking_for === 'articleship') {
                $isProfileComplete =
                    !empty($request->srn) &&
                    !empty($request->city) &&
                    !empty($request->gender);
                if ($registrationType === 'confirm') {
                    $confirmFieldsComplete =
                        !empty($request->preferred_locations_json) &&
                        !empty($request->it_oc_status) &&
                        !empty($request->exposure_type) &&
                        !empty($request->core_department) &&
                        (
                            $resumePath ||
                            !empty($existingProfile->resume_path ?? null)
                        ) &&
                        (
                            $marksheetPath ||
                            !empty($existingProfile->marksheet_path ?? null)
                        );
                    $isProfileComplete =
                        $isProfileComplete &&
                        $confirmFieldsComplete;
                }
            } elseif (
                in_array(
                    $request->looking_for,
                    ['semi-qualified', 'qualified']
                )
            ) {
                $isProfileComplete =
                    !empty($request->srn) &&
                    !empty($request->city) &&
                    !empty($request->gender) &&
                    !empty($request->experience_years) &&
                    !empty($request->current_ctc) &&
                    !empty($request->expected_ctc) &&
                    (
                        $resumePath ||
                        !empty($existingProfile->resume_path ?? null)
                    );
            } elseif ($request->looking_for === 'creator') {
                $isProfileComplete = !empty($request->city);
            }
            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'profile_completed' => $isProfileComplete ? 1 : 0,
                    'updated_at' => now()
                ]);
            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Profile updated successfully',
                'profile_completed' => $isProfileComplete ? 1 : 0,
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
}
