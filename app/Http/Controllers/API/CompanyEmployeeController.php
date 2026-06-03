<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CompanyEmployeeController extends Controller
{
    /**
     * POST /companies/{id}/employees/preview
     * Returns first 8 visible students whose current_firm_id matches + totals + stats.
     */
    public function getPreview(Request $request, $id)
    {
        try {
            $firm = DB::table('firm_profiles')->where('id', $id)->first();
            if (!$firm) {
                return response()->json(['status' => false, 'message' => 'Company not found']);
            }

            $employees = $this->baseQuery($id)->limit(8)->get();
            $total     = $this->countQuery($id);

            return response()->json([
                'status' => true,
                'data'   => [
                    'employees' => $employees,
                    'total'     => $total,
                    'stats'     => $this->buildStats($id, $total),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('CompanyEmployeeController@getPreview: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * POST /companies/{id}/employees
     * Paginated directory with search + category filter.
     */
    public function getDirectory(Request $request, $id)
    {
        try {
            $firm = DB::table('firm_profiles')->where('id', $id)->first();
            if (!$firm) {
                return response()->json(['status' => false, 'message' => 'Company not found']);
            }

            $perPage  = 20;
            $page     = max(1, (int) $request->input('page', 1));
            $search   = trim($request->input('search', ''));
            $category = $request->input('category', 'all');

            $query = $this->baseQuery($id, $search, $category);
            $total = (clone $query)->count('sp.id');

            $employees = $query
                ->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get();

            return response()->json([
                'status' => true,
                'data'   => [
                    'employees' => $employees,
                    'total'     => $total,
                    'page'      => $page,
                    'per_page'  => $perPage,
                    'has_more'  => ($page * $perPage) < $total,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('CompanyEmployeeController@getDirectory: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * POST /companies/{id}/employees/category-counts
     */
    public function getCategoryCounts(Request $request, $id)
    {
        try {
            $counts = DB::table('student_profiles as sp')
                ->where('sp.current_firm_id', $id)
                ->where(function ($q) {
                    $q->whereNull('sp.show_in_directory')
                      ->orWhere('sp.show_in_directory', true);
                })
                ->selectRaw('sp.looking_for, COUNT(*) as count')
                ->groupBy('sp.looking_for')
                ->get()
                ->keyBy('looking_for');

            return response()->json(['status' => true, 'data' => $counts]);
        } catch (\Exception $e) {
            Log::error('CompanyEmployeeController@getCategoryCounts: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Helpers                                                             */
    /* ------------------------------------------------------------------ */

    private function baseQuery($companyId, string $search = '', string $category = 'all')
    {
        $q = DB::table('student_profiles as sp')
            ->join('users as u', 'u.id', '=', 'sp.user_id')
            ->where('sp.current_firm_id', $companyId)
            ->where(function ($inner) {
                $inner->whereNull('sp.show_in_directory')
                      ->orWhere('sp.show_in_directory', true);
            })
            ->select([
                'sp.id',
                'u.name',
                'u.profile_image',
                'sp.looking_for as designation',
                DB::raw('NULL as joined_at'),
                DB::raw("'hired' as verification_type"),
                'sp.looking_for',
            ])
            ->orderBy('sp.id', 'desc');

        if ($search !== '') {
            $q->where(function ($s) use ($search) {
                $s->where('u.name', 'like', "%{$search}%")
                  ->orWhere('sp.looking_for', 'like', "%{$search}%");
            });
        }

        if ($category !== 'all') {
            $q->where('sp.looking_for', $category);
        }

        return $q;
    }

    private function countQuery($companyId): int
    {
        return DB::table('student_profiles as sp')
            ->where('sp.current_firm_id', $companyId)
            ->where(function ($q) {
                $q->whereNull('sp.show_in_directory')
                  ->orWhere('sp.show_in_directory', true);
            })
            ->count();
    }

    private function buildStats($companyId, int $total): array
    {
        return [
            'total_members'        => $total,
            'hired_through_portal' => $total,
            'interviews_conducted' => 0,
            'rating'               => null,
        ];
    }
}
