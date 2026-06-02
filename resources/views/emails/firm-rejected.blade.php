@extends('emails.layouts.app', ['heading' => 'Account Verification Update'])

@section('content')
<p>Dear <strong>{{ $firmName }}</strong>,</p>

<p>Thank you for registering on <strong>Start Your Story</strong>. After reviewing your firm account, we were unable to approve it at this time.</p>

<div class="info-box" style="border-color:#fecaca; background:#fef2f2;">
    <p style="color:#b91c1c;"><strong>Reason:</strong> {{ $reason }}</p>
</div>

<p style="margin-top:20px;">If you believe this is a mistake or would like to provide additional information, please reach out to us and we will be happy to review your account again.</p>

<div style="text-align:center; margin: 32px 0;">
    <a href="mailto:info@startyourstory.in" class="button" style="background:#374151;">
        Contact Support
    </a>
</div>

<p>We apologise for any inconvenience.<br><strong>Team Start Your Story</strong></p>
@endsection
