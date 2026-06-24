<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\PayoutDetailsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * User-facing payout details (centralized — used by referral earners and creators).
 * Auth is enforced by the surrounding route group (sets request attribute auth_user).
 */
class PayoutDetailsController extends Controller
{
    /** GET /payout-details — current user's payout profile (display-safe) + presence flag. */
    public function show(Request $request): JsonResponse
    {
        try {
            $user = $request->attributes->get('auth_user');
            if (!$user) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

            return response()->json([
                'status' => true,
                'data'   => [
                    'payout_details' => PayoutDetailsService::getForDisplay((int) $user->id),
                    'has_details'    => PayoutDetailsService::has((int) $user->id),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('PayoutDetailsController@show: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /** POST /payout-details — save UPI or bank details (method-aware validation). */
    public function save(Request $request): JsonResponse
    {
        try {
            $user = $request->attributes->get('auth_user');
            if (!$user) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

            $result = PayoutDetailsService::save((int) $user->id, $request->all());
            if (!$result['ok']) {
                return response()->json(['status' => false, 'message' => $result['message']], 422);
            }

            return response()->json(['status' => true, 'message' => $result['message']]);
        } catch (\Throwable $e) {
            Log::error('PayoutDetailsController@save: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }
}
