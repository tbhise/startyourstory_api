@extends('emails.layouts.app', ['heading' => 'Reset Your Password'])

@section('content')
<p>Hello,</p>

<p>We received a request to reset the password for your <strong>Start Your Story</strong> account.</p>

<p>Click the button below to create a new password:</p>

<div style="text-align:center; margin: 32px 0;">
    <a href="{{ $resetUrl }}" class="button">
        Reset Password
    </a>
</div>

<div class="info-box">
    <p>If you did not request this change, please ignore this email. Your password will remain unchanged.</p>
    <p style="margin-top:8px;">This link will expire in <strong>60 minutes</strong>.</p>
</div>

<p style="margin-top:24px;">Stay secure,<br><strong>Team Start Your Story</strong></p>
@endsection
