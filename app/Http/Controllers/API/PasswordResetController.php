<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Notifications\EmailNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public function __construct(
        private EmailNotificationService $emailService
    ) {}

    /**
     * POST /auth/forgot-password
     *
     * Accepts an email address, issues a hashed reset token, and dispatches
     * the reset email.  Always returns a generic success message regardless
     * of whether the account exists to prevent email enumeration.
     */
    public function forgotPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => false,
                    'message' => $validator->errors()->first(),
                ], 422);
            }

            // Always return the same message regardless of account existence.
            $successResponse = response()->json([
                'status'  => true,
                'message' => 'If an account exists with this email address, a password reset link has been sent.',
            ]);

            $user = DB::table('users')
                ->where('email', $request->email)
                ->where('is_deleted', false)
                ->first();

            if (!$user) {
                return $successResponse;
            }

            // Cryptographically secure random token; store only its hash.
            $plainToken  = Str::random(64);
            $hashedToken = hash('sha256', $plainToken);

            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'password_reset_token'      => $hashedToken,
                    'password_reset_expires_at' => now()->addMinutes(60),
                    'updated_at'                => now(),
                ]);

            $frontendUrl = config('app.frontend_url', 'https://startyourstory.in');
            $resetUrl    = "{$frontendUrl}/reset-password?token={$plainToken}";

            $this->emailService->sendPasswordResetEmail($user->email, $resetUrl);

            return $successResponse;
        } catch (\Exception $e) {
            Log::error('Forgot Password Error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Server error',
            ], 500);
        }
    }

    /**
     * POST /auth/reset-password
     *
     * Validates the token, verifies expiry, updates the password, and
     * immediately invalidates the token so it cannot be reused.
     */
    public function resetPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'token'    => 'required|string',
                'password' => 'required|string|min:6|max:15',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => false,
                    'message' => $validator->errors()->first(),
                ], 422);
            }

            $hashedToken = hash('sha256', $request->token);

            $user = DB::table('users')
                ->where('password_reset_token', $hashedToken)
                ->where('is_deleted', false)
                ->first();

            if (!$user) {
                return response()->json([
                    'status'  => false,
                    'message' => 'This password reset link is invalid.',
                ], 422);
            }

            if (
                !$user->password_reset_expires_at ||
                now()->greaterThan($user->password_reset_expires_at)
            ) {
                // Clean up the expired token.
                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'password_reset_token'      => null,
                        'password_reset_expires_at' => null,
                    ]);

                return response()->json([
                    'status'  => false,
                    'message' => 'This password reset link has expired. Please request a new one.',
                ], 422);
            }

            // Update the password and invalidate the token in one query.
            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'password'                  => Hash::make($request->password),
                    'password_reset_token'      => null,
                    'password_reset_expires_at' => null,
                    'updated_at'                => now(),
                ]);

            return response()->json([
                'status'  => true,
                'message' => 'Your password has been reset successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error('Reset Password Error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Server error',
            ], 500);
        }
    }
}
