<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * CA Library student auth — fully isolated from SYS auth.
 * Own cookie (ca_auth_token) + own session table (ca_sessions, ca_library DB),
 * mirroring the ApiAuthMiddleware pattern. Applied ONLY to CA Library student
 * routes via the 'ca.student' alias.
 */
class CaStudentAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->cookie('ca_auth_token');

        if (! $token) {
            return response()->json(['status' => false, 'message' => 'Token missing'], 401);
        }

        $session = DB::connection('ca_library')->table('ca_sessions')
            ->where('token', $token)
            ->first();

        if (! $session) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        if ($session->expires_at && now()->gt($session->expires_at)) {
            DB::connection('ca_library')->table('ca_sessions')->where('token', $token)->delete();
            return response()->json(['status' => false, 'message' => 'Session expired'], 401);
        }

        $student = DB::connection('ca_library')->table('ca_students')
            ->where('id', $session->student_id)
            ->where('status', 'active')
            ->first();

        if (! $student) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $request->attributes->set('ca_student', $student);

        return $next($request);
    }
}
