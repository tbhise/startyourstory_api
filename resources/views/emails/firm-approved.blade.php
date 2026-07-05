@extends('emails.layouts.premium', ['title' => 'Account Approved!'])

@section('content')
<div class="dm-p" style="font-family:'Inter',Arial,Helvetica,sans-serif;font-size:16px;line-height:1.8;color:#4b5563;">
<p>Dear <strong>{{ $firmName }}</strong>,</p>

<p>Great news! Your firm account on <strong>Start Your Story</strong> has been reviewed and <strong>approved</strong>.</p>

<p>You can now log in and access all platform features — post jobs, browse candidates, and start building your team.</p>

<div style="text-align:center; margin: 32px 0;">
    <a href="{{ env('FRONTEND_URL', 'https://startyourstory.in') }}/login" class="dm-btn" style="display:inline-block;background-color:#2563eb;border-radius:10px;padding:14px 28px;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:15px;font-weight:600;color:#FFFFFF;text-decoration:none;">
        Go to Dashboard
    </a>
</div>

<div class="dm-card dm-p" style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:14px 16px;margin-top:24px;color:#1e40af;font-size:14px;line-height:1.7;">
    <p>If you have any questions, reply to this email or contact us at <a href="mailto:info@startyourstory.in" style="color:#1e40af;">info@startyourstory.in</a>.</p>
</div>

<p style="margin-top:24px;">Welcome aboard,<br><strong>Team Start Your Story</strong></p>
</div>
@endsection
