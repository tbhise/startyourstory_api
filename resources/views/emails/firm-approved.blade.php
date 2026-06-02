@extends('emails.layouts.app', ['heading' => 'Account Approved!'])

@section('content')
<p>Dear <strong>{{ $firmName }}</strong>,</p>

<p>Great news! Your firm account on <strong>Start Your Story</strong> has been reviewed and <strong>approved</strong>.</p>

<p>You can now log in and access all platform features — post jobs, browse candidates, and start building your team.</p>

<div style="text-align:center; margin: 32px 0;">
    <a href="{{ env('FRONTEND_URL', 'https://startyourstory.in') }}/login" class="button">
        Go to Dashboard
    </a>
</div>

<div class="info-box">
    <p>If you have any questions, reply to this email or contact us at <a href="mailto:info@startyourstory.in" style="color:#1e40af;">info@startyourstory.in</a>.</p>
</div>

<p style="margin-top:24px;">Welcome aboard,<br><strong>Team Start Your Story</strong></p>
@endsection
