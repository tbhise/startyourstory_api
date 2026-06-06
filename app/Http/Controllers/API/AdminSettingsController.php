<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminSettingsController extends Controller
{
    private function admin(Request $request): ?object
    {
        $token = $request->cookie('admin_token');
        if (!$token) return null;
        return DB::table('admin_users')
            ->where('api_token', $token)
            ->first();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /admin/platform-settings
    // ─────────────────────────────────────────────────────────────────────────

    public function getSettings(Request $request): JsonResponse
    {
        $admin = $this->admin($request);
        if (!$admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

        $rows = DB::table('platform_settings')->get();
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row->key] = $row->value;
        }

        // Ensure defaults for settings we manage here
        if (!isset($settings['show_companies_to_students'])) {
            $settings['show_companies_to_students'] = 'true';
        }
        if (!isset($settings['free_applications_limit'])) {
            $settings['free_applications_limit'] = '3';
        }

        return response()->json(['status' => true, 'data' => $settings]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /admin/platform-settings/{key}
    // ─────────────────────────────────────────────────────────────────────────

    public function updateSetting(Request $request, string $key): JsonResponse
    {
        $admin = $this->admin($request);
        if (!$admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

        $allowed = ['show_companies_to_students', 'free_applications_limit'];
        if (!in_array($key, $allowed)) {
            return response()->json(['status' => false, 'message' => 'Unknown setting key.'], 422);
        }

        $value = (string) $request->input('value', '');

        DB::table('platform_settings')->updateOrInsert(
            ['key' => $key],
            ['value' => $value, 'updated_by' => $admin->id, 'updated_at' => now()]
        );

        return response()->json(['status' => true, 'message' => 'Setting updated.']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /platform-settings  (public — no auth)
    // ─────────────────────────────────────────────────────────────────────────

    public function getPublicSettings(): JsonResponse
    {
        $show = DB::table('platform_settings')
            ->where('key', 'show_companies_to_students')
            ->value('value');

        // Default to true when key not yet seeded
        $showCompanies = ($show === null) ? true : ($show === 'true' || $show === '1');

        return response()->json([
            'status' => true,
            'data'   => ['show_companies_to_students' => $showCompanies],
        ]);
    }
}
