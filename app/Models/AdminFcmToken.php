<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * An FCM registration token for one admin device. Multiple rows per admin =
 * multiple devices. Written when an authenticated admin registers their device.
 */
class AdminFcmToken extends Model
{
    protected $table = 'admin_fcm_tokens';

    protected $fillable = [
        'admin_user_id',
        'token',
        'device_info',
        'last_active_at',
    ];

    protected function casts(): array
    {
        return [
            'last_active_at' => 'datetime',
        ];
    }
}
