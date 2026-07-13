<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use App\Helpers\AuthHelper;
use Throwable;

/**
 * Records a SHORT, SAFE summary of a backend exception into the `error_logs`
 * table so admins get a quick at-a-glance view in the dashboard.
 *
 * This is intentionally a thin companion to Laravel's standard logging — the
 * COMPLETE exception (message + stack trace) is still written to
 * storage/logs/laravel.log by the framework's default reporter.
 *
 * Columns stored per row:
 *   - `message` / `error_summary`: the sanitized exception message — the `(SQL:
 *     ...)` / `(Connection: ...)` tail is stripped and passwords/tokens/secrets
 *     are redacted, so NO SQL query, bindings or credentials are persisted (e.g.
 *     "SQLSTATE[22001]: Data too long for column 'exposure_type'"). Capped at
 *     1000 chars to match the VARCHAR(1000) column.
 *   - `stack`: the full (secret-redacted) stack trace, capped to the TEXT column
 *     — kept because it is useful for debugging.
 *
 * The COMPLETE error (incl. the SQL) still goes to storage/logs/laravel.log via
 * the framework's default reporter. This recorder never stores SQL bindings,
 * passwords, tokens, secrets or session identifiers in message/error_summary,
 * and never throws (a logging failure must not break the host request).
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

    /**
     * Exact messages (case-insensitive, trimmed) that are routine noise and
     * must never be persisted to error_logs — regardless of whether they
     * arrive as a backend exception/log line or as a frontend-reported API
     * error (ErrorLogController@store consults this list too). These are
     * expected auth/UX responses, not errors:
     *  - "Invalid token" / "Unauthorized": every expired-cookie request.
     *  - "Send Verification Link API called": an audit Log::alert(), not a failure.
     *  - impersonation read-only rejection: intentional 403 by design.
     * Kept exact-match so a real error that merely CONTAINS one of these
     * words (e.g. "Unauthorized webhook signature ...") is still recorded.
     */
    private const MESSAGE_SKIP = [
        'invalid token',
        'unauthorized',
        'send verification link api called',
        'this action is disabled during admin impersonation (read-only mode).',
    ];

    /** True when the message is on the routine-noise skip list. */
    public static function shouldSkipMessage(string $message): bool
    {
        return in_array(mb_strtolower(trim($message)), self::MESSAGE_SKIP, true);
    }

    /** Log levels that represent a genuine error worth surfacing to admins. */
    private const LOG_ERROR_LEVELS = ['error', 'critical', 'alert', 'emergency'];

    /** Re-entrancy guard: never record a row while we are already recording one. */
    private static bool $writing = false;

    /** Max chars stored for message / error_summary — matches the VARCHAR(1000) column. */
    private const SUMMARY_MAX = 1000;
    /** Max chars stored for the stack trace (TEXT column, secret-redacted). */
    private const STACK_MAX = 15000;

    public static function record(Throwable $e, ?Request $request = null): void
    {
        foreach (self::SKIP as $skip) {
            if ($e instanceof $skip) {
                return;
            }
        }

        $summary = self::safeMessage($e);
        if (self::shouldSkipMessage($summary)) {
            return;
        }

        self::writeRow(
            $summary,
            self::statusFor($e),
            $request,
            self::stackTrace($e),
        );
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
        if ($safe === '' || self::shouldSkipMessage($safe)) {
            return;
        }

        // No exception object here, so there is no stack trace to store.
        self::writeRow($safe, 500);
    }

    /**
     * Insert one row into `error_logs`. NON-THROWING and re-entrant-safe.
     *
     * @param string      $summary Sanitized one-liner (SQL/Connection tail stripped,
     *                             secrets redacted) → both `message` and `error_summary`.
     * @param string|null $stack   Full (secret-redacted) stack trace → `stack`.
     */
    private static function writeRow(string $summary, int $status, ?Request $request = null, ?string $stack = null): void
    {
        if (self::$writing) {
            return;
        }
        self::$writing = true;

        try {
            $request ??= request();
            $url = $request ? mb_substr($request->path(), 0, 500) : null;

            [$userId, $userRole] = self::resolveUser($request);

            // Capped to the VARCHAR(1000) column — never stores SQL/bindings (those
            // were stripped by sanitize()); full detail stays in laravel.log.
            $summary = mb_substr($summary, 0, self::SUMMARY_MAX);

            // Correlation ID set by RequestIdMiddleware — lets admins match this
            // row to the frontend error row and the laravel.log lines.
            $requestId = $request?->attributes->get('request_id');

            DB::table('error_logs')->insert([
                'source'        => 'api',
                'request_id'    => is_string($requestId) ? mb_substr($requestId, 0, 64) : null,
                'category'      => self::categoryFor($status),
                'method'        => $request ? mb_substr($request->method(), 0, 10) : null,
                'endpoint'      => $url ? '/' . ltrim($url, '/') : null,
                'message'       => $summary,
                'error_summary' => $summary,
                'status'        => $status,
                'url'           => $url ? '/' . ltrim($url, '/') : null,
                // Full stack trace, secret-redacted and capped (TEXT column).
                'stack'         => $stack !== null && $stack !== '' ? mb_substr($stack, 0, self::STACK_MAX) : null,
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
     * Produce a safe, single-line message free of SQL/bindings and secrets —
     * stored in both `message` and `error_summary`.
     */
    private static function safeMessage(Throwable $e): string
    {
        $msg = self::sanitize($e->getMessage());

        return $msg !== '' ? $msg : class_basename($e);
    }

    /**
     * Build the full stack trace for the `stack` column. Prepends an "ExceptionClass
     * @ file:line" header, then the framework trace. Secrets are redacted (a scalar
     * string argument in a frame could otherwise leak a credential) but the trace is
     * otherwise kept complete; writeRow() caps the length to fit the TEXT column.
     */
    private static function stackTrace(Throwable $e): string
    {
        $header = sprintf('%s @ %s:%d', get_class($e), $e->getFile(), $e->getLine());

        // Keep newlines so the trace stays readable; only redact secret-looking values.
        $trace = self::redactSecretsKeepLines($header . "\n" . $e->getTraceAsString());

        return trim($trace);
    }

    /**
     * Strip SQL/bindings AND redact secrets — the fully sanitized one-liner.
     */
    private static function sanitize(string $msg): string
    {
        // Drop the " (Connection: ..., SQL: ...)" tail of DB errors — that is
        // where bindings/PII live. Applied unconditionally (no-op when absent).
        $msg = preg_replace('/\s*\(SQL:.*$/is', '', $msg) ?? $msg;
        $msg = preg_replace('/\s*\(Connection:.*$/is', '', $msg) ?? $msg;

        return self::redactSecrets($msg);
    }

    /**
     * Redact secret-looking key=value / "key": "value" pairs and collapse all
     * whitespace into single spaces. Does NOT touch SQL — used for both the raw
     * and the fully-sanitized message.
     */
    private static function redactSecrets(string $msg): string
    {
        $msg = preg_replace(
            '/\b(password|passwd|pwd|secret|token|authorization|auth|api[_-]?key|bearer|session|cookie|otp)\b\s*["\']?\s*[:=]\s*["\']?[^\s,"\'&]+/i',
            '$1=[redacted]',
            $msg
        ) ?? $msg;

        // Collapse all whitespace/newlines into single spaces.
        return trim((string) preg_replace('/\s+/', ' ', $msg));
    }

    /**
     * Like redactSecrets() but preserves newlines (for multi-line stack traces).
     * Only redacts secret-looking key=value pairs; line structure is kept intact.
     */
    private static function redactSecretsKeepLines(string $msg): string
    {
        $msg = preg_replace(
            '/\b(password|passwd|pwd|secret|token|authorization|auth|api[_-]?key|bearer|session|cookie|otp)\b\s*["\']?\s*[:=]\s*["\']?[^\s,"\'&]+/i',
            '$1=[redacted]',
            $msg
        ) ?? $msg;

        return $msg;
    }

    /**
     * Error category for a backend-recorded row, from the HTTP status. Uses the
     * same vocabulary as the frontend classifier so the admin category filter
     * covers both sources. (ValidationException/AuthenticationException are on
     * the SKIP list, so 422/401 mostly appear via recordLog'd controller lines.)
     */
    private static function categoryFor(int $status): string
    {
        return match (true) {
            $status === 413                   => 'Payload Too Large',
            $status === 422                   => 'Validation Error',
            $status === 401 || $status === 403 => 'Authentication Error',
            $status === 502 || $status === 504 => 'Reverse Proxy Failure',
            $status >= 500                    => 'Backend Exception',
            default                           => 'Backend Error',
        };
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
            if (! $request) {
                return [null, null];
            }
            $user = AuthHelper::resolveUser($request);

            return $user ? [$user->id, $user->role] : [null, null];
        } catch (Throwable) {
            return [null, null];
        }
    }
}
