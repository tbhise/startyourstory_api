<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    public function login(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            // Log::info('Admin Login Attempt', ['email' => $request->email , 'password' => $request->password]);
            $admin = DB::table('admin_users')
                ->where('email', $request->email)
                ->first();
            // Log::info('Admin Login Query Result', ['admin' => $admin]);
            if (!$admin) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }

            if (!Hash::check($request->password, $admin->password)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }

            if (!$admin->is_active) {
                return response()->json([
                    'status' => false,
                    'message' => 'Account is inactive'
                ], 403);
            }

            $token = Str::random(80);
            Log::info($admin->id);
            DB::table('admin_users')
                ->where('id', $admin->id)
                ->update([
                    'api_token' => $token,
                    'updated_at' => now(),
                ]);

            return response()->json([
                'status' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => [
                        'id' => $admin->id,
                        'name' => $admin->name,
                        'email' => $admin->email,
                        'role' => $admin->role,
                    ]
                ]
            ])->cookie(
                'admin_token',
                $token,
                60 * 24 * 30,
                '/',
                null,
                false,
                true
            );
        } catch (\Exception $e) {
            Log::error(
                'Admin Login Error',
                [
                    'message' => $e->getMessage(),
                    'line' => $e->getLine(),
                ]
            );
            return response()->json([
                'status' => false,
                'message' => 'Unexpected server error'
            ], 500);
        }
    }

    public function me(Request $request)
    {
        try {

            $token =
                $request->cookie('admin_token');

            if (!$token) {

                return response()->json([

                    'status' => false,

                    'message' => 'Unauthorized'
                ], 401);
            }

            $admin = DB::table('admin_users')

                ->where(
                    'api_token',
                    $token
                )

                ->first();

            if (!$admin) {

                return response()->json([

                    'status' => false,

                    'message' => 'Invalid token'
                ], 401);
            }

            return response()->json([

                'status' => true,

                'data' => [

                    'user' => [

                        'id' =>
                        $admin->id,

                        'name' =>
                        $admin->name,

                        'email' =>
                        $admin->email,

                        'role' =>
                        $admin->role,
                    ]
                ]
            ]);
        } catch (\Exception $e) {

            return response()->json([

                'status' => false,

                'message' =>
                'Unexpected server error'
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {

            $token =
                $request->cookie('admin_token');

            DB::table('admin_users')

                ->where(
                    'api_token',
                    $token
                )

                ->update([

                    'api_token' => null
                ]);

            return response()->json([

                'status' => true,

                'message' =>
                'Logout successful'

            ])->cookie(

                'admin_token',

                '',

                -1
            );
        } catch (\Exception $e) {

            return response()->json([

                'status' => false
            ]);
        }
    }
}
