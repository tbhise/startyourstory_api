<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * End-to-end request correlation (2026-07-13).
 *
 * Every API request gets a request ID that follows it from the browser to
 * Laravel and back:
 *
 *   1. The frontend axios interceptor generates a UUID and sends it as the
 *      X-Request-ID header. If the header is missing/invalid (curl, webhooks,
 *      old clients) one is generated here so the ID always exists.
 *   2. The ID is pushed into Log::withContext() so EVERY laravel.log line for
 *      this request carries {"request_id": "..."} — grep the ID from an admin
 *      error-log row to find the matching backend log lines instantly.
 *   3. The ID is stored on request attributes for ErrorLogRecorder /
 *      controllers ($request->attributes->get('request_id')).
 *   4. The response echoes X-Request-ID plus X-Response-Time (ms measured
 *      inside PHP) so the frontend can log server processing time and
 *      distinguish slow backend from slow network. Both headers are exposed
 *      to the browser via config/cors.php `exposed_headers`.
 *
 * Nginx correlation (server config, not this repo): add `$http_x_request_id`
 * to the access-log format, e.g.
 *   log_format main '... rid=$http_x_request_id';
 */
class RequestIdMiddleware
{
    /** Client-supplied IDs must look like a UUID/token — anything else is replaced. */
    private const VALID_ID = '/^[A-Za-z0-9_-]{8,64}$/';

    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);

        $requestId = (string) $request->header('X-Request-ID', '');
        if (! preg_match(self::VALID_ID, $requestId)) {
            $requestId = (string) Str::uuid();
        }

        $request->attributes->set('request_id', $requestId);
        Log::withContext(['request_id' => $requestId]);

        $response = $next($request);

        $durationMs = (int) round((microtime(true) - $start) * 1000);
        $response->headers->set('X-Request-ID', $requestId);
        $response->headers->set('X-Response-Time', (string) $durationMs);

        return $response;
    }
}
