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

            $users = DB::table('users')
                ->select('users.id', 'users.name', 'users.email', 'users.mobile', 'student_profiles.looking_for', 'student_profiles.srn', 'student_profiles.address', 'student_profiles.gender', 'student_profiles.passing_month', 'student_profiles.ca_status', 'student_profiles.articleship_status', 'student_profiles.preferred_location', 'student_profiles.it_oc_status', 'student_profiles.exposure_type', 'student_profiles.core_department', 'student_profiles.attempts', 'student_profiles.linkedin_url', 'student_profiles.portfolio_url', 'student_profiles.marksheet_path', 'student_profiles.resume_path', 'student_profiles.created_at', 'student_profiles.updated_at')
                ->leftJoin('student_profiles', 'users.id', 'student_profiles.user_id')
                ->where('users.is_deleted', false)
                ->where('users.role', 'student')
                ->get();

            return response()->json([
                'status' => true,
                'data' =>  $users
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
