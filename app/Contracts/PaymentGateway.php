<?php

namespace App\Contracts;

interface PaymentGateway
{
    /**
     * Create a payment order.
     *
     * @param  float  $amount   Amount in INR (not paise)
     * @param  string $receipt  Unique receipt string for idempotency
     * @param  array  $notes    Optional key-value metadata
     * @return array  ['order_id' => string, 'amount' => int, 'currency' => string, 'raw' => array]
     */
    public function createOrder(float $amount, string $receipt, array $notes = []): array;

    /**
     * Verify that a payment callback signature is authentic.
     *
     * @param  array $attributes  Gateway-specific fields (order_id, payment_id, signature …)
     * @return bool
     */
    public function verifySignature(array $attributes): bool;

    /**
     * Fetch full payment details from the gateway after capture.
     *
     * @param  string $paymentId
     * @return array
     */
    public function fetchPayment(string $paymentId): array;
}
