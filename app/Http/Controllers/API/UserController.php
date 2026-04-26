<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{


    public function registerStudent(Request $request)
    {
        DB::beginTransaction();

        try {

            $request->validate([
                'name' => 'required',
                'email' => 'required|email|unique:users,email',
                'mobile' => 'required',
                'password' => 'required|min:6|max:10',

            ]);

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
                'message' => 'Registration successful'
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('Student Registration Error: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Registration failed: Server error'
            ]);
        }
    }
}
