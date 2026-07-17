@extends('emails.layouts.premium', ['title' => 'Premium Subscription Activated', 'preheader' => 'Your Premium subscription is now active on StartYourStory.'])

@php
    $confirmations = [
        'phonepe'          => 'Your payment has been received successfully and your Premium subscription is now active.',
        'admin_assigned'   => 'A Premium subscription has been activated on your account by the StartYourStory team.',
        'request_approved' => 'Your Premium subscription request has been approved and your subscription is now active.',
    ];
    $confirmation = $confirmations[$activationType] ?? $confirmations['phonepe'];
@endphp

@section('content')
<div class="dm-p" style="font-family:'Inter',Arial,Helvetica,sans-serif;font-size:16px;line-height:1.8;color:#4b5563;">

    <p>Dear <strong>{{ $firmName }}</strong>,</p>

    <p>{{ $confirmation }}</p>

    <p>You now have full access to all Premium features on <strong>StartYourStory</strong>.</p>

    {{-- Subscription Details Card --}}
    <div class="dm-card" style="
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 12px;
        padding: 20px 24px;
        margin: 24px 0;
    ">
        <p class="dm-h" style="margin:0 0 10px;font-size:15px;font-weight:700;color:#111827;">Subscription Details</p>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td class="dm-p" style="padding: 6px 0; font-size: 14px; color: #6b7280; width: 150px; vertical-align: top;">
                    Plan
                </td>
                <td class="dm-h" style="padding: 6px 0; font-size: 15px; color: #111827; font-weight: 600;">
                    {{ $planName }}
                </td>
            </tr>
            <tr>
                <td class="dm-p" style="padding: 6px 0; font-size: 14px; color: #6b7280; vertical-align: top;">
                    Subscription Period
                </td>
                <td class="dm-h" style="padding: 6px 0; font-size: 15px; color: #111827; font-weight: 600;">
                    {{ $subscriptionPeriod }}
                </td>
            </tr>
            <tr>
                <td class="dm-p" style="padding: 6px 0; font-size: 14px; color: #6b7280; vertical-align: top;">
                    Activation Date
                </td>
                <td class="dm-h" style="padding: 6px 0; font-size: 15px; color: #111827; font-weight: 600;">
                    {{ $activationDate }}
                </td>
            </tr>
            @if (!empty($expiryDate))
                <tr>
                    <td class="dm-p" style="padding: 6px 0; font-size: 14px; color: #6b7280; vertical-align: top;">
                        Expiry Date
                    </td>
                    <td class="dm-h" style="padding: 6px 0; font-size: 15px; color: #111827; font-weight: 600;">
                        {{ $expiryDate }}
                    </td>
                </tr>
            @endif
            <tr>
                <td class="dm-p" style="padding: 6px 0; font-size: 14px; color: #6b7280; vertical-align: top;">
                    Amount
                </td>
                <td class="dm-h" style="padding: 6px 0; font-size: 15px; color: #111827; font-weight: 600;">
                    {{ $amount }}
                </td>
            </tr>
            <tr>
                <td class="dm-p" style="padding: 6px 0; font-size: 14px; color: #6b7280; vertical-align: top;">
                    Payment Method
                </td>
                <td class="dm-h" style="padding: 6px 0; font-size: 15px; color: #111827; font-weight: 600;">
                    {{ $paymentMethod }}
                </td>
            </tr>
            @if (!empty($invoiceNumber))
                <tr>
                    <td class="dm-p" style="padding: 6px 0; font-size: 14px; color: #6b7280; vertical-align: top;">
                        Invoice Number
                    </td>
                    <td class="dm-h" style="padding: 6px 0; font-size: 15px; color: #111827; font-weight: 600;">
                        {{ $invoiceNumber }}
                    </td>
                </tr>
            @endif
        </table>
    </div>

    {{-- Invoice download — highlighted section (invoice is NOT attached) --}}
    <div class="dm-card" style="
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-radius: 12px;
        padding: 18px 22px;
        margin: 24px 0;
    ">
        <p class="dm-h" style="margin:0 0 8px;font-size:15px;font-weight:700;color:#166534;">
            &#128196;&nbsp; Your Invoice is Ready
        </p>
        <p class="dm-p" style="margin:0;font-size:14px;line-height:1.7;color:#15803d;">
            Your invoice has been generated and is available in your account.
            You can download it anytime from:<br>
            <strong>Profile &rarr; Billing &amp; Payments &rarr; Premium Subscriptions &rarr; Invoice</strong>
        </p>
    </div>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 24px 0;">
        <tr>
            <td align="center" style="padding: 6px;">
                @include('emails.partials.cta-button', [
                    'url'   => $billingUrl,
                    'label' => 'View Billing & Download Invoice',
                    'color' => 'primary',
                    'size'  => 'lg',
                ])
            </td>
        </tr>
    </table>

    <div class="dm-card dm-p" style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:14px 16px;margin-top:24px;color:#1e40af;font-size:14px;line-height:1.7;">
        <p style="margin:0;">If you have any questions about your subscription or billing, reply to this email or contact us at <a href="mailto:support@startyourstory.in" style="color:#1e40af;">support@startyourstory.in</a>.</p>
    </div>

    <p style="margin-top:24px;">Thank you for choosing us,<br><strong>Team StartYourStory</strong></p>

</div>
@endsection
