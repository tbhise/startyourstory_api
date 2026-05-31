@php

    $userType = $userType ?? 'student';

    switch ($userType) {
        case 'firm':
            $headline = 'Welcome to Start Your Story 🎉';

            $intro =
                'Thank you for joining Start Your Story. Your firm can now connect with talented CA students, semi-qualified professionals and Chartered Accountants from across India.';

            $description =
                'Your account has been successfully created. Complete your firm profile, publish opportunities and start connecting with suitable candidates.';

            $ctaText = 'Start Hiring';

            $nextSteps = [
                'Complete your firm profile',
                'Post job opportunities',
                'Review candidate applications',
                'Schedule interviews with applicants',
            ];

            break;

        case 'creator':
            $headline = 'Welcome to Start Your Story 🎉';

            $intro =
                'Thank you for joining Start Your Story. Build your professional brand, share your expertise and engage with aspiring finance professionals.';

            $description =
                'Your creator account is ready. Complete your profile and begin building your presence within the CA community.';

            $ctaText = 'Complete Creator Profile';

            $nextSteps = [
                'Complete your creator profile',
                'Share valuable content',
                'Grow your professional audience',
                'Connect with students and professionals',
            ];

            break;

        default:
            $headline = 'Welcome to Start Your Story 🎉';

            $intro =
                'Thank you for joining Start Your Story, India\'s dedicated platform connecting CA students, semi-qualified professionals, qualified Chartered Accountants and firms.';

            $description =
                'Your account has been successfully created. Complete your profile, explore opportunities and start building meaningful professional connections.';

            $ctaText = 'Explore Opportunities';

            $nextSteps = [
                'Complete your profile',
                'Explore available opportunities',
                'Apply to relevant positions',
                'Connect with firms and professionals',
            ];

            break;
    }

@endphp




<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Start Your Story</title>
</head>

<body
    style="
    margin:0;
    padding:0;
    background:#f8fafc;
    font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;
">

    <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="center" style="padding:40px 20px;">

                <table width="600" cellpadding="0" cellspacing="0" border="0"
                    style="
                        max-width:600px;
                        background:#ffffff;
                        border-radius:18px;
                        overflow:hidden;
                        border:1px solid #e5e7eb;
                        box-shadow:0 10px 30px rgba(15,23,42,0.06);
                   ">

                    <!-- Header -->
                    <tr>
                        <td align="center"
                            style="
                            background:linear-gradient(135deg,#1e40af 0%,#2563eb 100%);
                            padding:40px 30px;
                        ">

                            <img src="https://startyourstory.in/assets/sys-logo-Ol8kKW1e.webp" alt="Start Your Story"
                                width="220"
                                style="
                                max-width:220px;
                                display:block;
                            " />

                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding:45px 40px;">

                            <h1
                                style="
                            margin:0 0 20px;
                            color:#111827;
                            font-size:30px;
                            line-height:38px;
                            font-weight:700;
                        ">
                                {{ $headline }}
                            </h1>

                            <p
                                style="
                            margin:0 0 18px;
                            color:#4b5563;
                            font-size:16px;
                            line-height:28px;
                        ">
                                Hello <strong>{{ $name }}</strong>,
                            </p>

                            <p
                                style="
                            margin:0 0 20px;
                            color:#4b5563;
                            font-size:16px;
                            line-height:28px;
                        ">
                                {{ $intro }}
                            </p>

                            <p
                                style="
                            margin:0 0 25px;
                            color:#4b5563;
                            font-size:16px;
                            line-height:28px;
                        ">
                                {{ $description }}
                            </p>

                            @if (!empty($couponCode))
                                <div
                                    style="
                            background:#eff6ff;
                            border:1px solid #bfdbfe;
                            border-radius:14px;
                            padding:24px;
                            text-align:center;
                            margin:30px 0;
                        ">

                                    <div
                                        style="
                                color:#1e40af;
                                font-size:13px;
                                font-weight:600;
                                letter-spacing:1px;
                                text-transform:uppercase;
                                margin-bottom:12px;
                            ">
                                        Your Welcome Coupon
                                    </div>

                                    <div
                                        style="
                                color:#1d4ed8;
                                font-size:32px;
                                font-weight:800;
                                letter-spacing:3px;
                            ">
                                        {{ $couponCode }}
                                    </div>

                                </div>
                            @endif

                            <div style="text-align:center;margin-top:35px;">

                                <a href="https://startyourstory.in/login"
                                    style="
                                    display:inline-block;
                                    background:linear-gradient(135deg,#1e40af 0%,#2563eb 100%);
                                    color:#ffffff;
                                    text-decoration:none;
                                    padding:14px 32px;
                                    border-radius:10px;
                                    font-size:15px;
                                    font-weight:600;
                               ">
                                    {{ $ctaText }}
                                </a>

                            </div>

                            <div
                                style="
                            margin-top:40px;
                            padding-top:30px;
                            border-top:1px solid #e5e7eb;
                        ">

                                <h3
                                    style="
                                margin:0 0 16px;
                                color:#111827;
                                font-size:18px;
                            ">
                                    What's Next?
                                </h3>

                                <table cellpadding="0" cellspacing="0" border="0">
                                    <tr>
                                        <td style="color:#4b5563;font-size:15px;line-height:30px;">
                                            @foreach ($nextSteps as $step)
                                                ✓ {{ $step }}<br>
                                            @endforeach
                                        </td>
                                    </tr>
                                </table>

                            </div>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td
                            style="
                        background:#f8fafc;
                        padding:25px;
                        text-align:center;
                        border-top:1px solid #e5e7eb;
                    ">

                            <p
                                style="
                            margin:0 0 8px;
                            color:#6b7280;
                            font-size:14px;
                        ">
                                © {{ date('Y') }} Start Your Story. All Rights Reserved.
                            </p>

                            <p
                                style="
                            margin:0;
                            color:#9ca3af;
                            font-size:12px;
                            line-height:20px;
                        ">
                                This is an automated email from Start Your Story.
                                Please do not reply directly to this message.
                            </p>

                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>

</html>
