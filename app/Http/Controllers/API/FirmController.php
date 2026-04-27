<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FirmController extends Controller
{
    public function registerFirm(Request $request)
    {
        DB::beginTransaction();
        try {
            // Log::info($request->all());
            $request->validate([
                'email' => 'required|email|unique:users,email',
                'mobile' => 'required',
                'password' => 'required|min:6|max:10',
                'firmName' => 'required',
                'city' => 'required',
            ]);
            // create user
            $userId = DB::table('users')->insertGetId([
                'name' => $request->firmName,
                'email' => $request->email,
                'mobile' => $request->mobile,
                'password' => bcrypt($request->password),
                'role' => 'firm',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            // create profile
            DB::table('firm_profiles')->insert([
                'user_id' => $userId,
                'firm_name' => $request->firmName,
                'city' => $request->city,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Firm Registration successfull..!'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Firm Registration Error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Firm Registration failed: Server error'
            ]);
        }
    }
}
