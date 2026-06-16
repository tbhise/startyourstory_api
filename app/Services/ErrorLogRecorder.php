<?php

namespace App\Services;

use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Records a SHORT, SAFE summary of a backend exception into the `error_logs`
 * table so admins get a quick at-a-glance view in the dashboard.
 *
 * This is intentionally a thin companion to Laravel's standard logging — the
 * COMPLETE exception (message + stack trace) is still written to
 * storage/logs/laravel.log by the framework's default reporter. This recorder
 * NEVER stores stack traces, SQL bindings, passwords, tokens, secrets or
 * session identifiers, and never throws (a logging failure must not break the
 * request it is reporting on).
 */
class ErrorLogRecorder
{
    /** Exception classes that are routine noise and should not be persisted. */
    private const SKIP = [
        \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
        \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException::class,
        \Illuminate\Validation\ValidationException::class,
        \Illuminate\Auth\AuthenticationException::class,
    ];

    public static function record(Throwable $e, ?Request $request = null): void
    {
        try {
            foreach (self::SKIP as $skip) {
                if ($e instanceof $skip) {
                    return;
                }
            }

            $request ??= request();

            $safe    = self::safeMessage($e);
            $status  = self::statusFor($e);
            $url     = $request ? mb_substr($request->path(), 0, 500) : null;

            [$userId, $userRole] = self::resolveUser($request);

            DB::table('error_logs')->insert([
                'source'        => 'api',
                'message'       => mb_substr($safe, 0, 1000),
                'error_summary' => mb_substr($safe, 0, 100),
                'status'        => $status,
                'url'           => $url ? '/' . ltrim($url, '/') : null,
                // Stack traces are deliberately NEVER stored in the DB.
                'stack'         => null,
                'user_id'       => $userId,
                'user_role'     => $userRole,
                'user_agent'    => $request ? mb_substr((string) $request->userAgent(), 0, 500) : null,
                'ip'            => $request?->ip(),
                'created_at'    => now(),
            ]);
        } catch (Throwable $inner) {
            // Recording must never break the host request. Fall back to the file log.
            Log::warning('ErrorLogRecorder failed: ' . $inner->getMessage());
        }
    }

    /**
     * Produce a safe, single-line message free of SQL/bindings and secrets.
     */
    private static function safeMessage(Throwable $e): string
    {
        $msg = $e->getMessage();

        // For DB errors, keep only the SQLSTATE/driver portion and drop the
        // " (Connection: ..., SQL: ...)" tail — that is where bindings/PII live.
        if ($e instanceof QueryException) {
            $msg = preg_replace('/\s*\(Connection:.*$/is', '', $msg) ?? $msg;
        }
        $msg = preg_replace('/\s*\(SQL:.*$/is', '', $msg) ?? $msg;
        $msg = preg_replace('/\s*\(Connection:.*$/is', '', $msg) ?? $msg;

        // Redact anything that looks like a secret (key=value or "key": "value").
        $msg = preg_replace(
            '/\b(password|passwd|pwd|secret|token|authorization|auth|api[_-]?key|bearer|session|cookie|otp)\b\s*["\']?\s*[:=]\s*["\']?[^\s,"\'&]+/i',
            '$1=[redacted]',
            $msg
        ) ?? $msg;

        // Collapse all whitespace/newlines into single spaces.
        $msg = trim((string) preg_replace('/\s+/', ' ', $msg));

        if ($msg === '') {
            $msg = class_basename($e);
        }

        return $msg;
    }

    /**
     * Best-effort HTTP status for the exception.
     */
    private static function statusFor(Throwable $e): int
    {
        if ($e instanceof HttpExceptionInterface) {
            return $e->getStatusCode();
        }
        return 500;
    }

    /**
     * Resolve the authenticated user from the auth_token cookie, best-effort.
     * Mirrors ErrorLogController@store so backend rows carry the same context.
     *
     * @return array{0: int|null, 1: string|null}
     */
    private static function resolveUser(?Request $request): array
    {
        try {
            $token = $request?->cookie('auth_token');
            if (! $token) {
                return [null, null];
            }
            $user = DB::table('users')
                ->where('api_token', $token)
                ->where('is_deleted', false)
                ->select('id', 'role')
                ->first();

            return $user ? [$user->id, $user->role] : [null, null];
        } catch (Throwable) {
            return [null, null];
        }
    }
}
