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
            // create user
            $userId = DB::table('users')->insertGetId([
                'name' => $request->name,
                'email' => $request->email,
                'mobile' => $request->mobile,
                'password' => bcrypt($request->password),
                'role' => 'student',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            // create profile
            DB::table('student_profiless')->insert([
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
        // Log::info($request->all());
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'looking_for' => 'nullable|string',
                'srn' => 'nullable|string',
                'address' => 'nullable|string',
                'gender' => 'nullable|string',
                'passing_month' => 'nullable|string',
                'ca_status' => 'nullable|string',
                'articleship_status' => 'nullable|string',
                'preferred_location' => 'nullable|string',
                'it_oc_status' => 'nullable|string',
                'exposure_type' => 'nullable|string',
                'core_department' => 'nullable|string',
                'attempts' => 'nullable|string',
                'linkedin_url' => 'nullable|string',
                'portfolio_url' => 'nullable|string',
                'resume_path' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'marksheet_path' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ]);
            }
            // ===================================
            // FILE UPLOADS
            // ===================================
            $resumePath = null;
            $marksheetPath = null;
            // Resume Upload
            if ($request->hasFile('resume_path')) {
                $resume = $request->file('resume_path');
                $resumeName = time() . '_resume.' . $resume->getClientOriginalExtension();
                $resumePath = $resume->storeAs(
                    'resumes',
                    $resumeName,
                    'public'
                );
            }
            // Marksheet Upload
            if ($request->hasFile('marksheet_path')) {
                $marksheet = $request->file('marksheet_path');
                $marksheetName = time() . '_marksheet.' . $marksheet->getClientOriginalExtension();
                $marksheetPath = $marksheet->storeAs(
                    'marksheets',
                    $marksheetName,
                    'public'
                );
            }
            // ===================================
            // PREPARE DATA
            // ===================================
            $profileData = [
                'user_id' => $request->userId,
                'looking_for' => $request->looking_for,
                'srn' => $request->srn,
                'address' => $request->address,
                'gender' => $request->gender,
                'passing_month' => $request->passing_month,
                'ca_status' => $request->ca_status,
                'articleship_status' => $request->articleship_status,
                'preferred_location' => json_encode(
                    explode(',', $request->preferred_location)
                ),
                'it_oc_status' => $request->it_oc_status,
                // 'exposure_type' => $request->exposure_type,
                'exposure_type' => json_encode(
                    explode(',', $request->exposure_type)
                ),
                'core_department' => $request->core_department,
                'attempts' => $request->attempts,
                'linkedin_url' => $request->linkedin_url,
                'portfolio_url' => $request->portfolio_url,
                'updated_at' => now(),
            ];
            // Add files only if uploaded
            if ($resumePath) {
                $profileData['resume_path'] = $resumePath;
            }
            if ($marksheetPath) {
                $profileData['marksheet_path'] = $marksheetPath;
            }
            // ===================================
            // CHECK PROFILE EXISTS
            // ===================================
            $existingProfile = DB::table('student_profiles')
                ->where('user_id', $request->userId)
                ->first();
            // ===================================
            // UPDATE OR INSERT
            // ===================================
            if ($existingProfile) {
                DB::table('student_profiles')
                    ->where('user_id', $request->userId)
                    ->update($profileData);
            } else {
                $profileData['created_at'] = now();
                DB::table('student_profiles')
                    ->insert($profileData);
            }
            // ===================================
            // UPDATE USERS TABLE
            // ===================================
            $isProfileComplete =
                !empty($request->srn) &&
                !empty($request->address) &&
                !empty($request->gender) &&
                !empty($request->passing_month) &&
                !empty($request->ca_status) &&
                !empty($request->preferred_location) &&
                !empty($request->it_oc_status) &&
                !empty($request->exposure_type) &&
                !empty($request->core_department) &&
                !empty($request->attempts) &&
                (
                    $resumePath ||
                    !empty($existingProfile->resume_path ?? null)
                ) &&
                (
                    $marksheetPath ||
                    !empty($existingProfile->marksheet_path ?? null)
                );
            DB::table('users')
                ->where('id', $request->userId)
                ->update([
                    'profile_completed' => $isProfileComplete ? 1 : 0,
                    'updated_at' => now()
                ]);
            // ===================================
            // RESPONSE
            // ===================================
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
            if (!$request->userId) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized'
                ]);
            }
            $profile = DB::table('student_profiles')
                ->where('user_id', $request->userId)
                ->first();
            return response()->json([
                'status' => true,
                'data' => [
                    'user' => $request->userId,
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

    
}
