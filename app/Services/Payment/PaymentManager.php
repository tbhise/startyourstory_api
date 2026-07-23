<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGateway;
use App\Services\SystemSettingService;

/**
 * Central entry point for resolving a payment gateway.
 *
 * Resolution rules (see the payment integration plan):
 *   - INITIATE  → active()  : the admin-selected default gateway.
 *   - VERIFY    → gateway() : the gateway stored on the payment row, so a
 *                             pending order still verifies with the gateway it
 *                             was created on even after the admin switches.
 *   - WEBHOOK   → gateway() : each webhook endpoint knows its own gateway.
 *
 * Controllers must go through this manager (or the factory) — never `new`.
 */
class PaymentManager
{
    /** Fallback when the setting is missing, so payments never break. */
    private const DEFAULT_GATEWAY = 'phonepe';

    /** Name of the admin-selected active gateway. */
    public function activeName(): string
    {
        $name = (string) SystemSettingService::get('default_payment_gateway', self::DEFAULT_GATEWAY);

        return $name !== '' ? $name : self::DEFAULT_GATEWAY;
    }

    /** The admin-selected active gateway (used when initiating a new payment). */
    public function active(): PaymentGateway
    {
        return $this->gateway($this->activeName());
    }

    /** A specific gateway by stored name (used when verifying / on webhooks). */
    public function gateway(string $name): PaymentGateway
    {
        return PaymentGatewayFactory::make($name);
    }
}
