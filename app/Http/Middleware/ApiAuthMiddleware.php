<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiAuthMiddleware
{
    // public function handle(Request $request, Closure $next)
    // {
    //     $token = $request->bearerToken();
    //     if (!$token) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Token missing'
    //         ], 401);
    //     }
    //     $user = DB::table('users')
    //         ->where('api_token', $token)
    //         ->first();
    //     if (!$user) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Unauthorized'
    //         ], 401);
    //     }
    //     /*
    //     |--------------------------------------------------------------------------
    //     | Store Logged In User
    //     |--------------------------------------------------------------------------
    //     */
    //     $request->attributes->set('auth_user', $user);
    //     return $next($request);
    // }

    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {

            return response()->json([
                'status' => false,
                'message' => 'Token missing'
            ], 401);
        }

        $user = DB::table('users')
            ->where('api_token', $token)
            ->first();

        if (!$user) {

            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        /*
    |--------------------------------------------------------------------------
    | Token Expired
    |--------------------------------------------------------------------------
    */

        if (
            $user->token_expires_at &&
            now()->gt($user->token_expires_at)
        ) {

            /*
        |--------------------------------------------------------------------------
        | Remove Expired Token
        |--------------------------------------------------------------------------
        */

            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'api_token' => null,
                    'token_expires_at' => null,
                ]);

            return response()->json([
                'status' => false,
                'message' => 'Session expired'
            ], 401);
        }

        $request->attributes->set('auth_user', $user);

        return $next($request);
    }
}
