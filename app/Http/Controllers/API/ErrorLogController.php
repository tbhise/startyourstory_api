<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ErrorLogController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | POST /error-logs
    | Called by the frontend whenever an API error or unhandled JS error occurs.
    | No auth required — errors can happen before/during login.
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        try {
            $source  = in_array($request->input('source'), ['api', 'frontend'], true)
                ? $request->input('source')
                : 'frontend';

            $message = mb_substr((string) $request->input('message', 'Unknown error'), 0, 2000);
            $url     = mb_substr((string) $request->input('url', ''), 0, 500) ?: null;
            $stack   = $request->input('stack')
                ? mb_substr((string) $request->input('stack'), 0, 5000)
                : null;
            $status  = $request->input('status') ? (int) $request->input('status') : null;

            // Attach authenticated user info if available
            $userId   = null;
            $userRole = null;
            $token    = $request->cookie('auth_token');
            if ($token) {
                $user = DB::table('users')
                    ->where('api_token', $token)
                    ->where('is_deleted', false)
                    ->select('id', 'role')
                    ->first();
                if ($user) {
                    $userId   = $user->id;
                    $userRole = $user->role;
                }
            }

            DB::table('error_logs')->insert([
                'source'     => $source,
                'message'    => $message,
                'status'     => $status,
                'url'        => $url,
                'stack'      => $stack,
                'user_id'    => $userId,
                'user_role'  => $userRole,
                'user_agent' => mb_substr($request->userAgent() ?? '', 0, 500),
                'ip'         => $request->ip(),
                'created_at' => now(),
            ]);

            return response()->json(['status' => true], 201);
        } catch (\Exception $e) {
            // Never let error-logging itself break anything
            Log::warning('ErrorLogController@store failed: ' . $e->getMessage());
            return response()->json(['status' => true], 201);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /admin/error-logs
    | Query: source, status, page, search
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        try {
            $page    = max(1, (int) $request->get('page', 1));
            $perPage = 50;
            $source  = $request->get('source', '');
            $search  = trim($request->get('search', ''));

            $query = DB::table('error_logs');

            if ($source && in_array($source, ['api', 'frontend'])) {
                $query->where('source', $source);
            }
            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('message', 'like', "%{$search}%")
                      ->orWhere('url', 'like', "%{$search}%");
                });
            }

            $total = (clone $query)->count();
            $rows  = $query
                ->orderByDesc('created_at')
                ->offset(($page - 1) * $perPage)
                ->limit($perPage)
                ->get();

            return response()->json([
                'status'   => true,
                'message'  => 'Error logs fetched',
                'data'     => [
                    'errors'   => $rows,
                    'total'    => $total,
                    'page'     => $page,
                    'has_more' => ($page * $perPage) < $total,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('ErrorLogController@index: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /admin/error-logs/stats
    |--------------------------------------------------------------------------
    */
    public function stats()
    {
        try {
            $total    = DB::table('error_logs')->count();
            $api      = DB::table('error_logs')->where('source', 'api')->count();
            $frontend = DB::table('error_logs')->where('source', 'frontend')->count();
            $today    = DB::table('error_logs')->whereDate('created_at', today())->count();
            $errors5xx = DB::table('error_logs')->where('status', '>=', 500)->count();

            return response()->json([
                'status' => true,
                'data'   => [
                    'total'     => $total,
                    'api'       => $api,
                    'frontend'  => $frontend,
                    'today'     => $today,
                    'errors5xx' => $errors5xx,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('ErrorLogController@stats: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE /admin/error-logs
    | Clear all logs (or by source)
    |--------------------------------------------------------------------------
    */
    public function destroy(Request $request)
    {
        try {
            $source = $request->get('source', '');
            $query  = DB::table('error_logs');
            if ($source && in_array($source, ['api', 'frontend'])) {
                $query->where('source', $source);
            }
            $deleted = $query->delete();
            return response()->json(['status' => true, 'message' => "{$deleted} logs deleted"]);
        } catch (\Exception $e) {
            Log::error('ErrorLogController@destroy: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }
}
