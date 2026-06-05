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
     * Supported:  razorpay
     * Planned:    phonepe | cashfree | payu
     */
    public static function make(string $gateway = 'razorpay'): PaymentGateway
    {
        return match ($gateway) {
            'razorpay' => new RazorpayGateway(),
            // 'phonepe'  => new PhonePeGateway(),
            // 'cashfree' => new CashfreeGateway(),
            // 'payu'     => new PayUGateway(),
            default    => throw new \InvalidArgumentException("Unknown payment gateway: {$gateway}"),
        };
    }
}
