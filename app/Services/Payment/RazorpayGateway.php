<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGateway;
use Razorpay\Api\Api;

class RazorpayGateway implements PaymentGateway
{
    private Api $api;

    public function __construct()
    {
        $this->api = new Api(
            config('services.razorpay.key'),
            config('services.razorpay.secret')
        );
    }

    public function createOrder(float $amount, string $receipt, array $notes = []): array
    {
        $order = $this->api->order->create([
            'receipt'         => $receipt,
            'amount'          => (int) round($amount * 100), // paise
            'currency'        => 'INR',
            'payment_capture' => 1,
            'notes'           => $notes,
        ]);

        return [
            'order_id' => $order['id'],
            'amount'   => $order['amount'],
            'currency' => $order['currency'],
            'raw'      => $order->toArray(),
        ];
    }

    public function verifySignature(array $attributes): bool
    {
        try {
            $this->api->utility->verifyPaymentSignature([
                'razorpay_order_id'   => $attributes['order_id'],
                'razorpay_payment_id' => $attributes['payment_id'],
                'razorpay_signature'  => $attributes['signature'],
            ]);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function fetchPayment(string $paymentId): array
    {
        return $this->api->payment->fetch($paymentId)->toArray();
    }
}
