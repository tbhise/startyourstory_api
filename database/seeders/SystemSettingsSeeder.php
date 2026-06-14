<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the initial Platform Settings. Idempotent: re-running only fills missing
 * keys and never overwrites an admin-edited value (updateOrInsert on metadata,
 * value only set on first insert).
 */
class SystemSettingsSeeder extends Seeder
{
    public function run(): void
    {
        // [key, value, type, title, description, category]
        $settings = [
            ['student_referral_reward', '50', 'integer', 'Student Referral Reward', 'SYS Coins credited to the referrer when a referred student completes onboarding.', 'rewards'],
            ['firm_premium_purchase_reward', '2000', 'integer', 'Firm Premium Purchase Reward', 'Amount (₹) rewarded to the referrer when a referred firm buys premium.', 'rewards'],
            ['welcome_bonus_coins', '100', 'integer', 'Welcome Bonus Coins', 'SYS Coins granted once to provisional students on completing onboarding.', 'welcome_bonus'],
            ['free_applications_count', '3', 'integer', 'Free Applications Count', 'Number of free job applications a student gets before fees apply.', 'application'],
            ['application_fee_amount', '49', 'integer', 'Application Fee Amount', 'Wallet fee (₹) charged per job application beyond the free quota.', 'application'],
            ['minimum_wallet_recharge', '150', 'integer', 'Minimum Wallet Recharge Amount', 'Smallest allowed wallet recharge amount (₹).', 'wallet'],
        ];

        foreach ($settings as [$key, $value, $type, $title, $desc, $category]) {
            $exists = DB::table('system_settings')->where('setting_key', $key)->exists();

            DB::table('system_settings')->updateOrInsert(
                ['setting_key' => $key],
                array_merge(
                    [
                        'setting_type' => $type,
                        'title'        => $title,
                        'description'  => $desc,
                        'category'     => $category,
                        'is_editable'  => true,
                        'updated_at'   => now(),
                    ],
                    // Only set the value (and created_at) on first insert — never
                    // clobber an admin-edited value on a re-seed.
                    $exists ? [] : ['setting_value' => $value, 'created_at' => now()]
                )
            );
        }
    }
}
