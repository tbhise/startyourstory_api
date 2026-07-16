<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Helpers\AuthHelper;

class ErrorLogController extends Controller
{
    /**
     * Allowed values for the client-supplied `category` column. Anything else
     * is stored as "Unknown Transport Failure" so a hostile client can't
     * inject arbitrary strings into an indexed/filterable column.
     */
    private const CATEGORIES = [
        'Request Timeout', 'Upload Timeout', 'Download Timeout',
        'Server Processing Timeout', 'Reverse Proxy Timeout', 'Gateway Timeout',
        'Payload Too Large', 'Offline', 'Slow Network',
        'DNS Failure', 'SSL Failure', 'Connection Refused', 'Connection Reset',
        // 'CORS Failure' is kept for rows stored before 2026-07-16; new clients
        // report the honest 'No Response (Backend Reachable)' instead (a CORS
        // rejection cannot actually be confirmed from the browser).
        'API Unreachable', 'Reverse Proxy Failure', 'CORS Failure',
        'No Response (Backend Reachable)',
        'Backend Exception', 'Backend Error', 'Validation Error',
        'Authentication Error', 'Session Expired',
        'Browser Cancelled Request', 'Navigation Cancelled Request',
        'Application Cancelled Request', 'Upload Interrupted',
        'Render Error', 'Frontend Error', 'Unknown Transport Failure',
    ];

    /** Max stored size of the diagnostics JSON blob (chars). */
    private const DIAGNOSTICS_MAX = 20000;

    /** Keys (case-insensitive substring match) always stripped from diagnostics. */
    private const SECRET_KEYS = [
        'password', 'passwd', 'pwd', 'secret', 'token', 'authorization',
        'auth', 'api_key', 'apikey', 'bearer', 'session', 'cookie', 'otp',
    ];

    /**
     * Recursively drop secret-looking keys and oversized string values from the
     * client-supplied diagnostics array. Defense-in-depth: the frontend already
     * never collects these, but the endpoint is public so we re-sanitize here.
     */
    private static function sanitizeDiagnostics(mixed $value, int $depth = 0): mixed
    {
        if ($depth > 6) {
            return null;
        }
        if (is_array($value)) {
            $clean = [];
            $count = 0;
            foreach ($value as $k => $v) {
                if (++$count > 100) {
                    break;
                }
                if (is_string($k)) {
                    $lk = strtolower($k);
                    foreach (self::SECRET_KEYS as $secret) {
                        if (str_contains($lk, $secret)) {
                            continue 2;
                        }
                    }
                }
                $clean[$k] = self::sanitizeDiagnostics($v, $depth + 1);
            }
            return $clean;
        }
        if (is_string($value)) {
            return mb_substr($value, 0, 2000);
        }
        if (is_scalar($value) || $value === null) {
            return $value;
        }
        return null;
    }

    /** Short nullable string helper for the new columns. */
    private static function str(Request $request, string $key, int $max): ?string
    {
        $v = $request->input($key);
        return is_string($v) && $v !== '' ? mb_substr($v, 0, $max) : null;
    }

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

            // Routine-noise messages (expired-session 401s, intentional
            // impersonation 403s, audit log lines) are never persisted —
            // same skip list as backend-recorded errors. Ack with 201 so the
            // fire-and-forget client is unaffected.
            if (\App\Services\ErrorLogRecorder::shouldSkipMessage($message)) {
                return response()->json(['status' => true], 201);
            }

            $url     = mb_substr((string) $request->input('url', ''), 0, 500) ?: null;
            $stack   = $request->input('stack')
                ? mb_substr((string) $request->input('stack'), 0, 5000)
                : null;
            $status  = $request->input('status') ? (int) $request->input('status') : null;

            // Attach authenticated user info if available
            $userId   = null;
            $userRole = null;
            $user     = AuthHelper::resolveUser($request);
            if ($user) {
                $userId   = $user->id;
                $userRole = $user->role;
            }

            // ── Diagnostics fields (2026-07-13) — all optional, all capped ──
            // The body carries the ID of the FAILED request (what we correlate
            // on) — NOT this log-submission request's own middleware-set ID.
            $requestId = self::str($request, 'request_id', 64);
            if ($requestId && ! preg_match('/^[A-Za-z0-9_-]{8,64}$/', $requestId)) {
                $requestId = null;
            }

            $category = $request->input('category');
            $category = in_array($category, self::CATEGORIES, true)
                ? $category
                : ($category ? 'Unknown Transport Failure' : null);

            $duration = $request->input('duration_ms');
            $duration = is_numeric($duration) ? min((int) $duration, 4294967295) : null;

            $diagnostics = $request->input('diagnostics');
            if (is_array($diagnostics)) {
                $diagnostics = json_encode(
                    self::sanitizeDiagnostics($diagnostics),
                    JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
                );
                if ($diagnostics === false || strlen($diagnostics) > self::DIAGNOSTICS_MAX) {
                    $diagnostics = $diagnostics === false
                        ? null
                        : json_encode(['truncated' => true, 'raw' => mb_substr($diagnostics, 0, self::DIAGNOSTICS_MAX)]);
                }
            } else {
                $diagnostics = null;
            }

            DB::table('error_logs')->insert([
                'source'     => $source,
                'request_id' => is_string($requestId) ? mb_substr($requestId, 0, 64) : null,
                'category'   => $category,
                'error_code' => self::str($request, 'error_code', 40),
                'method'     => self::str($request, 'method', 10),
                'endpoint'   => self::str($request, 'endpoint', 255),
                'page'       => self::str($request, 'page', 255),
                'duration_ms'=> $duration,
                'browser'    => self::str($request, 'browser', 40),
                'os'         => self::str($request, 'os', 40),
                'device'     => self::str($request, 'device', 20),
                'diagnostics'=> $diagnostics,
                'message'    => $message,
                // Frontend rows: the submitted message IS the raw error — mirror it
                // into error_summary (≤1000) so the dashboard's raw view is uniform.
                'error_summary' => mb_substr($message, 0, 1000),
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
                      ->orWhere('error_summary', 'like', "%{$search}%")
                      ->orWhere('url', 'like', "%{$search}%")
                      ->orWhere('endpoint', 'like', "%{$search}%")
                      ->orWhere('request_id', $search);
                });
            }

            // ── Diagnostics filters (2026-07-13) — all optional ──
            // `category` accepts either an exact category or one of the grouped
            // buckets the admin UI exposes (network / timeout / upload / …).
            $category = trim((string) $request->get('category', ''));
            if ($category !== '') {
                $groups = self::categoryGroups();
                if (isset($groups[$category])) {
                    $query->whereIn('category', $groups[$category]);
                } else {
                    $query->where('category', $category);
                }
            }
            foreach (['browser', 'device', 'method'] as $col) {
                $v = trim((string) $request->get($col, ''));
                if ($v !== '') {
                    $query->where($col, $v);
                }
            }
            $endpoint = trim((string) $request->get('endpoint', ''));
            if ($endpoint !== '') {
                $query->where('endpoint', 'like', "%{$endpoint}%");
            }
            $page_route = trim((string) $request->get('page_route', ''));
            if ($page_route !== '') {
                $query->where('page', 'like', "%{$page_route}%");
            }
            if (is_numeric($request->get('user_id'))) {
                $query->where('user_id', (int) $request->get('user_id'));
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

            // Per-bucket counts for the admin summary cards (one grouped query).
            $byCategory = DB::table('error_logs')
                ->whereNotNull('category')
                ->selectRaw('category, COUNT(*) as c')
                ->groupBy('category')
                ->pluck('c', 'category');
            $bucketCount = function (array $categories) use ($byCategory): int {
                $sum = 0;
                foreach ($categories as $cat) {
                    $sum += (int) ($byCategory[$cat] ?? 0);
                }
                return $sum;
            };
            $groups = self::categoryGroups();

            return response()->json([
                'status' => true,
                'data'   => [
                    'total'     => $total,
                    'api'       => $api,
                    'frontend'  => $frontend,
                    'today'     => $today,
                    'errors5xx' => $errors5xx,
                    'network'    => $bucketCount($groups['network']),
                    'timeout'    => $bucketCount($groups['timeout']),
                    'upload'     => $bucketCount($groups['upload']),
                    'backend'    => $bucketCount($groups['backend']),
                    'validation' => $bucketCount($groups['validation']),
                    'auth'       => $bucketCount($groups['auth']),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('ErrorLogController@stats: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * Grouped category buckets shared by stats(), index() filtering and the
     * admin UI's filter tabs.
     *
     * @return array<string, string[]>
     */
    private static function categoryGroups(): array
    {
        return [
            'network' => [
                'Offline', 'Slow Network', 'DNS Failure', 'SSL Failure',
                'Connection Refused', 'Connection Reset', 'API Unreachable',
                'Reverse Proxy Failure', 'CORS Failure',
                'No Response (Backend Reachable)', 'Unknown Transport Failure',
            ],
            'timeout' => [
                'Request Timeout', 'Upload Timeout', 'Download Timeout',
                'Server Processing Timeout', 'Reverse Proxy Timeout', 'Gateway Timeout',
            ],
            'upload' => ['Payload Too Large', 'Upload Timeout', 'Upload Interrupted'],
            'backend' => ['Backend Exception', 'Backend Error'],
            'validation' => ['Validation Error'],
            'auth' => ['Authentication Error', 'Session Expired'],
            'cancelled' => [
                'Browser Cancelled Request', 'Navigation Cancelled Request',
                'Application Cancelled Request',
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | GET /admin/error-logs/analytics
    | Aggregations for the dashboard: top categories/endpoints/browsers/devices/
    | routes, most affected users, and a 14-day trend. Optional ?days=N window
    | (default 14, max 90) applies to everything.
    |--------------------------------------------------------------------------
    */
    public function analytics(Request $request)
    {
        try {
            $days  = min(90, max(1, (int) $request->get('days', 14)));
            $since = now()->subDays($days)->startOfDay();

            $top = function (string $column) use ($since) {
                return DB::table('error_logs')
                    ->where('created_at', '>=', $since)
                    ->whereNotNull($column)
                    ->where($column, '!=', '')
                    ->selectRaw("{$column} as label, COUNT(*) as count")
                    ->groupBy($column)
                    ->orderByDesc('count')
                    ->limit(8)
                    ->get();
            };

            $users = DB::table('error_logs')
                ->where('created_at', '>=', $since)
                ->whereNotNull('user_id')
                ->selectRaw('user_id, MAX(user_role) as user_role, COUNT(*) as count')
                ->groupBy('user_id')
                ->orderByDesc('count')
                ->limit(8)
                ->get();

            $trend = DB::table('error_logs')
                ->where('created_at', '>=', $since)
                ->selectRaw('DATE(created_at) as day, COUNT(*) as count')
                ->groupBy('day')
                ->orderBy('day')
                ->get();

            return response()->json([
                'status' => true,
                'data'   => [
                    'days'       => $days,
                    'categories' => $top('category'),
                    'endpoints'  => $top('endpoint'),
                    'browsers'   => $top('browser'),
                    'devices'    => $top('device'),
                    'routes'     => $top('page'),
                    'users'      => $users,
                    'trend'      => $trend,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('ErrorLogController@analytics: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /admin/error-logs/correlate?request_id=...
    |
    | Request-ID correlation with NO inference: answers exactly two questions
    | from hard evidence and nothing more.
    |
    |  - backend_request_found: did this request ID reach Laravel? YES when the
    |    proof-of-arrival cache marker written by RequestIdMiddleware exists
    |    (kept ARRIVAL_TTL_HOURS), or when a backend error row carries the ID
    |    (an error row can only exist if the request arrived). NOT FOUND is
    |    reported as-is — within the retention window it means the request
    |    never reached Laravel; beyond it, it is inconclusive.
    |  - backend_error_exists: is there an error_logs row with source='api'
    |    for this request ID (written by ErrorLogRecorder / the MessageLogged
    |    listener)?
    |
    | Deliberately does NOT claim which middleware/controller/validation stage
    | executed — that is not knowable from the stored evidence.
    |--------------------------------------------------------------------------
    */
    public function correlate(Request $request)
    {
        try {
            $requestId = trim((string) $request->get('request_id', ''));
            if (! preg_match('/^[A-Za-z0-9_-]{8,64}$/', $requestId)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Invalid request id',
                ], 422);
            }

            // Proof-of-arrival marker (best-effort read — cache may be down).
            $seenAt = null;
            try {
                $seenAt = \Illuminate\Support\Facades\Cache::get(
                    \App\Http\Middleware\RequestIdMiddleware::arrivalKey($requestId)
                );
            } catch (\Throwable) {
                // ignore — treated as no marker
            }
            $seenAt = is_string($seenAt) ? $seenAt : null;

            $backendErrors = DB::table('error_logs')
                ->where('request_id', $requestId)
                ->where('source', 'api')
                ->orderBy('created_at')
                ->limit(10)
                ->get(['id', 'status', 'category', 'message', 'endpoint', 'created_at']);

            return response()->json([
                'status' => true,
                'data'   => [
                    'request_id'             => $requestId,
                    'backend_request_found'  => $seenAt !== null || $backendErrors->isNotEmpty(),
                    'backend_seen_at'        => $seenAt,
                    'backend_error_exists'   => $backendErrors->isNotEmpty(),
                    'backend_errors'         => $backendErrors,
                    'marker_retention_hours' => \App\Http\Middleware\RequestIdMiddleware::ARRIVAL_TTL_HOURS,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('ErrorLogController@correlate: ' . $e->getMessage());
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
