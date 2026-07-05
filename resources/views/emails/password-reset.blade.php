@extends('emails.layouts.premium', ['title' => 'Reset Your Password'])

@section('content')
<div class="dm-p" style="font-family:'Inter',Arial,Helvetica,sans-serif;font-size:16px;line-height:1.8;color:#4b5563;">
<p>Hello,</p>

<p>We received a request to reset the password for your <strong>Start Your Story</strong> account.</p>

<p>Click the button below to create a new password:</p>

<div style="text-align:center; margin: 32px 0;">
    <a href="{{ $resetUrl }}" class="dm-btn" style="display:inline-block;background-color:#2563eb;border-radius:10px;padding:14px 28px;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:15px;font-weight:600;color:#FFFFFF;text-decoration:none;">
        Reset Password
    </a>
</div>

<div class="dm-card dm-p" style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:14px 16px;margin-top:24px;color:#1e40af;font-size:14px;line-height:1.7;">
    <p>If you did not request this change, please ignore this email. Your password will remain unchanged.</p>
    <p style="margin-top:8px;">This link will expire in <strong>60 minutes</strong>.</p>
</div>

<p style="margin-top:24px;">Stay secure,<br><strong>Team Start Your Story</strong></p>
</div>
@endsection
