<?php

namespace App\Helpers;

/**
 * Shared constants/helpers for the Support Ticket feature, used by both the
 * user-facing SupportTicketController and the AdminSupportTicketController so the
 * category whitelist, ticket-number format, status labels and attachment-URL
 * building stay in one place.
 */
class SupportTicketHelper
{
    /** Allowed ticket categories (must match the frontend dropdown exactly). */
    public const CATEGORIES = [
        'Profile Completion Issue',
        'Login Issue',
        'Account Verification Issue',
        'Upload Attachment Issue',
        'Website Bug',
        'Performance Issue',
        'Job Application Issue',
        'Resume Issue',
        'Job Posting Issue',
        'Candidate Access Issue',
        'Subscription / Payment Issue',
        'Other',
    ];

    public const STATUSES = ['submitted', 'in_process', 'closed'];

    /** Human label for a stored status value. */
    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'submitted'  => 'Submitted',
            'in_process' => 'In Process',
            'closed'     => 'Closed',
            default      => ucfirst($status),
        };
    }

    /** SYS-TKT-000001 from the auto-increment id. */
    public static function ticketNo(int $id): string
    {
        return 'SYS-TKT-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }

    /** Absolute URL for a public-disk relative path (null-safe). */
    public static function fileUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }
        return asset('storage/' . ltrim($path, '/'));
    }

    /**
     * Decode the tickets.attachments JSON column into an array of file objects,
     * each guaranteed to expose a usable `url`. Accepts the raw DB value.
     */
    public static function decodeAttachments($raw): array
    {
        if (empty($raw)) {
            return [];
        }
        $list = is_array($raw) ? $raw : json_decode($raw, true);
        if (!is_array($list)) {
            return [];
        }
        return array_values(array_map(static function ($f) {
            $f = (array) $f;
            if (empty($f['url']) && !empty($f['path'])) {
                $f['url'] = self::fileUrl($f['path']);
            }
            return $f;
        }, $list));
    }
}
