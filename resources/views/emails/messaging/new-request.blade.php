{{--
    New message REQUEST notification (first message in a new conversation).
    Variables: $recipientName, $senderName, $messagePreview, $appName, $messagesUrl.
    Migrated from a standalone HTML shell to the shared premium layout —
    body content unchanged.
--}}
@extends('emails.layouts.premium', [
    'title'     => 'New Message Request — StartYourStory',
    'preheader' => 'You have a new message request from ' . $senderName . '.',
])

@section('content')
<div class="dm-p" style="font-family:'Inter',Arial,Helvetica,sans-serif;font-size:14px;line-height:1.6;color:#4b5563;">

    <p class="dm-h" style="margin:0 0 8px;font-size:15px;color:#111827;font-weight:600;">Hi {{ $recipientName }},</p>

    <p style="margin:0 0 20px;">
        You have a new message request from <strong>{{ $senderName }}</strong>.
    </p>

    {{-- Preview box --}}
    <div class="dm-card" style="background:#f9fafb;border-left:3px solid #2563eb;border-radius:6px;padding:14px 16px;margin-bottom:24px;">
        <p class="dm-p" style="margin:0;font-size:13px;color:#374151;line-height:1.6;font-style:italic;">"{{ $messagePreview }}"</p>
    </div>

    <p style="margin:0 0 24px;">
        Log in to view the full message and reply.
    </p>

    {{-- CTA --}}
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 16px;">
        <tr>
            <td class="dm-btn" style="background-color:#2563eb;border-radius:8px;">
                <a href="{{ $messagesUrl }}" style="display:inline-block;padding:12px 24px;color:#ffffff;font-size:14px;font-weight:600;text-decoration:none;">
                    View Message
                </a>
            </td>
        </tr>
    </table>

</div>
@endsection
