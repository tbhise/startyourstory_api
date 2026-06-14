<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

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
        // Login — 5/min per IP AND per email.
        RateLimiter::for('auth-login', static function (Request $request) use ($tooMany, $emailKey) {
            return [
                Limit::perMinute(5)->by('login-ip:' . $request->ip())->response($tooMany),
                Limit::perMinute(5)->by('login-' . $emailKey($request))->response($tooMany),
            ];
        });

        // Registration — 5/min per IP.
        RateLimiter::for('auth-register', static fn (Request $request) =>
            Limit::perMinute(5)->by('register-ip:' . $request->ip())->response($tooMany));

        // Forgot password — 3/min per IP AND per email.
        RateLimiter::for('auth-forgot', static function (Request $request) use ($tooMany, $emailKey) {
            return [
                Limit::perMinute(3)->by('forgot-ip:' . $request->ip())->response($tooMany),
                Limit::perMinute(3)->by('forgot-' . $emailKey($request))->response($tooMany),
            ];
        });

        // Email verification resend — 3/min per user.
        RateLimiter::for('email-verify', static fn (Request $request) =>
            Limit::perMinute(3)->by('email-verify-' . $userKey($request))->response($tooMany));

        // ── Applications ─────────────────────────────────────────────────────
        // Apply Job / Articleship (same endpoint) — 10/min per user.
        RateLimiter::for('apply', static fn (Request $request) =>
            Limit::perMinute(10)->by('apply-' . $userKey($request))->response($tooMany));

        // ── Payments ─────────────────────────────────────────────────────────
        // Payment initiation (PhonePe) — 5/min per user.
        RateLimiter::for('payment-initiate', static fn (Request $request) =>
            Limit::perMinute(5)->by('pay-init-' . $userKey($request))->response($tooMany));

        // Payment proof upload (manual / premium) — 5/min per user.
        RateLimiter::for('payment-proof', static fn (Request $request) =>
            Limit::perMinute(5)->by('pay-proof-' . $userKey($request))->response($tooMany));

        // ── Public forms ─────────────────────────────────────────────────────
        // Contact / newsletter — 5/min per IP.
        RateLimiter::for('contact', static fn (Request $request) =>
            Limit::perMinute(5)->by('contact-ip:' . $request->ip())->response($tooMany));
    }
}
