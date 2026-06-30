<?php

namespace App\Services\Notifications;

use App\Models\AdminNotification;
use App\Services\Notifications\FcmService;
use Illuminate\Support\Facades\Log;

/**
 * Centralized admin-notification factory.
 *
 * All admin notifications (current and future) should be created through this
 * service so storage, shape and failure-handling stay consistent. Creation is
 * intentionally NON-THROWING — a notification failure is logged but never breaks
 * the host business flow (registration, payment, payout, contact, …).
 *
 * Usage:
 *   AdminNotificationService::create(
 *       AdminNotificationService::TYPE_CONTACT,
 *       'New contact form submission',
 *       'Jane Doe sent a message.',
 *       '/admin/feedback',
 *       ['email' => 'jane@x.com']
 *   );
 */
class AdminNotificationService
{
    // ── Notification types (extend freely; no schema change required) ──────────
    public const TYPE_FIRM_VERIFICATION    = 'firm_verification';
    public const TYPE_PAYMENT_VERIFICATION = 'payment_verification';
    public const TYPE_PREMIUM_REQUEST      = 'premium_request';
    public const TYPE_CREATOR_PAYOUT       = 'creator_payout';
    public const TYPE_CONTACT              = 'contact_submission';
    public const TYPE_SYSTEM_ALERT         = 'system_alert';
    public const TYPE_PROFILE_REPORT       = 'profile_report';
    public const TYPE_SUPPORT_TICKET       = 'support_ticket';

    /**
     * Create an admin notification. Returns the model, or null on failure.
     */
    public static function create(
        string $type,
        string $title,
        string $message,
        ?string $actionUrl = null,
        array $metadata = []
    ): ?AdminNotification {
        try {
            $notification = AdminNotification::create([
                'type'       => $type,
                'title'      => $title,
                'message'    => $message,
                'action_url' => $actionUrl,
                'metadata'   => $metadata ?: null,
                'is_read'    => false,
            ]);

            // Fan out to admin devices via FCM. Additive + non-throwing: a push
            // failure (or missing FCM config) never affects the stored notification.
            FcmService::sendToAllAdmins($title, $message, $actionUrl, [
                'type'            => $type,
                'notification_id' => (string) $notification->id,
            ]);

            return $notification;
        } catch (\Throwable $e) {
            Log::error('AdminNotificationService@create failed: ' . $e->getMessage(), [
                'type' => $type,
            ]);
            return null;
        }
    }

    // ── Typed convenience helpers for the Phase-1 sources ──────────────────────

    public static function firmVerification(string $firmName, int $firmProfileId): ?AdminNotification
    {
        return self::create(
            self::TYPE_FIRM_VERIFICATION,
            'New firm verification request',
            "{$firmName} has completed its profile and is ready for verification review.",
            '/admin/firms',
            ['firm_profile_id' => $firmProfileId, 'firm_name' => $firmName]
        );
    }

    public static function paymentVerification(string $studentName, float $amount, int $rechargeId): ?AdminNotification
    {
        return self::create(
            self::TYPE_PAYMENT_VERIFICATION,
            'Payment proof awaiting verification',
            "{$studentName} uploaded a wallet recharge proof of ₹" . number_format($amount, 2) . '.',
            '/admin/wallet-recharges',
            ['recharge_id' => $rechargeId, 'amount' => $amount, 'student_name' => $studentName]
        );
    }

    public static function creatorPayout(int $payoutEngagementId, float $netAmount, int $creatorId): ?AdminNotification
    {
        return self::create(
            self::TYPE_CREATOR_PAYOUT,
            'New creator payout request',
            'A creator payout of ₹' . number_format($netAmount, 2) . ' is pending processing.',
            '/admin/creator-payouts',
            ['engagement_id' => $payoutEngagementId, 'net_amount' => $netAmount, 'creator_id' => $creatorId]
        );
    }

    public static function premiumRequest(string $firmName, string $plan, float $amount, int $requestId, ?int $firmId = null): ?AdminNotification
    {
        return self::create(
            self::TYPE_PREMIUM_REQUEST,
            'Premium purchase request',
            "{$firmName} submitted a {$plan} premium payment of ₹" . number_format($amount, 2) . ' for verification.',
            '/admin/premium-requests',
            ['premium_request_id' => $requestId, 'firm_id' => $firmId, 'firm_name' => $firmName, 'plan' => $plan, 'amount' => $amount]
        );
    }

    public static function studentPremiumRequest(string $studentName, string $plan, float $amount, int $requestId, ?int $userId = null): ?AdminNotification
    {
        return self::create(
            self::TYPE_PREMIUM_REQUEST,
            'Student premium request',
            "{$studentName} submitted a {$plan} premium payment of ₹" . number_format($amount, 2) . ' for verification.',
            '/admin/student-premium-requests',
            ['student_premium_request_id' => $requestId, 'user_id' => $userId, 'student_name' => $studentName, 'plan' => $plan, 'amount' => $amount]
        );
    }

    public static function contactSubmission(string $name, string $email, string $subject): ?AdminNotification
    {
        return self::create(
            self::TYPE_CONTACT,
            'New contact form submission',
            "{$name} sent a message: {$subject}",
            '/admin/feedback',
            ['name' => $name, 'email' => $email, 'subject' => $subject]
        );
    }
}
