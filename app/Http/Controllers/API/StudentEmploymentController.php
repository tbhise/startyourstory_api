<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Employment Status — isolated feature, deliberately separate from
 * Career Status (student_profiles.looking_for / UserController@updateCareerStatus).
 *
 * - student_profiles.employment_status  → quick-lookup flag for admin filtering
 * - student_employment_history          → full record (org, date, designation, source)
 *
 * Neither endpoint touches looking_for, profile_completed, or any existing
 * profile field, so Career Status / profile completion behaviour cannot regress.
 */
class StudentEmploymentController extends Controller
{
    /* ------------------------------------------------------------------ */
    /*  GET /student/employment                                             */
    /*  Returns the flag + the active employment record (if joined).        */
    /* ------------------------------------------------------------------ */
    public function show(Request $request)
    {
        try {
            $user = $request->attributes->get('auth_user');
            if (!$user || $user->role !== 'student') {
                return response()->json([
                    'status'  => false,
                    'message' => 'Only students can access employment status.',
                ], 403);
            }

            $profile = DB::table('student_profiles')
                ->where('user_id', $user->id)
                ->first();

            // Students without a profile row (or pre-migration rows) default to looking.
            $status = $profile->employment_status ?? 'looking';

            $current = null;
            if ($status === 'joined') {
                $row = DB::table('student_employment_history')
                    ->where('user_id', $user->id)
                    ->where('is_current', 1)
                    ->orderByDesc('id')
                    ->first();
                if ($row) {
                    $current = [
                        'firm_id'             => $row->firm_id !== null ? (int) $row->firm_id : null,
                        'organization_name'   => $row->organization_name,
                        'designation'         => $row->designation,
                        'joined_date'         => $row->joined_date,
                        'joined_via_platform' => (bool) $row->joined_via_platform,
                    ];
                }
            }

            return response()->json([
                'status' => true,
                'data'   => [
                    'employment_status' => $status,
                    'current'           => $current,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('StudentEmploymentController@show: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /* ------------------------------------------------------------------ */
    /*  POST /student/employment                                            */
    /*  employment_status=looking → clear flag + close any current record.  */
    /*  employment_status=joined  → require org + date, insert history row. */
    /* ------------------------------------------------------------------ */
    public function update(Request $request)
    {
        try {
            $user = $request->attributes->get('auth_user');
            if (!$user || $user->role !== 'student') {
                return response()->json([
                    'status'  => false,
                    'message' => 'Only students can update employment status.',
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'employment_status'   => 'required|string|in:looking,joined',
                // firm_profiles.id when picked from the FirmSelector dropdown
                // (searchFirms API); null when the student typed a custom name.
                'firm_id'             => 'nullable|integer|exists:firm_profiles,id',
                // joined_date may be in the future (offer accepted, joining soon).
                'organization_name'   => 'required_if:employment_status,joined|nullable|string|max:255',
                'joined_date'         => 'required_if:employment_status,joined|nullable|date',
                'designation'         => 'nullable|string|max:255',
                'joined_via_platform' => 'required_if:employment_status,joined|nullable|boolean',
            ], [
                'organization_name.required_if'   => 'Please enter the organization name.',
                'joined_date.required_if'         => 'Please select your joining date.',
                'joined_via_platform.required_if' => 'Please tell us whether you found this opportunity through StartYourStory.',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status'  => false,
                    'message' => $validator->errors()->first(),
                ]);
            }

            $profile = DB::table('student_profiles')
                ->where('user_id', $user->id)
                ->first();
            if (!$profile) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Please complete your profile before updating your employment status.',
                ]);
            }

            $newStatus = $request->employment_status;

            DB::beginTransaction();

            // Only employment_status is written — no other profile column is touched.
            DB::table('student_profiles')
                ->where('user_id', $user->id)
                ->update([
                    'employment_status' => $newStatus,
                    'updated_at'        => now(),
                ]);

            if ($newStatus === 'looking') {
                // Keep the history rows for admin analytics; just end the active one.
                DB::table('student_employment_history')
                    ->where('user_id', $user->id)
                    ->where('is_current', 1)
                    ->update(['is_current' => 0, 'updated_at' => now()]);
            } else {
                DB::table('student_employment_history')
                    ->where('user_id', $user->id)
                    ->where('is_current', 1)
                    ->update(['is_current' => 0, 'updated_at' => now()]);

                DB::table('student_employment_history')->insert([
                    'user_id'             => $user->id,
                    'firm_id'             => $request->filled('firm_id') ? (int) $request->firm_id : null,
                    'organization_name'   => trim($request->organization_name),
                    'designation'         => $request->filled('designation') ? trim($request->designation) : null,
                    'joined_date'         => $request->joined_date,
                    'joined_via_platform' => (bool) $request->joined_via_platform,
                    'is_current'          => 1,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);
            }

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => $newStatus === 'joined'
                    ? 'Employment details saved successfully'
                    : 'Employment status updated successfully',
                'data'    => [
                    'employment_status' => $newStatus,
                    'current'           => $newStatus === 'joined' ? [
                        'firm_id'             => $request->filled('firm_id') ? (int) $request->firm_id : null,
                        'organization_name'   => trim($request->organization_name),
                        'designation'         => $request->filled('designation') ? trim($request->designation) : null,
                        'joined_date'         => $request->joined_date,
                        'joined_via_platform' => (bool) $request->joined_via_platform,
                    ] : null,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('StudentEmploymentController@update: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }
}
