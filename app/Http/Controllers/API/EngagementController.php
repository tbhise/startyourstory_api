<?php

namespace App\Http\Controllers\API;

use App\Helpers\AuthHelper;
use App\Http\Controllers\Controller;
use App\Services\Engagement\InAppCampaignService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Public (authenticated user) side of the Engagement Hub — the "prompt engine".
 * The dashboard asks for the active campaign for a trigger; the backend decides
 * which one (audience + frequency + priority) and returns it fully described so
 * the frontend can render it generically. No popup logic lives on the frontend.
 */
class EngagementController extends Controller
{
    public function __construct(private InAppCampaignService $engine = new InAppCampaignService()) {}

    /** GET /engagement/active?trigger=dashboard_login */
    public function active(Request $request)
    {
        try {
            $user = AuthHelper::resolveUser($request);
            if (!$user) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }

            $trigger = (string) $request->input('trigger', 'dashboard_login');

            // Client-only capability signals (browser notification permission +
            // PWA install state). The backend cannot observe these on its own, so
            // the frontend reports them for type-aware eligibility.
            $caps = [
                'notif' => (string) $request->input('notif', ''),
                'pwa'   => (string) $request->input('pwa', ''),
            ];

            $campaign = $this->engine->resolveForUser($user, $trigger, $caps);

            return response()->json([
                'status' => true,
                'data'   => ['campaign' => $campaign ? $this->format($campaign) : null],
            ]);
        } catch (\Throwable $e) {
            // Never break a dashboard load because of the popup engine.
            Log::warning('Engagement@active failed: ' . $e->getMessage());
            return response()->json(['status' => true, 'data' => ['campaign' => null]]);
        }
    }

    /** POST /engagement/{id}/event  body: { action } */
    public function event(Request $request, $id)
    {
        try {
            $user = AuthHelper::resolveUser($request);
            if (!$user) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }
            $action = (string) $request->input('action', '');
            $ok = $this->engine->logEvent((int) $id, (int) $user->id, $action);
            if (!$ok) {
                return response()->json(['status' => false, 'message' => 'Invalid action'], 422);
            }
            return response()->json(['status' => true]);
        } catch (\Throwable $e) {
            Log::warning('Engagement@event failed: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Could not record event'], 500);
        }
    }

    private function format(object $c): array
    {
        return [
            'id'        => (string) $c->id,
            'type'      => $c->type,
            'title'     => $c->title,
            'subtitle'  => $c->subtitle,
            'image'     => $c->image ? asset('storage/' . $c->image) : null,
            'primary'   => $c->primary_btn_label ? [
                'label'  => $c->primary_btn_label,
                'action' => $c->primary_btn_action ?: 'dismiss',
                'value'  => $c->primary_btn_value,
            ] : null,
            'secondary' => $c->secondary_btn_label ? [
                'label'  => $c->secondary_btn_label,
                'action' => $c->secondary_btn_action ?: 'dismiss',
                'value'  => $c->secondary_btn_value,
            ] : null,
        ];
    }
}
