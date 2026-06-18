<?php

namespace App\Services\Notifications;

use App\Models\AdminFcmToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Firebase Cloud Messaging sender (HTTP v1 API).
 *
 * Uses the service-account OAuth2 flow (RS256 JWT → access token) implemented
 * with native openssl — no extra Composer dependency. Everything is config-driven
 * via config('services.fcm'); when credentials are absent the service is a safe
 * NO-OP (logs a debug line and returns) so notification creation never breaks.
 *
 * Security: only admin device tokens (admin_fcm_tokens) are ever targeted, so
 * students/firms can never receive admin pushes.
 */
class FcmService
{
    private const SCOPE      = 'https://www.googleapis.com/auth/firebase.messaging';
    private const TOKEN_URI  = 'https://oauth2.googleapis.com/token';
    private const CACHE_KEY  = 'fcm_access_token';

    public static function isConfigured(): bool
    {
        $c = config('services.fcm');
        return !empty($c['project_id']) && !empty($c['client_email']) && !empty($c['private_key']);
    }

    /**
     * Fan a notification out to every registered admin device.
     * Non-throwing — a delivery failure must never break the host flow.
     */
    public static function sendToAllAdmins(
        string $title,
        string $body,
        ?string $actionUrl = null,
        array $data = []
    ): void {
        try {
            if (!self::isConfigured()) {
                Log::debug('FcmService: not configured — skipping push.');
                return;
            }

            $tokens = AdminFcmToken::pluck('token', 'id'); // [id => token]
            if ($tokens->isEmpty()) return;

            $accessToken = self::accessToken();
            if (!$accessToken) return;

            $projectId = config('services.fcm.project_id');
            $endpoint  = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

            // Absolute click-through link on the frontend (FCM needs a full URL).
            $path        = $actionUrl ?: '/admin/notifications';
            $frontendUrl = rtrim((string) config('services.fcm.frontend_url'), '/');
            $link        = $frontendUrl ? $frontendUrl . $path : $path;

            // String-only data payload (FCM requirement).
            $dataPayload = array_map('strval', array_merge($data, [
                'action_url' => (string) $path,
            ]));

            foreach ($tokens as $id => $token) {
                $message = [
                    'message' => [
                        'token'        => $token,
                        'notification' => ['title' => $title, 'body' => $body],
                        'data'         => $dataPayload,
                        'webpush'      => [
                            'fcm_options' => ['link' => $link],
                            'notification' => ['icon' => '/android-chrome-192x192.png'],
                        ],
                    ],
                ];

                $resp = Http::withToken($accessToken)
                    ->acceptJson()
                    ->post($endpoint, $message);

                // Prune dead tokens so the table self-heals.
                if (in_array($resp->status(), [404, 403], true) || $resp->json('error.status') === 'UNREGISTERED') {
                    AdminFcmToken::where('id', $id)->delete();
                } elseif ($resp->failed()) {
                    Log::warning('FcmService: send failed', ['id' => $id, 'status' => $resp->status(), 'body' => $resp->body()]);
                }
            }
        } catch (\Throwable $e) {
            Log::error('FcmService@sendToAllAdmins: ' . $e->getMessage());
        }
    }

    /**
     * Mint (and cache) an OAuth2 access token from the service-account key.
     */
    private static function accessToken(): ?string
    {
        $cached = Cache::get(self::CACHE_KEY);
        if ($cached) return $cached;

        try {
            $clientEmail = config('services.fcm.client_email');
            $privateKey  = str_replace('\\n', "\n", (string) config('services.fcm.private_key'));

            $now = time();
            $header = self::b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $claim  = self::b64(json_encode([
                'iss'   => $clientEmail,
                'scope' => self::SCOPE,
                'aud'   => self::TOKEN_URI,
                'iat'   => $now,
                'exp'   => $now + 3600,
            ]));

            $signingInput = $header . '.' . $claim;
            $signature    = '';
            if (!openssl_sign($signingInput, $signature, $privateKey, 'sha256WithRSAEncryption')) {
                Log::error('FcmService: JWT signing failed (check FCM_PRIVATE_KEY).');
                return null;
            }
            $jwt = $signingInput . '.' . self::b64($signature);

            $resp = Http::asForm()->post(self::TOKEN_URI, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]);

            $accessToken = $resp->json('access_token');
            if (!$accessToken) {
                Log::error('FcmService: token exchange failed', ['body' => $resp->body()]);
                return null;
            }

            $ttl = max(60, ((int) $resp->json('expires_in', 3600)) - 60);
            Cache::put(self::CACHE_KEY, $accessToken, $ttl);

            return $accessToken;
        } catch (\Throwable $e) {
            Log::error('FcmService@accessToken: ' . $e->getMessage());
            return null;
        }
    }

    private static function b64(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
