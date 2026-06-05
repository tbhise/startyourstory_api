<?php

namespace App\Contracts\Mail;

use App\Enums\EmailPurpose;

interface HasEmailPurpose
{
    public function emailPurpose(): EmailPurpose;
}
