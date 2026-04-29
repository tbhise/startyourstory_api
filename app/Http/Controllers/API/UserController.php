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
        try {

        return response()->json([
            'status' => true,
            'message' => 'Profile updated successfully',
            'data' => $request->all()
        ]);

        dd($request->all());

        } catch (\Exception $e) {
            Log::error('Profile Update Error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Profile update failed: Server error'
            ]);
        }
    }


}
