@extends('emails.layouts.app', ['heading' => "You've Been Selected!"])

@section('content')
<p>Dear <strong>{{ $creatorName }}</strong>,</p>

<p>Great news! A firm has reviewed your bid and selected you for the project <strong>"{{ $projectTitle }}"</strong> on Start Your Story.</p>

<p>Please log in to review the project brief and contract details, then accept or decline the offer.</p>

<div style="text-align:center; margin: 32px 0;">
    <a href="{{ $respondUrl }}" class="button">
        Review &amp; Respond
    </a>
</div>

<div class="info-box">
    <p>This offer is waiting for your response. Once you accept, the firm will initiate payment to activate the project.</p>
</div>

<p style="margin-top:24px;">Good luck,<br><strong>Team Start Your Story</strong></p>
@endsection
