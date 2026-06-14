<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

/**
 * Application-level System Health for the admin dashboard.
 *
 * Scope is deliberately APPLICATION health only — DB connectivity, queue/jobs,
 * storage capacity, payment/mail configuration and sitemap reachability. It does
 * NOT touch CPU/RAM/Docker/Redis/Nginx/process/load metrics.
 *
 * Status vocabulary per check: 'healthy' (green) | 'warning' (yellow) | 'critical' (red).
 */
class AdminSystemHealthController extends Controller
{
    /** Stale-job threshold: a database-queued job sitting unreserved longer than this implies no worker is consuming. */
    private const QUEUE_STALE_SECONDS = 120;

    private function getAdmin(Request $request): ?object
    {
        $token = $request->cookie('admin_token');
        if (!$token) return null;
        return DB::table('admin_users')
            ->where('api_token', $token)
            ->where('is_active', true)
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | GET /admin/system-health
    |--------------------------------------------------------------------------
    */
    public function health(Request $request)
    {
        $admin = $this->getAdmin($request);
        if (!$admin) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $checks = [
            'database'    => $this->checkDatabase(),
            'queue'       => $this->checkQueue(),
            'failed_jobs' => $this->checkFailedJobs(),
            'storage'     => $this->checkStorage(),
            'phonepe'     => $this->checkPhonePe(),
            'email'       => $this->checkEmail(),
            'sitemap'     => $this->checkSitemap(),
        ];

        // Overall: any critical → critical; else any warning → warning; else healthy.
        $statuses = array_column($checks, 'status');
        if (in_array('critical', $statuses, true)) {
            $overallStatus = 'critical';
            $overallLabel  = 'System Critical';
        } elseif (in_array('warning', $statuses, true)) {
            $overallStatus = 'warning';
            $overallLabel  = 'System Warning';
        } else {
            $overallStatus = 'healthy';
            $overallLabel  = 'System Healthy';
        }

        return response()->json([
            'status' => true,
            'data'   => [
                'overall' => [
                    'status'     => $overallStatus,
                    'label'      => $overallLabel,
                    'checked_at' => now()->toIso8601String(),
                ],
                'checks' => $checks,
            ],
        ]);
    }

    // ── 1. Database ──────────────────────────────────────────────────────────
    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $ms = (int) round((microtime(true) - $start) * 1000);

            return [
                'label'            => 'Database',
                'status'           => 'healthy',
                'value'            => 'Connected',
                'response_time_ms' => $ms,
            ];
        } catch (\Throwable $e) {
            Log::error('SystemHealth@checkDatabase: ' . $e->getMessage());
            return [
                'label'            => 'Database',
                'status'           => 'critical',
                'value'            => 'Disconnected',
                'response_time_ms' => null,
            ];
        }
    }

    // ── 2. Queue Workers ─────────────────────────────────────────────────────
    private function checkQueue(): array
    {
        try {
            $driver = config('queue.default');

            // 'sync' processes jobs inline — always operational, no worker needed.
            if ($driver === 'sync') {
                return [
                    'label'   => 'Queue Workers',
                    'status'  => 'healthy',
                    'value'   => 'Running',
                    'detail'  => 'sync driver (inline)',
                    'pending' => 0,
                ];
            }

            // Database queue — verify the backing table is reachable and not backed up.
            $table = config('queue.connections.database.table', 'queue_jobs');
            $now   = time();

            $pending = (int) DB::table($table)->count();
            $stale   = (int) DB::table($table)
                ->whereNull('reserved_at')
                ->where('available_at', '<=', $now - self::QUEUE_STALE_SECONDS)
                ->count();

            // A stale backlog means jobs are queued but nothing is consuming them.
            if ($stale > 0) {
                return [
                    'label'   => 'Queue Workers',
                    'status'  => 'critical',
                    'value'   => 'Not Running',
                    'detail'  => "{$stale} job(s) stuck > " . self::QUEUE_STALE_SECONDS . 's',
                    'pending' => $pending,
                ];
            }

            return [
                'label'   => 'Queue Workers',
                'status'  => 'healthy',
                'value'   => 'Running',
                'detail'  => $pending > 0 ? "{$pending} job(s) processing" : 'Idle',
                'pending' => $pending,
            ];
        } catch (\Throwable $e) {
            Log::error('SystemHealth@checkQueue: ' . $e->getMessage());
            return [
                'label'   => 'Queue Workers',
                'status'  => 'critical',
                'value'   => 'Not Running',
                'detail'  => 'Queue table unreachable',
                'pending' => null,
            ];
        }
    }

    // ── 3. Failed Jobs ───────────────────────────────────────────────────────
    private function checkFailedJobs(): array
    {
        try {
            $count = (int) DB::table('failed_jobs')->count();
            return [
                'label'  => 'Failed Jobs',
                'status' => $count === 0 ? 'healthy' : 'warning',
                'value'  => $count . ' Failed Jobs',
                'count'  => $count,
            ];
        } catch (\Throwable $e) {
            Log::error('SystemHealth@checkFailedJobs: ' . $e->getMessage());
            return [
                'label'  => 'Failed Jobs',
                'status' => 'warning',
                'value'  => 'Unknown',
                'count'  => null,
            ];
        }
    }

    // ── 4. Storage Usage ─────────────────────────────────────────────────────
    private function checkStorage(): array
    {
        try {
            $path  = storage_path();
            $total = @disk_total_space($path);
            $free  = @disk_free_space($path);

            if (!$total || $free === false) {
                return [
                    'label'   => 'Storage Usage',
                    'status'  => 'warning',
                    'value'   => 'Unavailable',
                    'percent' => null,
                ];
            }

            $used    = $total - $free;
            $percent = (int) round(($used / $total) * 100);

            $status = 'healthy';
            if ($percent > 90)      $status = 'critical';
            elseif ($percent >= 80) $status = 'warning';

            $usedGb  = round($used  / 1073741824, 1);   // 1024^3
            $totalGb = round($total / 1073741824, 1);

            return [
                'label'    => 'Storage Usage',
                'status'   => $status,
                'value'    => "{$usedGb} GB / {$totalGb} GB",
                'percent'  => $percent,
                'used_gb'  => $usedGb,
                'total_gb' => $totalGb,
            ];
        } catch (\Throwable $e) {
            Log::error('SystemHealth@checkStorage: ' . $e->getMessage());
            return [
                'label'   => 'Storage Usage',
                'status'  => 'warning',
                'value'   => 'Unavailable',
                'percent' => null,
            ];
        }
    }

    // ── 5. PhonePe Configuration (validation only — no API calls) ─────────────
    private function checkPhonePe(): array
    {
        $cfg = config('services.phonepe', []);
        $required = ['merchant_id', 'client_id', 'client_secret', 'webhook_username', 'webhook_password'];

        $missing = array_filter($required, fn($k) => empty($cfg[$k] ?? null));
        $ok = count($missing) === 0;

        return [
            'label'  => 'PhonePe',
            'status' => $ok ? 'healthy' : 'critical',
            'value'  => $ok ? 'Configured' : 'Not Configured',
            'detail' => $ok ? null : count($missing) . ' credential(s) missing',
        ];
    }

    // ── 6. Email Service Configuration (validation only — no test email) ──────
    private function checkEmail(): array
    {
        $mailer = config('mail.default');
        $conn   = config("mail.mailers.{$mailer}", []);

        // For SMTP (the configured transport) require host + credentials.
        if (($conn['transport'] ?? $mailer) === 'smtp') {
            $ok = !empty($conn['host']) && !empty($conn['username']) && !empty($conn['password']);
        } else {
            // Non-SMTP transports (ses, log, sendmail...) are considered configured if defined.
            $ok = !empty($conn);
        }

        return [
            'label'  => 'Email Service',
            'status' => $ok ? 'healthy' : 'critical',
            'value'  => $ok ? 'Configured' : 'Not Configured',
            'detail' => $ok ? strtoupper((string) $mailer) : 'Missing mail credentials',
        ];
    }

    // ── 7. Sitemap ───────────────────────────────────────────────────────────
    private function checkSitemap(): array
    {
        try {
            $staticCount = SitemapController::staticUrlCount();

            $publishedBlogs = (int) DB::table('blogs')->where('status', 'published')->count();

            // Reachability: confirm the sitemap routes are registered (no HTTP self-call).
            $routeUris    = collect(Route::getRoutes()->getRoutes())->map(fn($r) => $r->uri());
            $routesExist  = $routeUris->contains('sitemap.xml')
                && $routeUris->contains('sitemaps/static.xml')
                && $routeUris->contains('sitemaps/blogs.xml');

            $ok = $routesExist && $staticCount > 0;

            return [
                'label'            => 'Sitemap',
                'status'           => $ok ? 'healthy' : 'warning',
                'value'            => $ok ? 'Healthy' : 'Issues Found',
                'static_urls'      => $staticCount,
                'published_blogs'  => $publishedBlogs,
                'routes_reachable' => $routesExist,
            ];
        } catch (\Throwable $e) {
            Log::error('SystemHealth@checkSitemap: ' . $e->getMessage());
            return [
                'label'           => 'Sitemap',
                'status'          => 'warning',
                'value'           => 'Issues Found',
                'static_urls'     => null,
                'published_blogs' => null,
            ];
        }
    }
}
