<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\AdminActivityLogger;
use App\Services\SystemSettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Admin CRUD for dynamic Platform Settings (system_settings). Auth follows the
 * existing admin pattern: admin_token cookie -> admin_users.
 */
class AdminSystemSettingController extends Controller
{
    private function getAdmin(Request $request): ?object
    {
        $token = $request->cookie('admin_token');
        if (!$token) return null;
        return DB::table('admin_users')
            ->where('api_token', $token)
            ->where('is_active', true)
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | GET /admin/system-settings — all settings grouped by category
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        $admin = $this->getAdmin($request);
        if (!$admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

        try {
            $rows = SystemSetting::orderBy('category')->orderBy('id')->get();

            $grouped = [];
            foreach ($rows as $r) {
                $grouped[$r->category][] = [
                    'key'         => $r->setting_key,
                    'value'       => $r->setting_value,
                    'type'        => $r->setting_type,
                    'title'       => $r->title,
                    'description' => $r->description,
                    'category'    => $r->category,
                    'is_editable' => (bool) $r->is_editable,
                ];
            }

            return response()->json(['status' => true, 'data' => ['groups' => $grouped]]);
        } catch (\Throwable $e) {
            Log::error('AdminSystemSettingController@index: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /admin/system-settings/{key} — update one setting (validated + audited)
    |--------------------------------------------------------------------------
    */
    public function update(Request $request, string $key)
    {
        $admin = $this->getAdmin($request);
        if (!$admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

        try {
            $setting = SystemSetting::where('setting_key', $key)->first();
            if (!$setting) {
                return response()->json(['status' => false, 'message' => 'Unknown setting.'], 404);
            }
            if (!$setting->is_editable) {
                return response()->json(['status' => false, 'message' => 'This setting is not editable.'], 422);
            }

            // Type-aware validation. Numeric settings: no negatives, zero allowed.
            $rules = match ($setting->setting_type) {
                'integer' => ['value' => 'required|integer|min:0'],
                'decimal' => ['value' => 'required|numeric|min:0'],
                'boolean' => ['value' => 'required|in:0,1,true,false'],
                default   => ['value' => 'required|string|max:1000'],
            };
            $messages = [
                'value.min'      => 'Value cannot be negative.',
                'value.required' => 'A value is required.',
                'value.integer'  => 'Value must be a whole number.',
                'value.numeric'  => 'Value must be a number.',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
            }

            $newValue = SystemSettingService::set($key, $request->input('value'), $admin);

            $isPayment = str_starts_with($key, 'payment_');
            AdminActivityLogger::log($admin, $isPayment ? AdminActivityLogger::PAYMENT_SETTINGS_UPDATED : AdminActivityLogger::PLATFORM_SETTINGS_UPDATED, 'system_setting', $key, "Updated system setting '{$key}'.", $request);

            return response()->json([
                'status'  => true,
                'message' => 'Setting updated.',
                'data'    => ['key' => $key, 'value' => $newValue],
            ]);
        } catch (\Throwable $e) {
            Log::error('AdminSystemSettingController@update: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /admin/system-settings/audit — recent change history
    |--------------------------------------------------------------------------
    */
    public function audit(Request $request)
    {
        $admin = $this->getAdmin($request);
        if (!$admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

        try {
            $logs = DB::table('system_setting_audits')
                ->orderByDesc('id')
                ->limit(100)
                ->get();

            return response()->json(['status' => true, 'data' => ['audits' => $logs]]);
        } catch (\Throwable $e) {
            Log::error('AdminSystemSettingController@audit: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }
}
