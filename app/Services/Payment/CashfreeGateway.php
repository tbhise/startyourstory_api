<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGateway;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Cashfree PG (Orders API, version 2023-08-01).
 *
 * Uses raw Laravel Http to mirror PhonePeGateway's style (no extra Composer
 * dependency). The frontend launches the hosted checkout with the official
 * Cashfree JS SDK using the payment_session_id returned by createOrder().
 *
 * Docs: https://www.cashfree.com/docs/api-reference/payments/latest
 */
class CashfreeGateway implements PaymentGateway
{
    private string $appId;
    private string $secretKey;
    private string $apiVersion;
    private string $baseUrl;
    private string $mode;

    public function __construct()
    {
        // All configuration comes from the environment via config/services.php —
        // no hardcoded credentials, URLs, API version or mode. Values may be
        // empty when Cashfree is not configured; ensureConfigured() guards use.
        $this->appId      = (string) config('services.cashfree.app_id');
        $this->secretKey  = (string) config('services.cashfree.secret_key');
        $this->apiVersion = (string) config('services.cashfree.api_version');
        $this->baseUrl    = rtrim((string) config('services.cashfree.base_url'), '/');
        $this->mode       = (string) config('services.cashfree.mode');
    }

    public function name(): string
    {
        return 'cashfree';
    }

    /**
     * Fail loudly (never silently) if Cashfree is selected without the required
     * environment configuration. The message names the missing variables and
     * contains no secrets. Called before any Cashfree API request.
     */
    private function ensureConfigured(): void
    {
        $missing = [];
        if ($this->appId === '')      $missing[] = 'CASHFREE_APP_ID';
        if ($this->secretKey === '')  $missing[] = 'CASHFREE_SECRET_KEY';
        if ($this->baseUrl === '')    $missing[] = 'CASHFREE_BASE_URL';
        if ($this->apiVersion === '') $missing[] = 'CASHFREE_API_VERSION';

        if ($missing) {
            throw new RuntimeException(
                'Cashfree payment gateway is not configured. Missing environment variable(s): '
                . implode(', ', $missing)
                . '. Set them in your environment, or switch the default gateway back to PhonePe.'
            );
        }
    }

    private function headers(): array
    {
        return [
            'Content-Type'    => 'application/json',
            'x-api-version'   => $this->apiVersion,
            'x-client-id'     => $this->appId,
            'x-client-secret' => $this->secretKey,
        ];
    }

    /**
     * Create a Cashfree order and return its payment_session_id for the JS SDK.
     *
     * $notes may contain: redirect_url, customer_id, customer_phone,
     * customer_email, customer_name.
     */
    public function createOrder(float $amount, string $receipt, array $notes = []): array
    {
        $this->ensureConfigured();

        $orderMeta = [
            // Cashfree substitutes {order_id} back into the return_url.
            'return_url' => $notes['redirect_url'] ?? '',
        ];
        // Per-order S2S webhook: route this order's callback straight to its own
        // domain webhook, so no dashboard webhook registration is required.
        // (Ignored by Cashfree if empty; must be a public HTTPS URL to fire.)
        if (! empty($notes['callback_url'])) {
            $orderMeta['notify_url'] = $notes['callback_url'];
        }

        // Cashfree requires a non-empty phone; treat an empty/whitespace value the
        // same as missing so the placeholder applies (wallet/CA flows without a
        // phone still work — the `??` operator alone would let '' through).
        $phone = trim((string) ($notes['customer_phone'] ?? ''));

        $payload = [
            'order_id'       => $receipt,
            'order_amount'   => round($amount, 2),
            'order_currency' => 'INR',
            'customer_details' => [
                // Cashfree requires a customer_id and a phone; fall back to safe
                // placeholders so wallet/CA flows without a phone still work.
                'customer_id'    => (string) ($notes['customer_id'] ?? ('cust_' . $receipt)),
                'customer_phone' => $phone !== '' ? $phone : '9999999999',
                'customer_email' => (string) ($notes['customer_email'] ?? ''),
                'customer_name'  => (string) ($notes['customer_name'] ?? ''),
            ],
            'order_meta' => $orderMeta,
        ];

        $response = Http::withHeaders($this->headers())
            ->post($this->baseUrl . '/orders', $payload);

        $data = $response->json();

        if ($response->failed() || empty($data['payment_session_id'])) {
            throw new RuntimeException('Cashfree order creation failed: ' . ($data['message'] ?? $response->body()));
        }

        return [
            'gateway'            => $this->name(),
            'order_id'           => $receipt,
            'amount'             => (int) round($amount * 100),
            'currency'           => 'INR',
            'redirect_url'       => null,
            'payment_session_id' => $data['payment_session_id'],
            'mode'               => $this->mode === 'production' ? 'production' : 'sandbox',
            'raw'                => $data,
        ];
    }

    /**
     * Server-side truth: fetch the order (and its payments) and NORMALIZE.
     */
    public function verifyPayment(string $orderId): array
    {
        $this->ensureConfigured();

        $orderResp = Http::withHeaders($this->headers())
            ->get($this->baseUrl . '/orders/' . $orderId);
        $order = $orderResp->json() ?? [];

        // A successful payment id lives on the payments sub-resource.
        $gatewayPaymentId = null;
        $paymentsResp = Http::withHeaders($this->headers())
            ->get($this->baseUrl . '/orders/' . $orderId . '/payments');
        $payments = $paymentsResp->json();
        if (is_array($payments)) {
            foreach ($payments as $p) {
                if (($p['payment_status'] ?? '') === 'SUCCESS') {
                    $gatewayPaymentId = (string) ($p['cf_payment_id'] ?? '');
                    break;
                }
            }
        }

        return $this->normalizeOrder($order, $orderId, $gatewayPaymentId);
    }

    /**
     * Verify the webhook signature (HMAC-SHA256 over `timestamp.rawBody`, base64,
     * constant-time) and NORMALIZE the payload. Fails closed.
     */
    public function parseWebhook(string $rawBody, array $headers): array
    {
        $signature = $this->header($headers, 'x-webhook-signature');
        $timestamp = $this->header($headers, 'x-webhook-timestamp');

        if ($this->secretKey === '' || $signature === '' || $timestamp === '') {
            throw new RuntimeException('Cashfree webhook signature verification failed (missing data)');
        }

        $expected = base64_encode(hash_hmac('sha256', $timestamp . $rawBody, $this->secretKey, true));
        if (! hash_equals($expected, $signature)) {
            throw new RuntimeException('Cashfree webhook signature verification failed');
        }

        $body    = json_decode($rawBody, true) ?: [];
        $order   = $body['data']['order'] ?? [];
        $payment = $body['data']['payment'] ?? [];
        $orderId = (string) ($order['order_id'] ?? '');

        // Map the webhook payment_status → normalized status.
        $paymentStatus = strtoupper((string) ($payment['payment_status'] ?? ''));
        $status = match ($paymentStatus) {
            'SUCCESS' => 'paid',
            'PENDING', 'NOT_ATTEMPTED', 'USER_DROPPED' => 'pending',
            default   => 'failed',
        };

        $amountInr = $payment['payment_amount'] ?? ($order['order_amount'] ?? null);

        return [
            'status'             => $status,
            'gateway_payment_id' => isset($payment['cf_payment_id']) ? (string) $payment['cf_payment_id'] : null,
            'amount'             => $amountInr !== null ? (int) round(((float) $amountInr) * 100) : null,
            'currency'           => (string) ($order['order_currency'] ?? 'INR'),
            'order_id'           => $orderId,
            'raw'                => $body,
        ];
    }

    /**
     * Refund-ready: Cashfree refund against a paid order. Not wired to any
     * business flow yet.
     */
    public function refund(string $orderId, string $refundId, float $amount): array
    {
        $this->ensureConfigured();

        $response = Http::withHeaders($this->headers())
            ->post($this->baseUrl . '/orders/' . $orderId . '/refunds', [
                'refund_amount' => round($amount, 2),
                'refund_id'     => $refundId,
                'refund_note'   => 'Refund for order ' . $orderId,
            ]);

        return $response->json() ?? [];
    }

    /**
     * Normalize a GET /orders/{id} response. order_status='PAID' means the order
     * is fully paid.
     */
    private function normalizeOrder(array $order, string $orderId, ?string $gatewayPaymentId): array
    {
        $orderStatus = strtoupper((string) ($order['order_status'] ?? ''));
        $status = match ($orderStatus) {
            'PAID'    => 'paid',
            'ACTIVE'  => 'pending',
            default   => 'failed',
        };

        $amountInr = $order['order_amount'] ?? null;

        return [
            'status'             => $status,
            'gateway_payment_id' => $gatewayPaymentId,
            'amount'             => $amountInr !== null ? (int) round(((float) $amountInr) * 100) : null,
            'currency'           => (string) ($order['order_currency'] ?? 'INR'),
            'order_id'           => $orderId,
            'raw'                => $order,
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
}
