<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Admin-facing notification (firm verification, payment proof, creator payout,
 * contact form, future system alerts). Written via AdminNotificationService.
 */
class AdminNotification extends Model
{
    protected $table = 'admin_notifications';

    protected $fillable = [
        'type',
        'title',
        'message',
        'action_url',
        'metadata',
        'is_read',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'is_read'  => 'boolean',
            'read_at'  => 'datetime',
        ];
    }
}
