<?php

namespace App\Contracts;

/**
 * Gateway-agnostic payment contract.
 *
 * Every provider (PhonePe, Cashfree, future Razorpay …) implements this. The
 * settlement layer only ever sees the NORMALIZED shapes returned by
 * verifyPayment()/parseWebhook(), so business logic is fully decoupled from any
 * one gateway's request/response format.
 *
 * Normalized result shape (returned by verifyPayment() and parseWebhook()):
 *   [
 *     'status'             => 'paid' | 'failed' | 'pending',
 *     'gateway_payment_id' => string|null,   // provider's own txn/payment id
 *     'amount'             => int|null,       // amount in paise (for verification)
 *     'currency'           => string,         // e.g. 'INR'
 *     'order_id'           => string,         // our merchant order id (receipt)
 *     'raw'                => array,          // full provider payload
 *   ]
 */
interface PaymentGateway
{
    /**
     * Create a payment order / hosted-checkout session.
     *
     * @param  float  $amount   Amount in INR (not paise)
     * @param  string $receipt  Unique merchant order id (idempotency key)
     * @param  array  $notes    Metadata: redirect_url, callback_url, customer_* …
     * @return array {
     *   'gateway'            => string,      // this gateway's name
     *   'order_id'           => string,      // = $receipt
     *   'amount'             => int,         // paise
     *   'currency'           => string,
     *   'redirect_url'       => string|null, // hosted page to redirect to (PhonePe)
     *   'payment_session_id' => string|null, // JS-SDK session (Cashfree)
     *   'mode'               => string|null, // 'sandbox'|'production' for the JS SDK
     *   'raw'                => array,
     * }
     */
    public function createOrder(float $amount, string $receipt, array $notes = []): array;

    /**
     * Server-side source of truth: fetch the order's status from the gateway and
     * return it NORMALIZED. Never trusts client/redirect params.
     */
    public function verifyPayment(string $orderId): array;

    /**
     * Verify a webhook's signature (throws on failure) and return the payload
     * NORMALIZED. $headers are the raw request headers; $rawBody is the exact,
     * unparsed request body (required for HMAC verification).
     *
     * @throws \RuntimeException on signature failure or unparseable payload
     */
    public function parseWebhook(string $rawBody, array $headers): array;

    /**
     * Refund-ready: issue a refund against a settled order. No business flow
     * currently calls this — it exists so refunds can be wired without touching
     * the gateway layer.
     *
     * @param  string $orderId   The merchant order id that was paid
     * @param  string $refundId  Unique idempotent refund id
     * @param  float  $amount    Amount in INR to refund
     * @return array  Raw provider response
     */
    public function refund(string $orderId, string $refundId, float $amount): array;

    /** Canonical gateway name (matches the value stored on payment rows). */
    public function name(): string;
}
