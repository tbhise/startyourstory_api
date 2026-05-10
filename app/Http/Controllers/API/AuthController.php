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
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ]);
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
                ]);
            }
            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid password'
                ]);
            }

            $token = base64_encode(Str::random(40));
            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'api_token' => $token,
                    'token_expires_at' => now()->addDays(7),
                    'updated_at' => now()
                ]);
            return response()->json([
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
                    'token' => $token,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Login Error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Server error'
            ]);
        }
    }
}
