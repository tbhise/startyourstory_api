<?php

namespace App\Services;

use App\Models\UserPayoutDetail;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Single source of truth for user payout details across ALL payout flows
 * (creator, referral, future). Centralizes the new `user_payout_details` table.
 *
 * Migration safety: reads fall back to the legacy `creator_bank_details` table
 * when a user has no row in the new table yet, so creator payouts keep working
 * during/after the data migration. All writes go to the new table only.
 *
 * account_number / ifsc_code are encrypted at rest (Crypt). UPI id is plaintext.
 */
class PayoutDetailsService
{
    /**
     * Does this user have payout details in EITHER table?
     * Used to decide whether to prompt the user (do not ask again if present).
     */
    public static function has(int $userId): bool
    {
        if (UserPayoutDetail::where('user_id', $userId)->exists()) {
            return true;
        }
        return DB::table('creator_bank_details')->where('creator_id', $userId)->exists();
    }

    /**
     * Return a normalized, display-safe payout profile for a user, or null.
     * Bank account number is masked; ifsc decrypted; upi shown in full.
     * Reads the new table first, then falls back to legacy creator_bank_details.
     *
     * @return array{method:string,upi_id:?string,account_holder_name:?string,bank_name:?string,account_number_masked:?string,ifsc_code:?string,is_verified:bool,source:string}|null
     */
    public static function getForDisplay(int $userId): ?array
    {
        $row = UserPayoutDetail::where('user_id', $userId)->first();
        if ($row) {
            return [
                'method'                => $row->preferred_method,
                'upi_id'                => $row->upi_id,
                'account_holder_name'   => $row->account_holder_name,
                'bank_name'             => $row->bank_name,
                'account_number_masked' => self::maskAccount($row->account_number),
                'ifsc_code'             => self::safeDecrypt($row->ifsc_code),
                'is_verified'           => (bool) $row->is_verified,
                'source'                => 'user_payout_details',
            ];
        }

        // Legacy fallback (bank only).
        $legacy = DB::table('creator_bank_details')->where('creator_id', $userId)->first();
        if ($legacy) {
            return [
                'method'                => 'bank',
                'upi_id'                => null,
                'account_holder_name'   => $legacy->account_holder_name,
                'bank_name'             => $legacy->bank_name,
                'account_number_masked' => self::maskAccount($legacy->account_number),
                'ifsc_code'             => self::safeDecrypt($legacy->ifsc_code),
                'is_verified'           => (bool) $legacy->is_verified,
                'source'                => 'creator_bank_details',
            ];
        }

        return null;
    }

    /**
     * Validate + upsert a user's payout details into the new table.
     * Method-aware rules: UPI requires upi_id; bank requires the four bank fields.
     *
     * @return array{ok:bool,message:string}
     */
    public static function save(int $userId, array $input): array
    {
        $method = $input['preferred_method'] ?? null;

        $rules = ['preferred_method' => 'required|in:upi,bank'];
        if ($method === 'upi') {
            // UPI ID: handle@bank format.
            $rules['upi_id'] = ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9._-]{2,256}@[a-zA-Z]{2,64}$/'];
        } elseif ($method === 'bank') {
            $rules['account_holder_name'] = 'required|string|max:255';
            $rules['bank_name']           = 'required|string|max:255';
            $rules['account_number']      = 'required|string|min:9|max:20';
            $rules['ifsc_code']           = ['required', 'string', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/i'];
        }

        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            return ['ok' => false, 'message' => $validator->errors()->first()];
        }

        if ($method === 'upi') {
            $values = [
                'preferred_method'    => 'upi',
                'upi_id'              => trim($input['upi_id']),
                // Clear stale bank fields when switching to UPI.
                'account_holder_name' => null,
                'bank_name'           => null,
                'account_number'      => null,
                'ifsc_code'           => null,
                'is_verified'         => false,
            ];
        } else {
            $values = [
                'preferred_method'    => 'bank',
                'upi_id'              => null,
                'account_holder_name' => $input['account_holder_name'],
                'bank_name'           => $input['bank_name'],
                'account_number'      => Crypt::encryptString($input['account_number']),
                'ifsc_code'           => Crypt::encryptString(strtoupper($input['ifsc_code'])),
                'is_verified'         => false,
            ];
        }

        UserPayoutDetail::updateOrCreate(['user_id' => $userId], $values);

        return ['ok' => true, 'message' => 'Payout details saved.'];
    }

    private static function maskAccount(?string $encrypted): ?string
    {
        if (!$encrypted) return null;
        try {
            $plain = Crypt::decryptString($encrypted);
        } catch (\Throwable $e) {
            $plain = $encrypted;
        }
        return '••••' . substr($plain, -4);
    }

    private static function safeDecrypt(?string $encrypted): ?string
    {
        if (!$encrypted) return null;
        try {
            return Crypt::decryptString($encrypted);
        } catch (\Throwable $e) {
            return $encrypted;
        }
    }
}
