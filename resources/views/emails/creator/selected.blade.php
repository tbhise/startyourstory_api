@extends('emails.layouts.premium', ['title' => "You've Been Selected!"])

@section('content')
<div class="dm-p" style="font-family:'Inter',Arial,Helvetica,sans-serif;font-size:16px;line-height:1.8;color:#4b5563;">
<p>Dear <strong>{{ $creatorName }}</strong>,</p>

<p>Great news! A firm has reviewed your bid and selected you for the project <strong>"{{ $projectTitle }}"</strong> on Start Your Story.</p>

<p>Please log in to review the project brief and contract details, then accept or decline the offer.</p>

<div style="text-align:center; margin: 32px 0;">
    <a href="{{ $respondUrl }}" class="dm-btn" style="display:inline-block;background-color:#2563eb;border-radius:10px;padding:14px 28px;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:15px;font-weight:600;color:#FFFFFF;text-decoration:none;">
        Review &amp; Respond
    </a>
</div>

<div class="dm-card dm-p" style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:14px 16px;margin-top:24px;color:#1e40af;font-size:14px;line-height:1.7;">
    <p>This offer is waiting for your response. Once you accept, the firm will initiate payment to activate the project.</p>
</div>

<p style="margin-top:24px;">Good luck,<br><strong>Team Start Your Story</strong></p>
</div>
@endsection
