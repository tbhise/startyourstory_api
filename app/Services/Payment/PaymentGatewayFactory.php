<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGateway;

class PaymentGatewayFactory
{
    /**
     * Resolve a gateway implementation by name.
     *
     * To add a new provider: implement PaymentGateway, place it in this namespace,
     * and add a case here. No other files need to change.
     *
     * Supported:  phonepe | cashfree
     * Planned:    payu | razorpay
     */
    public static function make(string $gateway = 'phonepe'): PaymentGateway
    {
        return match ($gateway) {
            'phonepe'  => new PhonePeGateway(),
            'cashfree' => new CashfreeGateway(),
            // 'razorpay' => new RazorpayGateway(),
            default    => throw new \InvalidArgumentException("Unknown payment gateway: {$gateway}"),
        };
    }
}
