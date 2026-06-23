<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Event;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Http\Request;
use App\Services\ErrorLogRecorder;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiters();
        $this->configureErrorLogCapture();
    }

    /**
     * Mirror every error-level (and above) application log into the `error_logs`
     * table so the admin Error Logs page shows the full picture.
     *
     * The report() hook in bootstrap/app.php already records UNCAUGHT exceptions.
     * This listener additionally captures the many controller catch-blocks that
     * Log::error() the failure and return 'Server error' WITHOUT re-throwing —
     * those never reach the report() hook, so they were previously invisible in
     * the DB (full detail still lives in storage/logs/laravel.log). Additive
     * only: ErrorLogRecorder::recordLog() skips entries already carrying an
     * exception in context, so nothing is double-recorded, and it is fully
     * non-throwing + re-entrant-safe.
     */
    private function configureErrorLogCapture(): void
    {
        Event::listen(MessageLogged::class, static function (MessageLogged $event): void {
            ErrorLogRecorder::recordLog($event->level, (string) $event->message, $event->context ?? []);
        });
    }

    /**
     * Named rate limiters for critical public/auth/payment endpoints.
     *
     * Additive security hardening only — no business logic is involved here.
     * Limits are intentionally generous (5–10/min) so genuine users are never
     * affected; only excessive bursts receive a clean HTTP 429.
     *
     * Scope keys:
     *   - per IP   : $request->ip()
     *   - per email: lowercased `email` from the request body
     *   - per user : the `auth_token` cookie (stable per session, available
     *                before any middleware runs; falls back to IP).
     */
    private function configureRateLimiters(): void
    {
        // Shared 429 body. Includes both `success` (task spec) and `status`
        // (this app's frontend convention) so existing error handling still works.
        $tooMany = static fn () => response()->json([
            'success' => false,
            'status'  => false,
            'message' => 'Too many requests. Please try again in a few minutes.',
        ], 429);

        $userKey = static fn (Request $request): string =>
            'u:' . ($request->cookie('auth_token') ?: $request->ip());

        $emailKey = static fn (Request $request): string =>
            'e:' . strtolower(trim((string) $request->input('email')));

        // ── Authentication ───────────────────────────────────────────────────
        // Login — 10/min per IP AND per email.
        RateLimiter::for('auth-login', static function (Request $request) use ($tooMany, $emailKey) {
            return [
                Limit::perMinute(10)->by('login-ip:' . $request->ip())->response($tooMany),
                Limit::perMinute(10)->by('login-' . $emailKey($request))->response($tooMany),
            ];
        });

        // Registration — 10/min per IP.
        RateLimiter::for('auth-register', static fn (Request $request) =>
            Limit::perMinute(10)->by('register-ip:' . $request->ip())->response($tooMany));

        // Forgot password — 10/min per IP AND per email.
        RateLimiter::for('auth-forgot', static function (Request $request) use ($tooMany, $emailKey) {
            return [
                Limit::perMinute(10)->by('forgot-ip:' . $request->ip())->response($tooMany),
                Limit::perMinute(10)->by('forgot-' . $emailKey($request))->response($tooMany),
            ];
        });

        // Email verification resend — 10/min per user.
        RateLimiter::for('email-verify', static fn (Request $request) =>
            Limit::perMinute(10)->by('email-verify-' . $userKey($request))->response($tooMany));

        // ── Applications ─────────────────────────────────────────────────────
        // Apply Job / Articleship (same endpoint) — 10/min per user.
        RateLimiter::for('apply', static fn (Request $request) =>
            Limit::perMinute(10)->by('apply-' . $userKey($request))->response($tooMany));

        // ── Payments ─────────────────────────────────────────────────────────
        // Payment initiation (PhonePe) — 10/min per user.
        RateLimiter::for('payment-initiate', static fn (Request $request) =>
            Limit::perMinute(10)->by('pay-init-' . $userKey($request))->response($tooMany));

        // Payment proof upload (manual / premium) — 10/min per user.
        RateLimiter::for('payment-proof', static fn (Request $request) =>
            Limit::perMinute(10)->by('pay-proof-' . $userKey($request))->response($tooMany));

        // ── Resume PDF generation ─────────────────────────────────────────────
        // mPDF uses 20-50 MB RAM per call. Cap at 5/min per authenticated user
        // to prevent memory exhaustion / DoS via bulk PDF generation.
        RateLimiter::for('resume-pdf', static fn (Request $request) =>
            Limit::perMinute(5)->by('resume-pdf-' . $userKey($request))->response($tooMany));

        // ── Public forms ─────────────────────────────────────────────────────
        // Contact / newsletter — 10/min per IP.
        RateLimiter::for('contact', static fn (Request $request) =>
            Limit::perMinute(10)->by('contact-ip:' . $request->ip())->response($tooMany));
    }
}
