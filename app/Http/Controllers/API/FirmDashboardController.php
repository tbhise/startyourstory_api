<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Helpers\SubscriptionHelper;

class FirmDashboardController extends Controller
{

    public function getCandidates(Request $request)
    {
        try {
            $authUser = $request->attributes->get('auth_user');
            $firm = DB::table('firm_profiles')->where('user_id', $authUser->id)->first();
            $pageSize = $request->page_size ?? 12;
            $query = DB::table('users')
                ->select(
                    'users.id',
                    'users.name',
                    'student_profiles.looking_for',
                    'student_profiles.address',
                    'student_profiles.gender',
                    'student_profiles.passing_month',
                    'student_profiles.exposure_type',
                    'student_profiles.core_department',
                    'student_profiles.resume_path',
                    'student_profiles.registration_type',
                    'student_profiles.preferred_categories',
                    'student_profiles.availability_status',
                    'student_profiles.experience_years',
                    'users.profile_image',
                    DB::raw("
                    CASE WHEN EXISTS (SELECT 1 FROM recruiter_actions WHERE recruiter_actions.student_id = users.id AND recruiter_actions.firm_id = {$firm->id}
                            AND recruiter_actions.action_type = 'candidate_saved') THEN 1 ELSE 0 END as is_saved"),
                    DB::raw('IF(users.email_verified_at IS NOT NULL, 1, 0) as is_verified')
                )
                ->leftJoin('student_profiles', 'users.id', '=', 'student_profiles.user_id')
                ->where('users.is_deleted', false)
                // Hide students who have requested account deletion (30-day grace).
                // Reversible: a login clears deletion_requested_at and they reappear.
                ->whereNull('users.deletion_requested_at')
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
                        ->orWhere('student_profiles.preferred_location', 'like', "%{$search}%")
                        ->orWhere('student_profiles.core_department', 'like', "%{$search}%");
                });
            }
            /*
        |--------------------------------------------------------------------------
        | City Filter — client-driven (empty = show all candidates)
        |--------------------------------------------------------------------------
        */
            $cities = $request->input('cities', []);
            if (!empty($cities)) {
                $query->where(function ($q) use ($cities) {
                    foreach ($cities as $city) {
                        $q->orWhereJsonContains('student_profiles.preferred_location', $city);
                    }
                });
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
                $query->whereIn(
                    'student_profiles.gender',
                    $genders
                );
            }
            /*
        |--------------------------------------------------------------------------
        | Department Filter
        |--------------------------------------------------------------------------
        */
            if ($request->filled('departments')) {
                $departments = is_array($request->departments)
                    ? $request->departments
                    : [$request->departments];
                $query->whereIn(
                    'student_profiles.core_department',
                    $departments
                );
            }

            /*
        |--------------------------------------------------------------------------
        | Creator Category Filter
        |--------------------------------------------------------------------------
        */
            if ($request->filled('categories')) {
                $categories = is_array($request->categories)
                    ? $request->categories
                    : [$request->categories];
                $query->where(function ($q) use ($categories) {
                    foreach ($categories as $cat) {
                        $q->orWhereJsonContains('student_profiles.preferred_categories', $cat);
                    }
                });
            }
            /*
        |--------------------------------------------------------------------------
        | Registration type
        |--------------------------------------------------------------------------
        */
            if ($request->filled('registration_type')) {
                $query->where(
                    'student_profiles.registration_type',
                    strtolower($request->registration_type)
                );
            }
            /*
        |--------------------------------------------------------------------------
        | Registered For
        |--------------------------------------------------------------------------
        */
            if ($request->filled('registered_for')) {
                $registeredFor = is_array($request->registered_for)
                    ? $request->registered_for
                    : [$request->registered_for];
                $query->where(function ($q) use ($registeredFor) {
                    $q->whereIn('student_profiles.looking_for', $registeredFor);
                    if (in_array('creator', $registeredFor)) {
                        $q->orWhere('student_profiles.is_creator', true);
                    }
                });
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
                $query->whereIn(
                    'student_profiles.passing_month',
                    $passingMonths
                );
            }
            /*
        |--------------------------------------------------------------------------
        | Saved Only
        |--------------------------------------------------------------------------
        */
            if ($request->saved_only) {
                $query->whereExists(function ($q) use ($firm) {
                    $q->select(DB::raw(1))
                        ->from('recruiter_actions')
                        ->whereColumn(
                            'recruiter_actions.student_id',
                            'users.id'
                        )
                        ->where(
                            'recruiter_actions.firm_id',
                            $firm->id
                        )
                        ->where(
                            'recruiter_actions.action_type',
                            'candidate_saved'
                        );
                });
            }
            /*
        |--------------------------------------------------------------------------
        | Sorting
        |--------------------------------------------------------------------------
        */
            switch ($request->sort) {
                case 'oldest':
                    $query->orderBy(
                        'student_profiles.created_at',
                        'asc'
                    );
                    break;
                case 'name':
                    $query->orderBy(
                        'users.name',
                        'asc'
                    );
                    break;
                default:
                    $query->orderBy(
                        'student_profiles.created_at',
                        'desc'
                    );
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
                    'students' =>
                    $users->items(),
                    'current_page' =>
                    $users->currentPage(),
                    'last_page' =>
                    $users->lastPage(),
                    'next_page_url' =>
                    $users->nextPageUrl(),
                    'total' =>
                    $users->total(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error(
                'Get Candidates Error: ' .
                    $e->getMessage()
            );
            return response()->json([
                'status' => false,
                'message' => 'Unexpected server error..!'
            ]);
        }
    }
    public function candidateDetail(Request $request)
    {
        try {
            /*
        |--------------------------------------------------------------------------
        | Auth User
        |--------------------------------------------------------------------------
        */
            $authUser =
                $request->attributes->get(
                    'auth_user'
                );
            /*
        |--------------------------------------------------------------------------
        | Firm Profile
        |--------------------------------------------------------------------------
        */
            $firm = DB::table('firm_profiles')
                ->where(
                    'user_id',
                    $authUser->id
                )
                ->first();
            /*
        |--------------------------------------------------------------------------
        | Premium Status
        |--------------------------------------------------------------------------
        */
            $isPremium = false;
            if ($firm) {
                $isPremium =
                    SubscriptionHelper::isPremiumFirm(
                        $firm->id
                    );
            }
            /*
        |--------------------------------------------------------------------------
        | Candidate
        |--------------------------------------------------------------------------
        */
            $users = DB::table('users')
                ->leftJoin(
                    'student_profiles',
                    'users.id',
                    '=',
                    'student_profiles.user_id'
                )
                ->where(
                    'users.is_deleted',
                    false
                )
                ->where(
                    'users.role',
                    'student'
                )
                ->where(
                    'users.id',
                    $request->id
                )
                ->first();
            /*
        |--------------------------------------------------------------------------
        | Not Found
        |--------------------------------------------------------------------------
        */
            if (!$users) {
                return response()->json([
                    'status' => false,
                    'message' =>
                    'Candidate not found'
                ]);
            }
            /**
             * Is Shortlisted
             *
             */
            $is_shortlisted =  DB::table('recruiter_actions')->where('firm_id', $firm->id)->where('student_id', $request->id)->where('action_status', 'shortlisted')->exists();
            $is_reported =  DB::table('reported_profiles')->where('reported_by', $firm->user_id)->where('student_id', $request->id)->exists();

            /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */
            $data = (array) $users;
            unset(
                $data['password'],
                $data['api_token'],
                $data['token_expires_at'],
                $data['is_deleted'],
                $data['referred_by'],
                $data['user_id']
            );
            if (isset($data['preferred_categories']) && is_string($data['preferred_categories'])) {
                $data['preferred_categories'] = json_decode($data['preferred_categories']) ?? [];
            }

            return response()->json([
                'status' => true,
                'data' => [
                    ...$data,
                    'profile_image' => $users->profile_image
                        ? asset('storage/' . $users->profile_image)
                        : null,
                    'is_premium' => $isPremium,
                    'is_shortlisted' => $is_shortlisted ? 'Shortlisted' : null,
                    'is_reported' => $is_reported,
                    'is_verified' => !empty($users->email_verified_at),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error(
                'Get candidateDetail Error: '
                    . $e->getMessage()
            );
            return response()->json([
                'status' => false,
                'message' =>
                'Unexpected server error..!'
            ]);
        }
    }
    public function downloadFile(Request $request)
    {
        try {
            $path = $request->path;
            $type = $request->type;
            $studentId = $request->student_id;
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
                ], 404);
            }
            $authUser = $request->attributes->get('auth_user');
            $firm = DB::table('firm_profiles')
                ->where('user_id', $authUser->id)->first();
            if ($firm && $studentId) {
                $message = '';
                if ($type === 'resume') {
                    $message =
                        $firm->firm_name
                        . ' downloaded your resume.';
                }
                if ($type === 'marksheet') {
                    $message =
                        $firm->firm_name
                        . ' downloaded your marksheet.';
                }
                DB::table('recruiter_actions')
                    ->insert([
                        'student_id' => $studentId,
                        'firm_id' => $firm->id,
                        'job_id' => null,
                        'application_id' => null,
                        'visible_to' => 'student',
                        'action_type' => $type . '_downloaded',
                        'message' => $message,
                        'created_at' => now(),
                        'updated_at' => now(),
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



    public function getNotifications(Request $request)
    {
        try {

            $authUser = $request->attributes->get('auth_user');
            // return $authUser;
            $notifications = DB::table('notifications')
                ->where('user_id', $authUser->id)
                ->orderBy('created_at', 'desc')
                ->limit(25)
                ->get();

            return response()->json([
                'status' => true,
                'data' => $notifications
            ]);
        } catch (\Exception $e) {
            Log::error(
                'Get Notifications Error: ' .
                    $e->getMessage()
            );
            return response()->json([
                'status' => false,
                'message' => 'Unexpected server error'
            ]);
        }
    }
}
