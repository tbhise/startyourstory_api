<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add your payout details</title>
</head>
<body style="margin:0; padding:0; background-color:#eef2f7; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#eef2f7;">
        <tr>
            <td align="center" style="padding:28px 14px;">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px; max-width:600px; background:#ffffff; border-radius:14px; overflow:hidden; box-shadow:0 6px 28px rgba(15,40,90,0.07);">
                    <tr>
                        <td style="background-image:linear-gradient(135deg,#3b82f6,#1e3a8a); padding:30px 40px;">
                            <div style="font-size:22px; font-weight:800; color:#ffffff; letter-spacing:-0.4px;">StartYourStory</div>
                            <div style="font-size:13px; color:#bfdbfe; padding-top:4px;">Referral Rewards</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px 40px 8px 40px;">
                            <p style="margin:0; font-size:16px; color:#334155;">Hello <strong style="color:#0f172a;">{{ $name }}</strong>,</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:12px 40px 0 40px;">
                            <h1 style="margin:0; font-size:24px; line-height:34px; font-weight:800; color:#0f172a;">
                                Your referral reward is ready
                            </h1>
                            <p style="margin:14px 0 0 0; font-size:15px; line-height:24px; color:#475569;">
                                You have a referral reward of
                                <strong style="color:#0f172a;">&#8377;{{ number_format($amount) }}</strong>
                                waiting to be paid. To receive it, please add your payout details
                                (UPI is the fastest) on your account.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:26px 40px 8px 40px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr>
                                    <td align="center" bgcolor="#2563eb" style="background-image:linear-gradient(135deg,#3b82f6,#1e40af); border-radius:12px;">
                                        <a href="{{ $payoutUrl }}" style="display:block; text-align:center; color:#ffffff; font-size:17px; font-weight:700; padding:16px 24px; text-decoration:none; border-radius:12px;">
                                            Add Payout Details
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 40px 36px 40px;">
                            <p style="margin:0; font-size:13px; line-height:21px; color:#94a3b8;">
                                If the button does not work, copy and paste this link into your browser:<br>
                                <span style="color:#2563eb; word-break:break-all;">{{ $payoutUrl }}</span>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#f1f5f9; border-top:1px solid #e2e8f0; padding:22px 40px; text-align:center;">
                            <p style="margin:0; font-size:12px; color:#94a3b8;">
                                &copy; {{ date('Y') }} StartYourStory. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
