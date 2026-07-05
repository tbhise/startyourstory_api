@extends('emails.layouts.premium', ['title' => 'Support Ticket Resolved'])

@section('content')
<div class="dm-p" style="font-family:'Inter',Arial,Helvetica,sans-serif;font-size:16px;line-height:1.8;color:#4b5563;">
<p>Dear <strong>{{ $userName }}</strong>,</p>

<p>Your support ticket has been resolved and closed. Here is a summary for your records:</p>

<div class="dm-card dm-p" style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:14px 16px;margin-top:24px;color:#1e40af;font-size:14px;line-height:1.7;">
    <p><strong>Ticket ID:</strong> {{ $ticketNo }}</p>
    <p><strong>Category:</strong> {{ $ticketCategory }}</p>
    <p style="margin-bottom:0;"><strong>Resolution:</strong><br>{{ $resolutionNote }}</p>
</div>

<div style="text-align:center; margin: 32px 0;">
    <a href="{{ env('FRONTEND_URL', 'https://startyourstory.in') }}/support" class="dm-btn" style="display:inline-block;background-color:#2563eb;border-radius:10px;padding:14px 28px;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:15px;font-weight:600;color:#FFFFFF;text-decoration:none;">
        View My Tickets
    </a>
</div>

<p>If your issue persists or you need further help, please raise a new support ticket and we'll be happy to assist.</p>

<p style="margin-top:24px;">Warm regards,<br><strong>Team Start Your Story</strong></p>
</div>
@endsection
