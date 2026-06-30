@extends('emails.layouts.app', ['heading' => 'Support Ticket Resolved'])

@section('content')
<p>Dear <strong>{{ $userName }}</strong>,</p>

<p>Your support ticket has been resolved and closed. Here is a summary for your records:</p>

<div class="info-box">
    <p><strong>Ticket ID:</strong> {{ $ticketNo }}</p>
    <p><strong>Category:</strong> {{ $ticketCategory }}</p>
    <p style="margin-bottom:0;"><strong>Resolution:</strong><br>{{ $resolutionNote }}</p>
</div>

<div style="text-align:center; margin: 32px 0;">
    <a href="{{ env('FRONTEND_URL', 'https://startyourstory.in') }}/support" class="button">
        View My Tickets
    </a>
</div>

<p>If your issue persists or you need further help, please raise a new support ticket and we'll be happy to assist.</p>

<p style="margin-top:24px;">Warm regards,<br><strong>Team Start Your Story</strong></p>
@endsection
