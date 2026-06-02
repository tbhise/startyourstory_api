<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FirmVerifiedMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->attributes->get('auth_user');

        if (!$user || $user->role !== 'firm') {
            return $next($request);
        }

        $firmProfile = DB::table('firm_profiles')
            ->where('user_id', $user->id)
            ->select('verification_status')
            ->first();

        $status = $firmProfile->verification_status ?? 'pending';

        if ($status !== 'approved') {
            return response()->json([
                'status' => false,
                'code' => 'FIRM_PENDING_VERIFICATION',
                'verification_status' => $status,
                'message' => $status === 'rejected'
                    ? 'Your firm account has been rejected. Please contact support.'
                    : 'Your firm account is pending manual verification. You will be notified by email once approved.',
            ], 403);
        }

        return $next($request);
    }
}
