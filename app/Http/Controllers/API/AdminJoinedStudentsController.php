<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * READ-ONLY admin view of students who joined a firm after placement.
 *
 * Backed entirely by the EXISTING student_employment_history table (written by
 * the student Employment Status card — StudentEmploymentController). One row
 * per reported joining; "Joined Via SYS" is the existing joined_via_platform
 * flag the student sets when reporting — no new tables or manual flags.
 */
class AdminJoinedStudentsController extends Controller
{
    private const PER_PAGE = 20;

    private function getAdmin(Request $request): ?object
    {
        $token = $request->cookie('admin_token');
        if (!$token) return null;
        return DB::table('admin_users')
            ->where('api_token', $token)
            ->where('is_active', true)
            ->first();
    }

    private function baseQuery()
    {
        return DB::table('student_employment_history as seh')
            ->join('users as u', 'u.id', '=', 'seh.user_id');
    }

    private function applyFilters($query, Request $request)
    {
        if ($from = $request->get('joined_from')) {
            $query->whereDate('seh.joined_date', '>=', $from);
        }
        if ($to = $request->get('joined_to')) {
            $query->whereDate('seh.joined_date', '<=', $to);
        }
        if (in_array($via = $request->get('via_sys'), ['yes', 'no'], true)) {
            $query->where('seh.joined_via_platform', $via === 'yes' ? 1 : 0);
        }
        if (($student = trim((string) $request->get('student', ''))) !== '') {
            $query->where(function ($q) use ($student) {
                $q->where('u.name', 'like', "%{$student}%")
                  ->orWhere('u.email', 'like', "%{$student}%");
            });
        }
        if (($firm = trim((string) $request->get('firm', ''))) !== '') {
            $query->where('seh.organization_name', 'like', "%{$firm}%");
        }
        if (($search = trim((string) $request->get('search', ''))) !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('u.name', 'like', "%{$search}%")
                  ->orWhere('u.email', 'like', "%{$search}%")
                  ->orWhere('seh.organization_name', 'like', "%{$search}%");
            });
        }
        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | GET /admin/joined-students
    | Filters: joined_from, joined_to, student, firm, via_sys (yes|no),
    |          search, page. Sorted newest joining first.
    |--------------------------------------------------------------------------
    */
    public function index(Request $request): JsonResponse
    {
        $admin = $this->getAdmin($request);
        if (!$admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

        try {
            $page = max(1, (int) $request->get('page', 1));

            $query = $this->applyFilters($this->baseQuery(), $request);

            $total = (clone $query)->count();

            $rows = $query
                ->orderByDesc('seh.joined_date')
                ->orderByDesc('seh.id')
                ->offset(($page - 1) * self::PER_PAGE)
                ->limit(self::PER_PAGE)
                ->get([
                    'seh.id',
                    'seh.user_id',
                    'seh.organization_name',
                    'seh.designation',
                    'seh.joined_date',
                    'seh.joined_via_platform',
                    'seh.is_current',
                    'seh.created_at',
                    'u.name as student_name',
                    'u.email as student_email',
                ]);

            $students = $rows->map(fn ($r) => [
                'id'             => (int) $r->id,
                'user_id'        => (int) $r->user_id,
                'student_name'   => $r->student_name,
                'student_email'  => $r->student_email,
                'firm_name'      => $r->organization_name,
                'designation'    => $r->designation,
                'joined_on'      => $r->joined_date ? date('d M Y', strtotime($r->joined_date)) : null,
                'joined_date'    => $r->joined_date,
                'joined_via_sys' => (bool) $r->joined_via_platform,
                'is_current'     => (bool) $r->is_current,
                'reported_on'    => $r->created_at ? date('d M Y', strtotime($r->created_at)) : null,
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Joined students fetched',
                'data'    => [
                    'students'  => $students,
                    'total'     => $total,
                    'page'      => $page,
                    'last_page' => max(1, (int) ceil($total / self::PER_PAGE)),
                    'has_more'  => ($page * self::PER_PAGE) < $total,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('AdminJoinedStudentsController@index: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /admin/joined-students/stats — headline counts for stat cards
    |--------------------------------------------------------------------------
    */
    public function stats(Request $request): JsonResponse
    {
        $admin = $this->getAdmin($request);
        if (!$admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

        try {
            $counts = DB::table('student_employment_history')
                ->selectRaw('COUNT(*) as total')
                ->selectRaw('SUM(joined_via_platform = 1) as via_sys')
                ->selectRaw('SUM(joined_via_platform = 0) as outside_sys')
                ->selectRaw("SUM(joined_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')) as this_month")
                ->first();

            return response()->json([
                'status' => true,
                'data'   => [
                    'total'       => (int) ($counts->total ?? 0),
                    'via_sys'     => (int) ($counts->via_sys ?? 0),
                    'outside_sys' => (int) ($counts->outside_sys ?? 0),
                    'this_month'  => (int) ($counts->this_month ?? 0),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('AdminJoinedStudentsController@stats: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }
}
