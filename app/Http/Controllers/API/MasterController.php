<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasterController extends Controller
{



    public function getCities(Request $request)
    {
        try {

            $query = DB::table('city_master')
                ->select(
                    'id',
                    'city_name',
                    'state_name',
                    DB::raw("CONCAT(city_name, ', ', state_name) as label"),
                )
                ->where('is_active', true);

            if ($request->filled('search')) {

                $search = trim($request->search);

                $query->where(function ($q) use ($search) {
                    $q->Where('city_name', 'like', "%{$search}%");
                });
            }
            if ($request->filled('state_name')) {

                $search = trim($request->state_name);

                $query->where(function ($q) use ($search) {
                    $q->Where('state_name', 'like', "%{$search}%");
                });
            }

            $cities = $query
                ->orderBy('city_name')
                ->get();

            return response()->json([
                'status' => true,
                'data' => $cities
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch cities'
            ]);
        }
    }
}
