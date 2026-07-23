<?php

namespace App\Helpers;

use App\Services\Notifications\EmailNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Queues the "Premium Subscription Activated" confirmation email for a firm.
 *
 * Called AFTER DB::commit() from every activation flow (PhonePe verify,
 * PhonePe webhook, admin manual assignment, premium-request approval) —
 * strictly non-blocking: any failure is logged and never affects activation.
 *
 * Exactly-once guarantee: a 'premium_activation_email_queued' marker row in
 * payment_logs (keyed by firm_subscription_id) is written before queueing, so
 * even when two paths activate the same subscription (verify + webhook racing
 * on one transaction), only the first caller sends the email.
 */
class PremiumActivationEmailHelper
{
    public const TYPE_PHONEPE          = 'phonepe';
    public const TYPE_CASHFREE         = 'cashfree';
    public const TYPE_ADMIN_ASSIGNED   = 'admin_assigned';
    public const TYPE_REQUEST_APPROVED = 'request_approved';

    private const EMAIL_MARKER_EVENT = 'premium_activation_email_queued';

    public static function send(int $subscriptionId, string $activationType): void
    {
        try {
            $sub = DB::table('firm_subscriptions')->where('id', $subscriptionId)->first();
            if (!$sub || $sub->status !== 'active') {
                return;
            }

            // Exactly-once: skip if any path already queued the email for this row.
            $alreadyQueued = DB::table('payment_logs')
                ->where('firm_subscription_id', $subscriptionId)
                ->where('event_type', self::EMAIL_MARKER_EVENT)
                ->exists();
            if ($alreadyQueued) {
                return;
            }

            // firm_subscriptions.firm_id is firm_profiles.id on every current
            // flow; the user_id fallback covers historical rows (same dual
            // resolution as AdminController's subscription listing).
            $firm = DB::table('firm_profiles')->where('id', $sub->firm_id)->first()
                ?? DB::table('firm_profiles')->where('user_id', $sub->firm_id)->first();
            if (!$firm) {
                return;
            }

            $email = DB::table('users')->where('id', $firm->user_id)->value('email');
            if (!$email) {
                return;
            }

            $meta   = PlanHelper::meta($sub->plan);
            $months = PlanHelper::durationMonths($sub->plan);
            $period = $months . ($months === 1 ? ' Month' : ' Months');

            $gateway = strtolower((string) ($sub->payment_gateway ?: $sub->payment_method ?: ''));
            $paymentMethod = match ($gateway) {
                'phonepe'  => 'PhonePe (Online)',
                'razorpay' => 'Razorpay (Online)',
                'manual'   => 'Manual / Bank Transfer',
                default    => $gateway !== '' ? ucfirst($gateway) : 'Manual',
            };

            // Same invoice number scheme as FirmBillingController (INV-PRM-00042).
            $invoiceNumber = 'INV-PRM-' . str_pad((string) $sub->id, 5, '0', STR_PAD_LEFT);

            $front      = rtrim(config('app.frontend_url', 'https://startyourstory.in'), '/');
            $billingUrl = $front . '/firm/billing-payments';

            $activationDate = $sub->starts_at ? date('d M Y', strtotime($sub->starts_at)) : date('d M Y');
            $expiryDate     = $sub->expires_at ? date('d M Y', strtotime($sub->expires_at)) : null;
            $amount         = '₹' . number_format((float) $sub->amount, 2);

            // Marker BEFORE queueing so a concurrent second caller bails out above.
            DB::table('payment_logs')->insert([
                'firm_subscription_id' => $subscriptionId,
                'event_type'           => self::EMAIL_MARKER_EVENT,
                'payload'              => json_encode(['activation_type' => $activationType, 'recipient' => $email]),
                'created_at'           => now(),
            ]);

            app(EmailNotificationService::class)->sendPremiumSubscriptionActivated(
                $email,
                $firm->firm_name ?? 'Hiring Partner',
                $activationType,
                $meta['name'],
                $period,
                $activationDate,
                $expiryDate,
                $amount,
                $paymentMethod,
                $invoiceNumber,
                $billingUrl
            );
        } catch (\Throwable $e) {
            Log::error('PremiumActivationEmailHelper: ' . $e->getMessage(), [
                'subscription_id' => $subscriptionId,
                'activation_type' => $activationType,
            ]);
        }
    }
}
