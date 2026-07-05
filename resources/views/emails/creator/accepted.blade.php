@extends('emails.layouts.premium', ['title' => 'Creator Accepted Your Project!'])

@section('content')
<div class="dm-p" style="font-family:'Inter',Arial,Helvetica,sans-serif;font-size:16px;line-height:1.8;color:#4b5563;">
<p>Dear <strong>{{ $firmName }}</strong>,</p>

<p><strong>{{ $creatorName }}</strong> has accepted your offer for the project <strong>"{{ $projectTitle }}"</strong>.</p>

<p>A contract has been created. Please log in to review the details and initiate payment to activate the project and get started.</p>

<div style="text-align:center; margin: 32px 0;">
    <a href="{{ $contractUrl }}" class="dm-btn" style="display:inline-block;background-color:#2563eb;border-radius:10px;padding:14px 28px;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:15px;font-weight:600;color:#FFFFFF;text-decoration:none;">
        View Contract &amp; Pay
    </a>
</div>

<div class="dm-card dm-p" style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:14px 16px;margin-top:24px;color:#1e40af;font-size:14px;line-height:1.7;">
    <p>Payment is required to activate the project. Funds are held in escrow and released to the creator only after you approve their deliverable.</p>
</div>

<p style="margin-top:24px;">Best regards,<br><strong>Team Start Your Story</strong></p>
</div>
@endsection
