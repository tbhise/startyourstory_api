<?php

namespace App\Services;

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

    /** Log levels that represent a genuine error worth surfacing to admins. */
    private const LOG_ERROR_LEVELS = ['error', 'critical', 'alert', 'emergency'];

    /** Re-entrancy guard: never record a row while we are already recording one. */
    private static bool $writing = false;

    public static function record(Throwable $e, ?Request $request = null): void
    {
        foreach (self::SKIP as $skip) {
            if ($e instanceof $skip) {
                return;
            }
        }

        self::writeRow(self::safeMessage($e), self::statusFor($e), $request);
    }

    /**
     * Record an error-level (or above) application log line into `error_logs`.
     *
     * Captures the many controller catch-blocks that Log::error() a failure and
     * return 'Server error' WITHOUT re-throwing — those never reach the
     * report() hook in bootstrap/app.php. Entries that already carry the
     * exception in their context are intentionally skipped here so an UNCAUGHT
     * exception (which the framework logs with `['exception' => $e]`, in
     * addition to the report() hook recording it) is never stored twice.
     */
    public static function recordLog(string $level, string $message, array $context = []): void
    {
        if (! in_array(strtolower($level), self::LOG_ERROR_LEVELS, true)) {
            return;
        }
        // Uncaught exceptions are handled by the report() hook — don't duplicate.
        if (isset($context['exception']) && $context['exception'] instanceof Throwable) {
            return;
        }

        $safe = self::sanitize($message);
        if ($safe === '') {
            return;
        }

        self::writeRow($safe, 500);
    }

    /**
     * Insert one sanitized row into `error_logs`. NON-THROWING and re-entrant-safe.
     */
    private static function writeRow(string $safe, int $status, ?Request $request = null): void
    {
        if (self::$writing) {
            return;
        }
        self::$writing = true;

        try {
            $request ??= request();
            $url = $request ? mb_substr($request->path(), 0, 500) : null;

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
        } finally {
            self::$writing = false;
        }
    }

    /**
     * Produce a safe, single-line message free of SQL/bindings and secrets.
     */
    private static function safeMessage(Throwable $e): string
    {
        $msg = self::sanitize($e->getMessage());

        return $msg !== '' ? $msg : class_basename($e);
    }

    /**
     * Strip SQL/bindings and redact secrets from an arbitrary message string,
     * collapsing it to a single safe line.
     */
    private static function sanitize(string $msg): string
    {
        // Drop the " (Connection: ..., SQL: ...)" tail of DB errors — that is
        // where bindings/PII live. Applied unconditionally (no-op when absent).
        $msg = preg_replace('/\s*\(SQL:.*$/is', '', $msg) ?? $msg;
        $msg = preg_replace('/\s*\(Connection:.*$/is', '', $msg) ?? $msg;

        // Redact anything that looks like a secret (key=value or "key": "value").
        $msg = preg_replace(
            '/\b(password|passwd|pwd|secret|token|authorization|auth|api[_-]?key|bearer|session|cookie|otp)\b\s*["\']?\s*[:=]\s*["\']?[^\s,"\'&]+/i',
            '$1=[redacted]',
            $msg
        ) ?? $msg;

        // Collapse all whitespace/newlines into single spaces.
        return trim((string) preg_replace('/\s+/', ' ', $msg));
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
