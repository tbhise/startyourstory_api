@extends('emails.layouts.premium', ['title' => 'Account Verification Update'])

@section('content')
<div class="dm-p" style="font-family:'Inter',Arial,Helvetica,sans-serif;font-size:16px;line-height:1.8;color:#4b5563;">
<p>Dear <strong>{{ $firmName }}</strong>,</p>

<p>Thank you for registering on <strong>Start Your Story</strong>. After reviewing your firm account, we were unable to approve it at this time.</p>

<div class="dm-card dm-p" style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:14px 16px;margin-top:24px;color:#1e40af;font-size:14px;line-height:1.7; border-color:#fecaca; background:#fef2f2;">
    <p style="color:#b91c1c;"><strong>Reason:</strong> {{ $reason }}</p>
</div>

<p style="margin-top:20px;">If you believe this is a mistake or would like to provide additional information, please reach out to us and we will be happy to review your account again.</p>

<div style="text-align:center; margin: 32px 0;">
    <a href="mailto:info@startyourstory.in" style="display:inline-block;background-color:#374151;border-radius:10px;padding:14px 28px;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:15px;font-weight:600;color:#FFFFFF;text-decoration:none;">
        Contact Support
    </a>
</div>

<p>We apologise for any inconvenience.<br><strong>Team Start Your Story</strong></p>
</div>
@endsection
