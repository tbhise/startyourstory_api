<?php

namespace App\Http\Controllers\API;

use App\Helpers\ImageHelper;
use App\Http\Controllers\Controller;
use App\Services\AdminActivityLogger;
use App\Services\SystemSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * Admin-managed MANUAL payment destination details (bank/UPI/QR) for the Premium
 * Subscription page. Stored as `system_settings` rows under the `payment`
 * category. This controller ONLY exposes/manages the destination details — it has
 * nothing to do with PhonePe, plans, pricing, branch discounts, subscription
 * activation, or the verification workflow.
 */
class PaymentSettingsController extends Controller
{
    /** Destination directory (public disk) for the uploaded QR image. */
    private const QR_DIR = 'payment-settings';

    private function getAdmin(Request $request): ?object
    {
        $token = $request->cookie('admin_token');
        if (!$token) return null;
        return DB::table('admin_users')
            ->where('api_token', $token)
            ->where('is_active', true)
            ->first();
    }

    /** Build the public absolute URL for a stored (relative) image path. */
    private function qrUrl(?string $path): string
    {
        if (!$path) return '';
        return url('/storage/' . ltrim($path, '/'));
    }

    /*
    |--------------------------------------------------------------------------
    | GET /payments/instructions  (PUBLIC)
    | Returns the admin-managed manual payment destination details. Plans,
    | pricing and PhonePe are intentionally NOT included here.
    |--------------------------------------------------------------------------
    */
    public function instructions(): JsonResponse
    {
        try {
            $instructions = [
                'account_holder' => (string) SystemSettingService::get('payment_account_holder', ''),
                'bank_name'      => (string) SystemSettingService::get('payment_bank_name', ''),
                'account_number' => (string) SystemSettingService::get('payment_account_number', ''),
                'ifsc_code'      => (string) SystemSettingService::get('payment_ifsc', ''),
                'upi_id'         => (string) SystemSettingService::get('payment_upi_id', ''),
                'qr_image'       => $this->qrUrl((string) SystemSettingService::get('payment_qr_code', '')),
            ];

            return response()->json([
                'status' => true,
                'data'   => ['instructions' => $instructions],
            ]);
        } catch (\Throwable $e) {
            Log::error('PaymentSettingsController@instructions: ' . $e->getMessage());
            // Never crash the payment page — return empty values so the frontend
            // shows its "details unavailable" fallback instead.
            return response()->json([
                'status' => true,
                'data'   => [
                    'instructions' => [
                        'account_holder' => '',
                        'bank_name'      => '',
                        'account_number' => '',
                        'ifsc_code'      => '',
                        'upi_id'         => '',
                        'qr_image'       => '',
                    ],
                ],
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /admin/payment-settings/qr  (ADMIN)
    | Upload or replace the QR code image. Reuses ImageHelper (WebP optimise) and
    | the public disk, exactly like blog featured images.
    |--------------------------------------------------------------------------
    */
    public function uploadQr(Request $request): JsonResponse
    {
        $admin = $this->getAdmin($request);
        if (!$admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

        $validator = Validator::make($request->all(), [
            'qr_code' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ], [
            'qr_code.required' => 'Please choose a QR code image to upload.',
            'qr_code.image'    => 'The QR code must be an image file.',
            'qr_code.mimes'    => 'Allowed formats: JPG, PNG, WEBP.',
            'qr_code.max'      => 'The QR code image must be 5 MB or smaller.',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        try {
            $oldPath = (string) SystemSettingService::get('payment_qr_code', '');

            $newPath = ImageHelper::optimizeToWebp($request->file('qr_code'), self::QR_DIR, 'public');

            // Persist the new path (audited + cache-busted via the service).
            SystemSettingService::set('payment_qr_code', $newPath, $admin);

            // Remove the previous image only after the new one is safely stored.
            if ($oldPath && $oldPath !== $newPath) {
                Storage::disk('public')->delete($oldPath);
            }

            AdminActivityLogger::log($admin, AdminActivityLogger::PAYMENT_SETTINGS_UPDATED, 'payment_setting', 'payment_qr_code', 'Uploaded/replaced the payment QR code image.', $request);

            return response()->json([
                'status'  => true,
                'message' => 'QR code uploaded.',
                'data'    => ['qr_image' => $this->qrUrl($newPath)],
            ]);
        } catch (\Throwable $e) {
            Log::error('PaymentSettingsController@uploadQr: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Failed to upload QR code.'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE /admin/payment-settings/qr  (ADMIN)
    | Remove the QR code image (clears the setting + deletes the file).
    |--------------------------------------------------------------------------
    */
    public function deleteQr(Request $request): JsonResponse
    {
        $admin = $this->getAdmin($request);
        if (!$admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

        try {
            $oldPath = (string) SystemSettingService::get('payment_qr_code', '');

            SystemSettingService::set('payment_qr_code', '', $admin);

            if ($oldPath) {
                Storage::disk('public')->delete($oldPath);
            }

            AdminActivityLogger::log($admin, AdminActivityLogger::PAYMENT_SETTINGS_UPDATED, 'payment_setting', 'payment_qr_code', 'Removed the payment QR code image.', $request);

            return response()->json(['status' => true, 'message' => 'QR code removed.']);
        } catch (\Throwable $e) {
            Log::error('PaymentSettingsController@deleteQr: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Failed to remove QR code.'], 500);
        }
    }
}
