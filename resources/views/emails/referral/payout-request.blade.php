{{--
    Referral reward — ask the user to add payout details.
    Variables: $name, $amount, $payoutUrl.
    Migrated from a standalone HTML shell to the shared premium layout —
    body content unchanged.
--}}
@extends('emails.layouts.premium', [
    'title'     => 'Add your payout details',
    'preheader' => 'Your referral reward of ₹' . number_format($amount) . ' is waiting to be paid.',
])

@section('content')
<div class="dm-p" style="font-family:'Inter',Arial,Helvetica,sans-serif;font-size:15px;line-height:24px;color:#475569;">

    <p class="dm-p" style="margin:0 0 14px;font-size:16px;color:#334155;">
        Hello <strong class="dm-h" style="color:#0f172a;">{{ $name }}</strong>,
    </p>

    <h1 class="dm-h" style="margin:0;font-size:24px;line-height:34px;font-weight:800;color:#0f172a;">
        Your referral reward is ready
    </h1>

    <p style="margin:14px 0 0 0;">
        You have a referral reward of
        <strong class="dm-h" style="color:#0f172a;">&#8377;{{ number_format($amount) }}</strong>
        waiting to be paid. To receive it, please add your payout details
        (UPI is the fastest) on your account.
    </p>

    {{-- CTA --}}
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:26px 0 8px;">
        <tr>
            <td class="dm-btn" align="center" bgcolor="#2563eb" style="background-color:#2563eb;border-radius:12px;">
                <a href="{{ $payoutUrl }}" style="display:block;text-align:center;color:#ffffff;font-size:17px;font-weight:700;padding:16px 24px;text-decoration:none;border-radius:12px;">
                    Add Payout Details
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:18px 0 16px;font-size:13px;line-height:21px;color:#94a3b8;">
        If the button does not work, copy and paste this link into your browser:<br>
        <span class="dm-hi" style="color:#2563eb;word-break:break-all;">{{ $payoutUrl }}</span>
    </p>

</div>
@endsection
