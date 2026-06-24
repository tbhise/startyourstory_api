<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Admin Email Logs — READ-ONLY analytics over the shared `email_logs` table
 * (every email the platform sends logs a row). Mirrors ErrorLogController:
 * paginated index, stats, and a single "delete all" action. Admin-only — all
 * /admin/* paths are guarded by AdminAuthMiddleware (see bootstrap/app.php).
 */
class EmailLogController extends Controller
{
    private const STATUSES = ['pending', 'sent', 'failed'];
    private const PER_PAGE  = 50;

    /*
    |--------------------------------------------------------------------------
    | GET /admin/email-logs
    | Query: status, campaign_type, from (Y-m-d), to (Y-m-d), search, page
    | Latest first. Pagination mirrors the error-logs page/has_more contract.
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        try {
            $page    = max(1, (int) $request->get('page', 1));
            $status  = (string) $request->get('status', '');
            $purpose = (string) $request->get('campaign_type', '');
            $from    = trim((string) $request->get('from', ''));
            $to      = trim((string) $request->get('to', ''));
            $search  = trim((string) $request->get('search', ''));

            // Shared filter closure — applied to BOTH the count query (email_logs
            // only, so the join can never inflate the total) and the data query.
            $applyFilters = function ($q) use ($status, $purpose, $from, $to, $search) {
                if ($status !== '' && in_array($status, self::STATUSES, true)) {
                    $q->where('email_logs.status', $status);
                }
                if ($purpose !== '') {
                    $q->where('email_logs.email_purpose', $purpose);
                }
                if ($from !== '') {
                    $q->whereDate('email_logs.created_at', '>=', $from);
                }
                if ($to !== '') {
                    $q->whereDate('email_logs.created_at', '<=', $to);
                }
                if ($search !== '') {
                    $q->where(function ($s) use ($search) {
                        $s->where('email_logs.recipient_email', 'like', "%{$search}%")
                          ->orWhere('email_logs.subject', 'like', "%{$search}%");
                    });
                }
            };

            $countQuery = DB::table('email_logs');
            $applyFilters($countQuery);
            $total = $countQuery->count();

            // Left join users (by email) only to surface the recipient name. Email
            // is effectively unique in users, so this stays 1:1 and never inflates.
            // `users.email` and `email_logs.recipient_email` use different collations
            // (utf8mb4_0900_ai_ci vs utf8mb4_unicode_ci), so the join `=` must force a
            // common collation or MySQL throws "Illegal mix of collations" (1267).
            $dataQuery = DB::table('email_logs')
                ->leftJoin('users', function ($join) {
                    $join->on(
                        'email_logs.recipient_email',
                        '=',
                        DB::raw('`users`.`email` collate utf8mb4_unicode_ci')
                    );
                })
                ->select([
                    'email_logs.id',
                    'email_logs.recipient_email',
                    'email_logs.recipient_type',
                    'email_logs.email_purpose',
                    'email_logs.subject',
                    'email_logs.status',
                    'email_logs.click_count',
                    'email_logs.clicked_at',
                    'email_logs.sent_at',
                    'email_logs.created_at',
                    'users.name as recipient_name',
                ]);
            $applyFilters($dataQuery);

            $rows = $dataQuery
                ->orderByDesc('email_logs.created_at')
                ->offset(($page - 1) * self::PER_PAGE)
                ->limit(self::PER_PAGE)
                ->get();

            return response()->json([
                'status'  => true,
                'message' => 'Email logs fetched',
                'data'    => [
                    'logs'     => $rows,
                    'total'    => $total,
                    'page'     => $page,
                    'has_more' => ($page * self::PER_PAGE) < $total,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('EmailLogController@index: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /admin/email-logs/stats
    | Totals + distinct campaign types (so the filter dropdown stays accurate).
    |--------------------------------------------------------------------------
    */
    public function stats()
    {
        try {
            $total   = DB::table('email_logs')->count();
            $sent    = DB::table('email_logs')->where('status', 'sent')->count();
            $failed  = DB::table('email_logs')->where('status', 'failed')->count();
            $clicked = DB::table('email_logs')->where('click_count', '>', 0)->count();

            $campaignTypes = DB::table('email_logs')
                ->select('email_purpose')
                ->distinct()
                ->orderBy('email_purpose')
                ->pluck('email_purpose');

            return response()->json([
                'status' => true,
                'data'   => [
                    'total'          => $total,
                    'sent'           => $sent,
                    'failed'         => $failed,
                    'clicked'        => $clicked,
                    'campaign_types' => $campaignTypes,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('EmailLogController@stats: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE /admin/email-logs
    | Irreversible: clears ALL email log rows. (Read-only page — no per-row ops.)
    |--------------------------------------------------------------------------
    */
    public function destroy(Request $request)
    {
        try {
            $deleted = DB::table('email_logs')->delete();
            return response()->json(['status' => true, 'message' => "{$deleted} logs deleted"]);
        } catch (\Exception $e) {
            Log::error('EmailLogController@destroy: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }
}
