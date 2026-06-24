<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Centralized payout details for ALL payout flows (creator payouts, referral
 * payouts, future). One profile per user. Replaces the creator-only
 * `creator_bank_details` table.
 *
 * - account_number / ifsc_code are stored ENCRYPTED (Crypt::encryptString),
 *   identical to the legacy creator_bank_details, so existing encrypted values
 *   are copied across as-is (no re-encryption needed).
 * - upi_id is stored in plaintext (shown in full to the user/admin).
 * - preferred_method drives validation: 'upi' needs upi_id; 'bank' needs the
 *   four bank fields.
 *
 * SAFETY: this migration is ADDITIVE. It creates the new table and COPIES legacy
 * rows in. It does NOT drop creator_bank_details — that happens in a later
 * migration only after the migrated flows are verified in production.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_payout_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->enum('preferred_method', ['upi', 'bank'])->default('upi');
            $table->string('upi_id', 255)->nullable();
            $table->string('account_holder_name', 255)->nullable();
            $table->string('bank_name', 255)->nullable();
            $table->text('account_number')->nullable();   // encrypted
            $table->text('ifsc_code')->nullable();         // encrypted
            $table->boolean('is_verified')->default(false);
            $table->timestamps();

            $table->unique('user_id', 'uq_upd_user'); // one payout profile per user
        });

        // ── Data migration: copy legacy creator_bank_details → user_payout_details ──
        // Encrypted account_number / ifsc_code are copied verbatim. insertOrIgnore +
        // the unique(user_id) makes this idempotent and safe to re-run.
        if (Schema::hasTable('creator_bank_details')) {
            DB::table('creator_bank_details')->orderBy('id')->chunkById(200, function ($rows) {
                $now = now();
                $payload = [];
                foreach ($rows as $r) {
                    $payload[] = [
                        'user_id'             => $r->creator_id,
                        'preferred_method'    => 'bank',
                        'upi_id'              => null,
                        'account_holder_name' => $r->account_holder_name,
                        'bank_name'           => $r->bank_name,
                        'account_number'      => $r->account_number, // already encrypted
                        'ifsc_code'           => $r->ifsc_code,      // already encrypted
                        'is_verified'         => $r->is_verified,
                        'created_at'          => $r->created_at ?? $now,
                        'updated_at'          => $r->updated_at ?? $now,
                    ];
                }
                if ($payload) {
                    DB::table('user_payout_details')->insertOrIgnore($payload);
                }
            });
        }
    }

    public function down(): void
    {
        // Only drop the new table. creator_bank_details is left untouched.
        Schema::dropIfExists('user_payout_details');
    }
};
