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
                ], 400);
            }

            $fullPath = storage_path('app/public/' . $path);

            if (!file_exists($fullPath)) {

                return response()->json([
                    'status' => false,
                    'message' => 'File not found'
                ], 404);
            }

            return response()->download($fullPath);
        } catch (\Exception $e) {

            Log::error("Download Error: " . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong'
            ], 500);
        }
    }




}
