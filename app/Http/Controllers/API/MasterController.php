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
                    'state_name'
                )
                ->where('is_active', true);

            if ($request->filled('search')) {

                $search = trim($request->search);

                $query->where(function ($q) use ($search) {

                    $q->where('city_name', 'like', "%{$search}%")
                        ->orWhere('state_name', 'like', "%{$search}%");
                });
            }

            $cities = $query
                ->orderBy('city_name')
                ->limit(50)
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
