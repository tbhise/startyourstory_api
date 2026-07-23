<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGateway;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PhonePeGateway implements PaymentGateway
{
    private string $merchantId;
    private string $clientId;
    private string $clientSecret;
    private int    $clientVersion;
    private string $baseUrl;
    private string $webhookUsername;
    private string $webhookPassword;

    public function __construct()
    {
        $this->merchantId      = config('services.phonepe.merchant_id');
        $this->clientId        = config('services.phonepe.client_id');
        $this->clientSecret    = config('services.phonepe.client_secret');
        $this->clientVersion   = (int) config('services.phonepe.client_version', 1);
        $this->baseUrl         = rtrim(config('services.phonepe.base_url', 'https://api-preprod.phonepe.com/apis/pg-sandbox'), '/');
        $this->webhookUsername = config('services.phonepe.webhook_username', '');
        $this->webhookPassword = config('services.phonepe.webhook_password', '');
    }

    /**
     * Fetch (or refresh) an OAuth2 access token from PhonePe.
     * Token is cached until 60 seconds before its expiry.
     */
    private function getAccessToken(): string
    {
        $cacheKey = 'phonepe_access_token_' . md5($this->clientId);

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $response = Http::asForm()->post($this->baseUrl . '/v1/oauth/token', [
            'client_id'      => $this->clientId,
            'client_secret'  => $this->clientSecret,
            'client_version' => $this->clientVersion,
            'grant_type'     => 'client_credentials',
        ]);

        $data = $response->json();

        if (empty($data['access_token'])) {
            throw new RuntimeException('PhonePe OAuth token request failed: ' . ($data['message'] ?? $response->body()));
        }

        // Cache until 60s before expiry; fall back to 10 minutes if field absent
        $expiresAt = $data['expires_at'] ?? (time() + 600);
        $ttl       = max(1, $expiresAt - time() - 60);

        Cache::put($cacheKey, $data['access_token'], $ttl);

        return $data['access_token'];
    }

    /**
     * Initiate a PhonePe PG_CHECKOUT payment (redirect flow).
     *
     * Returns:
     *   order_id     → merchantOrderId (our reference for status checks)
     *   amount       → amount in paise
     *   currency     → 'INR'
     *   redirect_url → PhonePe checkout URL to redirect the user to
     *   raw          → full PhonePe API response
     */
    public function createOrder(float $amount, string $receipt, array $notes = []): array
    {
        $token = $this->getAccessToken();

        $payload = [
            'merchantOrderId' => $receipt,
            'amount'          => (int) round($amount * 100),
            'expireAfter'     => 1200,
            'paymentFlow'     => [
                'type'         => 'PG_CHECKOUT',
                'merchantUrls' => [
                    'redirectUrl' => $notes['redirect_url'] ?? '',
                ],
            ],
        ];

        $response = Http::withHeaders([
            'Content-Type'  => 'application/json',
            'Authorization' => 'O-Bearer ' . $token,
            'X-MERCHANT-ID' => $this->merchantId,
        ])->post($this->baseUrl . '/checkout/v2/pay', $payload);

        $data = $response->json();

        if ($response->failed() || empty($data['redirectUrl'])) {
            throw new RuntimeException('PhonePe order creation failed: ' . ($data['message'] ?? $response->body()));
        }

        return [
            'gateway'            => $this->name(),
            'order_id'           => $receipt,
            'amount'             => (int) round($amount * 100),
            'currency'           => 'INR',
            'redirect_url'       => $data['redirectUrl'],
            'payment_session_id' => null,
            'mode'               => null,
            'raw'                => $data,
        ];
    }

    public function name(): string
    {
        return 'phonepe';
    }

    /**
     * Server-side status fetch, NORMALIZED to the gateway-agnostic shape.
     * Wraps the existing fetchPayment() status call.
     */
    public function verifyPayment(string $orderId): array
    {
        $data = $this->fetchPayment($orderId);

        return $this->normalize($data, $orderId);
    }

    /**
     * Verify the webhook Authorization header (fail closed) and return the
     * NORMALIZED payload. $rawBody is PhonePe's JSON body; $headers are the
     * request headers (case-insensitive lookup for 'authorization').
     */
    public function parseWebhook(string $rawBody, array $headers): array
    {
        $authorization = $this->header($headers, 'authorization');

        if (! $this->verifySignature(['authorization' => $authorization])) {
            throw new RuntimeException('PhonePe webhook signature verification failed');
        }

        $body    = json_decode($rawBody, true) ?: [];
        $payload = $body['payload'] ?? [];
        $orderId = $payload['merchantOrderId'] ?? '';

        return $this->normalize($payload, $orderId);
    }

    /**
     * Refund-ready: PhonePe v2 refund. Not wired to any business flow yet.
     */
    public function refund(string $orderId, string $refundId, float $amount): array
    {
        $token = $this->getAccessToken();

        $response = Http::withHeaders([
            'Content-Type'  => 'application/json',
            'Authorization' => 'O-Bearer ' . $token,
            'X-MERCHANT-ID' => $this->merchantId,
        ])->post($this->baseUrl . '/payments/v2/refund', [
            'merchantRefundId'        => $refundId,
            'originalMerchantOrderId' => $orderId,
            'amount'                  => (int) round($amount * 100),
        ]);

        return $response->json() ?? [];
    }

    /**
     * Normalize a PhonePe status response OR webhook payload (both carry
     * `state`, `amount` (paise) and `paymentDetails[0].transactionId`) into the
     * gateway-agnostic result shape.
     */
    private function normalize(array $data, string $orderId): array
    {
        $state = strtoupper((string) ($data['state'] ?? ''));

        $status = match ($state) {
            'COMPLETED' => 'paid',
            'PENDING'   => 'pending',
            default     => 'failed',
        };

        return [
            'status'             => $status,
            'gateway_payment_id' => $data['paymentDetails'][0]['transactionId'] ?? null,
            'amount'             => isset($data['amount']) ? (int) $data['amount'] : null,
            'currency'           => 'INR',
            'order_id'           => $orderId,
            'raw'                => $data,
        ];
    }

    /** Case-insensitive header lookup from a plain headers array. */
    private function header(array $headers, string $name): string
    {
        foreach ($headers as $key => $value) {
            if (strtolower((string) $key) === strtolower($name)) {
                return is_array($value) ? (string) ($value[0] ?? '') : (string) $value;
            }
        }

        return '';
    }

    /**
     * Verify the webhook Authorization header from PhonePe.
     *
     * PhonePe v2 sends: Authorization: SHA256(username:password)
     * $attributes must contain 'authorization' key with the raw header value.
     */
    public function verifySignature(array $attributes): bool
    {
        // FAIL CLOSED: if webhook credentials are not configured we cannot prove the
        // request came from PhonePe, so reject it. (Previously this returned true,
        // which let unauthenticated/forged webhooks through.)
        if (empty($this->webhookUsername) || empty($this->webhookPassword)) {
            return false;
        }

        $received = trim((string) ($attributes['authorization'] ?? ''));
        if ($received === '') {
            return false;
        }

        // PhonePe v2: Authorization = SHA256(username:password). Compare in
        // constant time and case-insensitively (hashes are hex).
        $expected = hash('sha256', $this->webhookUsername . ':' . $this->webhookPassword);

        return hash_equals(strtolower($expected), strtolower($received));
    }

    /**
     * Check payment status from PhonePe order status API.
     *
     * $paymentId = merchantOrderId used in createOrder()
     */
    public function fetchPayment(string $paymentId): array
    {
        $token = $this->getAccessToken();

        $response = Http::withHeaders([
            'Content-Type'  => 'application/json',
            'Authorization' => 'O-Bearer ' . $token,
            'X-MERCHANT-ID' => $this->merchantId,
        ])->get($this->baseUrl . '/checkout/v2/order/' . $paymentId . '/status');

        return $response->json() ?? [];
    }
}
