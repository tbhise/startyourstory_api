@extends('emails.layouts.app', ['heading' => 'Creator Accepted Your Project!'])

@section('content')
<p>Dear <strong>{{ $firmName }}</strong>,</p>

<p><strong>{{ $creatorName }}</strong> has accepted your offer for the project <strong>"{{ $projectTitle }}"</strong>.</p>

<p>A contract has been created. Please log in to review the details and initiate payment to activate the project and get started.</p>

<div style="text-align:center; margin: 32px 0;">
    <a href="{{ $contractUrl }}" class="button">
        View Contract &amp; Pay
    </a>
</div>

<div class="info-box">
    <p>Payment is required to activate the project. Funds are held in escrow and released to the creator only after you approve their deliverable.</p>
</div>

<p style="margin-top:24px;">Best regards,<br><strong>Team Start Your Story</strong></p>
@endsection
