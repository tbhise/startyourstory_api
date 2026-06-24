<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Centralized payout profile for a user (creator + referral + future payouts).
 * account_number / ifsc_code are stored encrypted — handled by PayoutDetailsService.
 */
class UserPayoutDetail extends Model
{
    protected $table = 'user_payout_details';

    protected $fillable = [
        'user_id',
        'preferred_method',
        'upi_id',
        'account_holder_name',
        'bank_name',
        'account_number',
        'ifsc_code',
        'is_verified',
    ];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
        ];
    }
}
