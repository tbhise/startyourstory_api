<?php

namespace App\Services\Email;

use App\Enums\EmailPurpose;

class EmailSenderResolver
{
    /**
     * Resolve the sender identity for a given email purpose.
     *
     * Returns ['address' => string, 'name' => string].
     * To separate sender identities in the future, update config/email.php
     * and set the corresponding MAIL_*_ADDRESS env variables.
     */
    public static function resolve(EmailPurpose $purpose): array
    {
        $key    = $purpose->senderKey();
        $sender = config("email.senders.{$key}");

        if (empty($sender['address'])) {
            $sender = config('email.senders.default');
        }

        return $sender;
    }
}
