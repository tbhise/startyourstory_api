<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Firebase Cloud Messaging sender for STUDENT / FIRM devices (HTTP v1 API).
 *
 * Deliberately a separate class from FcmService (admin push) so the admin
 * pipeline stays 100% isolated — this service only ever reads user_fcm_tokens
 * and can never target admin devices. It shares the same Firebase service
 * account (config('services.fcm')) and OAuth access-token cache, so no new
 * configuration is required.
 *
 * Like FcmService, everything is non-throwing and a safe NO-OP when FCM is
 * unconfigured — a push failure must never break the host flow. Callers
 * should not invoke this synchronously in requests; dispatch SendUserPushJob.
 */
class UserPushService
{
    private const SCOPE     = 'https://www.googleapis.com/auth/firebase.messaging';
    private const TOKEN_URI = 'https://oauth2.googleapis.com/token';
    // Same cache key as FcmService — same service account, one minted token.
    private const CACHE_KEY = 'fcm_access_token';

    public static function isConfigured(): bool
    {
        $c = config('services.fcm');
        return !empty($c['project_id']) && !empty($c['client_email']) && !empty($c['private_key']);
    }

    /**
     * Send a push to every registered device of one user (student or firm).
     * Non-throwing — logs and returns on any failure.
     */
    public static function sendToUser(
        int $userId,
        string $title,
        string $body,
        ?string $actionUrl = null,
        array $data = [],
        ?string $collapseTag = null
    ): void {
        try {
            if (!self::isConfigured()) {
                Log::debug('UserPushService: not configured — skipping push.');
                return;
            }

            $tokens = DB::table('user_fcm_tokens')
                ->where('user_id', $userId)
                ->pluck('token', 'id'); // [id => token]
            if ($tokens->isEmpty()) return;

            $accessToken = self::accessToken();
            if (!$accessToken) return;

            $projectId = config('services.fcm.project_id');
            $endpoint  = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

            // Absolute click-through link on the frontend (FCM needs a full URL).
            $path        = $actionUrl ?: '/';
            $frontendUrl = rtrim((string) config('services.fcm.frontend_url'), '/');
            $link        = $frontendUrl ? $frontendUrl . $path : $path;

            // String-only data payload (FCM requirement).
            $dataPayload = array_map('strval', array_merge($data, [
                'action_url' => (string) $path,
            ]));

            foreach ($tokens as $id => $token) {
                $webpushNotification = ['icon' => '/android-chrome-192x192.png'];
                if ($collapseTag) {
                    // Same tag → the browser REPLACES the previous notification
                    // instead of stacking (e.g. a burst of chat messages shows
                    // as one updating notification, not ten).
                    $webpushNotification['tag'] = $collapseTag;
                }
                $message = [
                    'message' => [
                        'token'        => $token,
                        'notification' => ['title' => $title, 'body' => $body],
                        'data'         => $dataPayload,
                        'webpush'      => [
                            'fcm_options' => ['link' => $link],
                            'notification' => $webpushNotification,
                        ],
                    ],
                ];

                $resp = Http::withToken($accessToken)
                    ->acceptJson()
                    ->post($endpoint, $message);

                // Prune dead tokens so the table self-heals.
                if (in_array($resp->status(), [404, 403], true) || $resp->json('error.status') === 'UNREGISTERED') {
                    DB::table('user_fcm_tokens')->where('id', $id)->delete();
                } elseif ($resp->failed()) {
                    Log::warning('UserPushService: send failed', ['id' => $id, 'status' => $resp->status(), 'body' => $resp->body()]);
                }
            }
        } catch (\Throwable $e) {
            Log::error('UserPushService@sendToUser: ' . $e->getMessage());
        }
    }

    /**
     * Mint (and cache) an OAuth2 access token from the service-account key.
     * Same flow (and cache slot) as FcmService::accessToken — duplicated here
     * instead of shared so the admin class never needs modification.
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
                Log::error('UserPushService: JWT signing failed (check FCM_PRIVATE_KEY).');
                return null;
            }
            $jwt = $signingInput . '.' . self::b64($signature);

            $resp = Http::asForm()->post(self::TOKEN_URI, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]);

            $accessToken = $resp->json('access_token');
            if (!$accessToken) {
                Log::error('UserPushService: token exchange failed', ['body' => $resp->body()]);
                return null;
            }

            $ttl = max(60, ((int) $resp->json('expires_in', 3600)) - 60);
            Cache::put(self::CACHE_KEY, $accessToken, $ttl);

            return $accessToken;
        } catch (\Throwable $e) {
            Log::error('UserPushService@accessToken: ' . $e->getMessage());
            return null;
        }
    }

    private static function b64(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
