<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Admin analytics — revenue reporting + operational dashboard stats.
 *
 * Read-only. Auth via the admin_token cookie (same pattern as the rest of the
 * admin API). No business logic or payment calculations are mutated here.
 *
 * Revenue sources:
 *  - Premium revenue   → firm_subscriptions.amount (status = active)
 *  - Wallet revenue    → wallet_recharges.amount   (status = approved; manual + gateway)
 *  - Creator commission→ creator_payouts.commission_amount (platform's marketplace cut)
 *  - Referral payouts  → referral_payouts.reward_amount (money paid out — an expense)
 *  Net Revenue = (premium + wallet + commission) − referral payouts
 */
class AdminAnalyticsController extends Controller
{
    private function admin(Request $request): ?object
    {
        $token = $request->cookie('admin_token');
        if (! $token) return null;
        return DB::table('admin_users')->where('api_token', $token)->where('is_active', true)->first();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /admin/revenue-analytics?period=month&from=&to=
    // ─────────────────────────────────────────────────────────────────────────

    public function revenue(Request $request)
    {
        try {
            $admin = $this->admin($request);
            if (! $admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

            $period = $request->input('period', 'month');
            [$from, $to, $granularity] = $this->resolveRange(
                $period,
                $request->input('from'),
                $request->input('to')
            );

            // ── Metric totals ────────────────────────────────────────────────
            $premiumRevenue = (float) DB::table('firm_subscriptions')
                ->where('status', 'active')
                ->whereBetween('created_at', [$from, $to])
                ->sum(DB::raw('COALESCE(amount, 0)'));

            $walletRevenue = (float) DB::table('wallet_recharges')
                ->where('status', 'approved')
                ->whereBetween('created_at', [$from, $to])
                ->sum(DB::raw('COALESCE(amount, 0)'));

            $creatorCommissions = (float) DB::table('creator_payouts')
                ->whereBetween('created_at', [$from, $to])
                ->sum(DB::raw('COALESCE(commission_amount, 0)'));

            $referralPayouts = (float) DB::table('referral_payouts')
                ->whereIn('status', ['approved', 'paid'])
                ->whereBetween('created_at', [$from, $to])
                ->sum(DB::raw('COALESCE(reward_amount, 0)'));

            $totalRevenue = $premiumRevenue + $walletRevenue + $creatorCommissions;
            $netRevenue   = $totalRevenue - $referralPayouts;

            // ── Trend series ─────────────────────────────────────────────────
            $premiumTrend = $this->trend(
                fn() => DB::table('firm_subscriptions')->where('status', 'active'),
                'created_at',
                'COALESCE(amount, 0)',
                $from,
                $to,
                $granularity
            );
            $walletTrend = $this->trend(
                fn() => DB::table('wallet_recharges')->where('status', 'approved'),
                'created_at',
                'COALESCE(amount, 0)',
                $from,
                $to,
                $granularity
            );

            // Combined revenue trend = premium + wallet + commission per bucket.
            $commissionTrend = $this->trend(
                fn() => DB::table('creator_payouts'),
                'created_at',
                'COALESCE(commission_amount, 0)',
                $from,
                $to,
                $granularity
            );
            $revenueTrend = [];
            foreach ($premiumTrend as $i => $point) {
                $revenueTrend[] = [
                    'label' => $point['label'],
                    'value' => round(
                        $point['value']
                        + ($walletTrend[$i]['value'] ?? 0)
                        + ($commissionTrend[$i]['value'] ?? 0),
                        2
                    ),
                ];
            }

            return response()->json([
                'status' => true,
                'data'   => [
                    'metrics' => [
                        'total_revenue'       => round($totalRevenue, 2),
                        'premium_revenue'     => round($premiumRevenue, 2),
                        'wallet_revenue'      => round($walletRevenue, 2),
                        'creator_commissions' => round($creatorCommissions, 2),
                        'referral_payouts'    => round($referralPayouts, 2),
                        'net_revenue'         => round($netRevenue, 2),
                    ],
                    'trends' => [
                        'revenue' => $revenueTrend,
                        'premium' => $premiumTrend,
                        'wallet'  => $walletTrend,
                    ],
                    'range' => [
                        'from'   => $from->toDateTimeString(),
                        'to'     => $to->toDateTimeString(),
                        'period' => $period,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('AdminAnalytics@revenue: ' . $e->getMessage(), ['line' => $e->getLine()]);
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /admin/dashboard-stats
    // ─────────────────────────────────────────────────────────────────────────

    public function dashboard(Request $request)
    {
        try {
            $admin = $this->admin($request);
            if (! $admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

            $monthStart = Carbon::now()->startOfMonth();
            $now        = Carbon::now();

            // ── KPI row 1 ────────────────────────────────────────────────────
            $totalStudents = (int) DB::table('users')
                ->where('role', 'student')->where('is_deleted', false)->count();
            $totalFirms = (int) DB::table('users')
                ->where('role', 'firm')->where('is_deleted', false)->count();
            $applicationsThisMonth = (int) DB::table('applications')
                ->whereBetween('created_at', [$monthStart, $now])->count();

            $premiumRevMonth = (float) DB::table('firm_subscriptions')
                ->where('status', 'active')
                ->whereBetween('created_at', [$monthStart, $now])
                ->sum(DB::raw('COALESCE(amount, 0)'));
            $walletRevMonth = (float) DB::table('wallet_recharges')
                ->where('status', 'approved')
                ->whereBetween('created_at', [$monthStart, $now])
                ->sum(DB::raw('COALESCE(amount, 0)'));
            $commissionMonth = (float) DB::table('creator_payouts')
                ->whereBetween('created_at', [$monthStart, $now])
                ->sum(DB::raw('COALESCE(commission_amount, 0)'));
            $revenueThisMonth = $premiumRevMonth + $walletRevMonth + $commissionMonth;

            // ── KPI row 2 ────────────────────────────────────────────────────
            $premiumFirms = (int) DB::table('firm_profiles')->where('is_premium', 1)->count();
            $walletRechargesMonth = (int) DB::table('wallet_recharges')
                ->where('status', 'approved')
                ->whereBetween('created_at', [$monthStart, $now])->count();
            $pendingVerifications = (int) DB::table('firm_profiles')
                ->where('verification_status', 'pending')->count();
            $unreadNotifications = (int) DB::table('admin_notifications')
                ->where('is_read', false)->count();

            // ── Recent activity ──────────────────────────────────────────────
            $recentFirms = DB::table('firm_profiles as fp')
                ->join('users as u', 'u.id', '=', 'fp.user_id')
                ->where('u.role', 'firm')->where('u.is_deleted', false)
                ->orderByDesc('fp.created_at')
                ->limit(5)
                ->get(['fp.user_id as id', 'fp.firm_name as name', 'u.email', 'fp.created_at'])
                ->map(fn($r) => [
                    'id'         => (int) $r->id,
                    'name'       => $r->name,
                    'email'      => $r->email,
                    'created_at' => $r->created_at ? date('d M Y h:i A', strtotime($r->created_at)) : null,
                ]);

            $recentPremium = DB::table('firm_subscriptions as fs')
                ->leftJoin('firm_profiles as fp', 'fp.user_id', '=', 'fs.firm_id')
                ->where('fs.status', 'active')
                ->orderByDesc('fs.created_at')
                ->limit(5)
                ->get(['fs.id', 'fp.firm_name', 'fs.amount', 'fs.created_at'])
                ->map(fn($r) => [
                    'id'         => (int) $r->id,
                    'firm_name'  => $r->firm_name,
                    'amount'     => (float) ($r->amount ?? 0),
                    'created_at' => $r->created_at ? date('d M Y h:i A', strtotime($r->created_at)) : null,
                ]);

            $recentApplications = DB::table('applications as a')
                ->leftJoin('users as u', 'u.id', '=', 'a.student_id')
                ->leftJoin('jobs as j', 'j.id', '=', 'a.job_id')
                ->orderByDesc('a.created_at')
                ->limit(5)
                ->get(['a.id', 'u.name as student_name', 'j.title as job_title', 'a.created_at'])
                ->map(fn($r) => [
                    'id'           => (int) $r->id,
                    'student_name' => $r->student_name,
                    'job_title'    => $r->job_title,
                    'created_at'   => $r->created_at ? date('d M Y h:i A', strtotime($r->created_at)) : null,
                ]);

            $recentRecharges = DB::table('wallet_recharges as wr')
                ->leftJoin('users as u', 'u.id', '=', 'wr.user_id')
                ->where('wr.status', 'approved')
                ->orderByDesc('wr.created_at')
                ->limit(5)
                ->get(['wr.id', 'u.name as student_name', 'wr.amount', 'wr.created_at'])
                ->map(fn($r) => [
                    'id'           => (int) $r->id,
                    'student_name' => $r->student_name,
                    'amount'       => (float) ($r->amount ?? 0),
                    'created_at'   => $r->created_at ? date('d M Y h:i A', strtotime($r->created_at)) : null,
                ]);

            return response()->json([
                'status' => true,
                'data'   => [
                    'kpis' => [
                        'total_students'              => $totalStudents,
                        'total_firms'                 => $totalFirms,
                        'applications_this_month'     => $applicationsThisMonth,
                        'revenue_this_month'          => round($revenueThisMonth, 2),
                        'premium_firms'               => $premiumFirms,
                        'wallet_recharges_this_month' => $walletRechargesMonth,
                        'pending_verifications'       => $pendingVerifications,
                        'unread_notifications'        => $unreadNotifications,
                    ],
                    'recent' => [
                        'firms'        => $recentFirms,
                        'premium'      => $recentPremium,
                        'applications' => $recentApplications,
                        'recharges'    => $recentRecharges,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('AdminAnalytics@dashboard: ' . $e->getMessage(), ['line' => $e->getLine()]);
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Resolve a period keyword into [from, to, granularity].
     * granularity ∈ hour | day | month — drives the trend bucket size.
     */
    private function resolveRange(string $period, ?string $from, ?string $to): array
    {
        $now = Carbon::now();
        switch ($period) {
            case 'today':
                return [$now->copy()->startOfDay(), $now->copy()->endOfDay(), 'hour'];
            case 'week':
                return [$now->copy()->startOfWeek(), $now->copy()->endOfWeek(), 'day'];
            case 'year':
                return [$now->copy()->startOfYear(), $now->copy()->endOfYear(), 'month'];
            case 'custom':
                $f = $from ? Carbon::parse($from)->startOfDay() : $now->copy()->startOfMonth();
                $t = $to ? Carbon::parse($to)->endOfDay() : $now->copy()->endOfDay();
                // Pick a sensible granularity for the span.
                $gran = $f->diffInDays($t) > 90 ? 'month' : 'day';
                return [$f, $t, $gran];
            case 'month':
            default:
                return [$now->copy()->startOfMonth(), $now->copy()->endOfMonth(), 'day'];
        }
    }

    /**
     * Build a continuous trend series (gaps filled with 0) for a money column.
     *
     * @param  \Closure $base  Returns a fresh query builder with status filters applied.
     */
    private function trend(\Closure $base, string $dateCol, string $amountExpr, Carbon $from, Carbon $to, string $gran): array
    {
        [$sqlFormat, $phpFormat, $label, $step] = match ($gran) {
            'hour'  => ['%Y-%m-%d %H', 'Y-m-d H', 'g A', 'addHour'],
            'month' => ['%Y-%m', 'Y-m', 'M Y', 'addMonth'],
            default => ['%Y-%m-%d', 'Y-m-d', 'd M', 'addDay'],
        };

        $rows = $base()
            ->whereBetween($dateCol, [$from, $to])
            ->selectRaw("DATE_FORMAT($dateCol, '$sqlFormat') as bucket, SUM($amountExpr) as total")
            ->groupBy('bucket')
            ->pluck('total', 'bucket');

        $series = [];
        $cursor = $from->copy();
        // Cap iterations defensively (e.g. hourly over a huge custom range).
        $guard = 0;
        while ($cursor->lte($to) && $guard < 1000) {
            $key = $cursor->format($phpFormat);
            $series[] = [
                'label' => $cursor->format($label),
                'value' => round((float) ($rows[$key] ?? 0), 2),
            ];
            $cursor->{$step}();
            $guard++;
        }

        return $series;
    }
}
