<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FirmDashboardController extends Controller
{




    public function getCandidates(Request $request)
    {
        try {

            $pageSize = $request->page_size ?? 12;

            $query = DB::table('users')
                ->select(
                    'users.id',
                    'users.name',
                    'users.email',
                    'users.mobile',

                    'student_profiles.looking_for',
                    'student_profiles.srn',
                    'student_profiles.address',
                    'student_profiles.gender',
                    'student_profiles.passing_month',
                    'student_profiles.ca_status',
                    'student_profiles.articleship_status',
                    'student_profiles.preferred_location',
                    'student_profiles.it_oc_status',
                    'student_profiles.exposure_type',
                    'student_profiles.core_department',
                    'student_profiles.attempts',
                    'student_profiles.linkedin_url',
                    'student_profiles.portfolio_url',
                    'student_profiles.marksheet_path',
                    'student_profiles.resume_path',
                    'student_profiles.created_at',
                    'student_profiles.updated_at',
                    'student_profiles.registration_type'
                )
                ->leftJoin('student_profiles', 'users.id', '=', 'student_profiles.user_id')
                ->where('users.is_deleted', false)
                ->where('users.role', 'student');

            /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

            if ($request->filled('search')) {

                $search = trim($request->search);

                $query->where(function ($q) use ($search) {

                    $q->where('users.name', 'like', "%{$search}%")
                        ->orWhere('users.email', 'like', "%{$search}%")
                        ->orWhere('student_profiles.address', 'like', "%{$search}%")
                        ->orWhere('student_profiles.core_department', 'like', "%{$search}%");
                });
            }

            /*
        |--------------------------------------------------------------------------
        | City Filter
        |--------------------------------------------------------------------------
        */

            if ($request->filled('cities')) {

                $cities = is_array($request->cities)
                    ? $request->cities
                    : [$request->cities];

                $query->whereIn('student_profiles.address', $cities);
            }

            /*
        |--------------------------------------------------------------------------
        | Gender Filter
        |--------------------------------------------------------------------------
        */

            if ($request->filled('genders')) {

                $genders = is_array($request->genders)
                    ? $request->genders
                    : [$request->genders];

                $query->whereIn('student_profiles.gender', $genders);
            }

            /*
        |--------------------------------------------------------------------------
        | Registration Type
        |--------------------------------------------------------------------------
        | Provisional / Confirm
        |--------------------------------------------------------------------------
        */

            if ($request->filled('registration_types')) {

                $registrationTypes = is_array($request->registration_types)
                    ? $request->registration_types
                    : [$request->registration_types];

                $query->whereIn('student_profiles.articleship_status', $registrationTypes);
            }

            /*
        |--------------------------------------------------------------------------
        | Departments
        |--------------------------------------------------------------------------
        */

            if ($request->filled('departments')) {

                $departments = is_array($request->departments)
                    ? $request->departments
                    : [$request->departments];

                $query->whereIn('student_profiles.core_department', $departments);
            }

            /*
        |--------------------------------------------------------------------------
        | Registered For
        |--------------------------------------------------------------------------
        | Articleship
        | Semi Qualified Jobs
        | Qualified Jobs
        | Creator or Freelancer
        |--------------------------------------------------------------------------
        */

            if ($request->filled('registered_for')) {

                $registeredFor = is_array($request->registered_for)
                    ? $request->registered_for
                    : [$request->registered_for];

                $query->whereIn('student_profiles.looking_for', $registeredFor);
            }

            /*
        |--------------------------------------------------------------------------
        | Passing Month
        |--------------------------------------------------------------------------
        */

            if ($request->filled('passing_months')) {

                $passingMonths = is_array($request->passing_months)
                    ? $request->passing_months
                    : [$request->passing_months];

                $query->whereIn('student_profiles.passing_month', $passingMonths);
            }

            /*
        |--------------------------------------------------------------------------
        | Sorting
        |--------------------------------------------------------------------------
        */

            switch ($request->sort) {

                case 'oldest':

                    $query->orderBy('student_profiles.created_at', 'asc');

                    break;

                case 'name':

                    $query->orderBy('users.name', 'asc');

                    break;

                case 'passing_month':

                    $query->orderBy('student_profiles.passing_month', 'asc');

                    break;

                default:

                    $query->orderBy('student_profiles.created_at', 'desc');

                    break;
            }

            /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

            $users = $query->paginate($pageSize);

            return response()->json([
                'status' => true,
                'data' => [
                    'students' => $users->items(),
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'next_page_url' => $users->nextPageUrl(),
                    'total' => $users->total(),
                ]
            ]);
        } catch (\Exception $e) {

            Log::error('Get Candidates Error: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Unexpected server error..!'
            ]);
        }
    }

    public function candidateDetail(Request $request)
    {
        try {


            $users = DB::table('users')
                ->leftJoin('student_profiles', 'users.id', 'student_profiles.user_id')
                ->where('users.is_deleted', false)
                ->where('users.role', 'student')
                ->where('users.id', $request->id)
                ->first();


            if (!$users) {

                return response()->json([
                    'status' => false,
                    'message' => 'Candidate not found'
                ]);
            }
            return response()->json([
                'status' => true,
                'data' =>  $users
            ]);
        } catch (\Exception $e) {
            Log::error('Get candidateDetail Error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Unexpected server error..!'
            ]);
        }
    }


    public function downloadFile(Request $request)
    {
        try {

            $path = $request->path;

            if (!$path) {

                return response()->json([
                    'status' => false,
                    'message' => 'File path required'
                ]);
            }

            $fullPath = storage_path('app/public/' . $path);

            if (!file_exists($fullPath)) {

                return response()->json([
                    'status' => false,
                    'message' => 'File not found'
                ]);
            }

            return response()->download($fullPath);
        } catch (\Exception $e) {

            Log::error("Download Error: " . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong'
            ]);
        }
    }
}
