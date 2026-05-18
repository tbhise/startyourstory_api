<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required',
                'role' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $user = DB::table('users')
                ->where('email', $request->email)
                ->where('role', $request->role)
                ->where('is_deleted', false)
                ->first();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found'
                ], 404);
            }

            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid password'
                ], 401);
            }

            $token = base64_encode(Str::random(40));

            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'api_token' => $token,
                    'token_expires_at' => now()->addDays(7),
                    'updated_at' => now()
                ]);

            return response()
                ->json([
                    'status' => true,
                    'message' => 'Login successful',
                    'data' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'mobile' => $user->mobile,
                        'role' => $user->role,
                        'profile_completed' => $user->profile_completed,
                        'profile_image' => $user->profile_image
                            ? asset('storage/' . $user->profile_image)
                            : null,
                    ]
                ])
                ->cookie(
                    'auth_token', // cookie name
                    $token,       // cookie value
                    60 * 24 * 7, // 7 days in minutes
                    '/',         // path
                    null,        // domain
                    false,       // secure (set true on HTTPS production)
                    false,        // httpOnly
                    false,       // raw
                    'Lax'        // sameSite
                );
        } catch (\Exception $e) {
            Log::error('Login Error: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Server error'
            ], 500);
        }
    }

    public function me(Request $request)
    {
        try {

            // Log::info($request->headers->all());
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

            if (
                $user->token_expires_at &&
                now()->greaterThan($user->token_expires_at)
            ) {

                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'api_token' => null
                    ]);

                return response()->json([
                    'status' => false,
                    'message' => 'Token expired'
                ], 401);
            }

            return response()->json([
                'status' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'mobile' => $user->mobile,
                    'role' => $user->role,
                    'profile_completed' => $user->profile_completed,
                    'profile_image' => $user->profile_image
                        ? asset('storage/' . $user->profile_image)
                        : null,
                ]
            ]);
        } catch (\Exception $e) {

            Log::error('Me API Error: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Server error'
            ], 500);
        }
    }
}
