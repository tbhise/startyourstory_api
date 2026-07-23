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

            // Manual payment destination details (Premium Subscription page). Seeded
            // with the previously-hardcoded values so existing behaviour is preserved.
            ['payment_account_holder', 'MR. RITESH CHANDAK', 'string', 'Account Holder Name', 'Name on the bank account that receives manual payments.', 'payment'],
            ['payment_bank_name', 'Bank of Baroda', 'string', 'Bank Name', 'Bank where the receiving account is held.', 'payment'],
            ['payment_account_number', '97980100019171', 'string', 'Account Number', 'Receiving bank account number.', 'payment'],
            ['payment_ifsc', 'BARBODBMURU', 'string', 'IFSC Code', 'IFSC code of the receiving bank branch.', 'payment'],
            ['payment_upi_id', '9156235503@ybl', 'string', 'UPI ID', 'UPI ID shown to firms for manual UPI payments.', 'payment'],
            // QR image path (relative, on the public disk). Managed via the upload
            // endpoint, NOT the generic text editor — hence is_editable = false.
            ['payment_qr_code', '', 'string', 'Payment QR Code', 'QR code image for manual UPI payments (optional).', 'payment'],

            // Active online payment gateway. Resolved by PaymentManager at
            // payment-initiation time; verify/webhook use the gateway stored on
            // the row. Allowed values: phonepe | cashfree.
            ['default_payment_gateway', 'phonepe', 'string', 'Default Payment Gateway', 'Online payment gateway used for new payments (Wallet, Premium, Creator, CA Library). Existing pending payments still verify on the gateway they were created with.', 'payment'],

            // CA Library — snapshotted into ca_test_submissions at creation time;
            // changing it only affects NEW submissions.
            ['ca_library_evaluation_fee', '99', 'integer', 'Answer Sheet Evaluation Fee', 'Fee (₹) charged for one CA Library answer sheet evaluation. Applied to new submissions only.', 'ca_library'],
        ];

        // Settings managed by a dedicated uploader rather than the generic text editor.
        $nonEditable = ['payment_qr_code'];

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
                        'is_editable'  => !in_array($key, $nonEditable, true),
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
