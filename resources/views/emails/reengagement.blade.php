@php
    // Self-contained template: derives heading/lead/benefits from the userType +
    // verified flag passed in by ReEngagementMail (or the preview route). Keeping
    // this computation inside the view means the mailable/artisan command pass only
    // name, userType, verified and cta — no sending logic changes required.
    $userType = $userType ?? 'student';
    $verified = $verified ?? false;

    switch ($userType) {
        case 'firm':
            $heading = $verified
                ? 'Your next great hire is waiting'
                : 'Verify your email to start hiring';
            $lead = $verified
                ? "You're almost there — complete your firm profile to unlock the full power of Start Your Story:"
                : 'Verify your email to activate your firm account and unlock the full power of Start Your Story:';
            $benefits = [
                'Better candidate engagement',
                'More trust from applicants',
                'Higher-quality applications',
                'Post jobs',
                'Hire right talent faster',
            ];
            break;

        case 'creator':
            $heading = $verified
                ? "You're one step from more projects"
                : 'Verify your email to get discovered';
            $lead = $verified
                ? "You're almost there — complete your creator profile to make the most of Start Your Story:"
                : 'Verify your email to activate your creator profile and make the most of Start Your Story:';
            $benefits = [
                'Better visibility to firms',
                'More project opportunities',
                'Higher chances of collaboration',
                'Stronger profile credibility',
            ];
            break;

        default: // student
            $heading = $verified
                ? "You're almost ready to get hired"
                : 'Verify your email to start applying';
            $lead = $verified
                ? "You're almost there — complete your profile to get the most out of Start Your Story:"
                : 'Verify your email to activate your account and get the most out of Start Your Story:';
            $benefits = [
                'Apply to jobs',
                'Better job recommendations',
                'More visibility to firms',
                'Faster shortlisting',
            ];
            break;
    }
@endphp
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="color-scheme" content="light only">
    <meta name="supported-color-schemes" content="light only">
    <title>{{ $heading }}</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <style>
        :root { color-scheme: light only; supported-color-schemes: light only; }

        /* Mobile / small-screen overrides. Outlook (Windows) ignores these and
           falls back to the fixed 640px table width, which is the desired desktop look. */
        @media only screen and (max-width: 640px) {
            .sys-wrap { padding: 16px 0 !important; }
            .sys-container { width: 100% !important; max-width: 100% !important; border-radius: 0 !important; }
            .sys-px { padding-left: 22px !important; padding-right: 22px !important; }
            .sys-header { padding: 26px 22px !important; }
            .sys-footer { padding-left: 22px !important; padding-right: 22px !important; }
            .sys-heading { font-size: 25px !important; line-height: 36px !important; }
            .sys-brand { font-size: 22px !important; line-height: 28px !important; }
        }
        a { text-decoration: none; }
    </style>
</head>
<body style="margin:0; padding:0; background-color:#eef2f7; -webkit-font-smoothing:antialiased; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">

    <!-- Preheader -->
    <div style="display:none; max-height:0; overflow:hidden; opacity:0; font-size:1px; line-height:1px; color:#eef2f7;">
        {{ $lead }}
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#eef2f7;">
        <tr>
            <td class="sys-wrap" align="center" style="padding:28px 14px;">

                <table role="presentation" class="sys-container" width="640" cellpadding="0" cellspacing="0" border="0" style="width:640px; max-width:640px; background-color:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 6px 28px rgba(15,40,90,0.07);">

                    <!-- ============ BRANDING HEADER ============ -->
                    <tr>
                        <td class="sys-header" style="background:#1e3a8a; background-image:linear-gradient(135deg,#3b82f6 0%,#2563eb 45%,#1e3a8a 100%); padding:34px 44px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center">
                                <tr>
                                    <td valign="middle" style="padding-right:16px;">
                                        <img src="https://startyourstory.in/favicon.ico" width="46" height="46" alt="StartYourStory" style="display:block; border-radius:12px; background:#ffffff; padding:6px; box-sizing:border-box; box-shadow:0 4px 12px rgba(15,40,90,0.20);">
                                    </td>
                                    <td valign="middle">
                                        <div class="sys-brand" style="font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:25px; font-weight:800; color:#ffffff; line-height:28px; letter-spacing:-0.4px;">
                                            StartYourStory
                                        </div>
                                        <div style="font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:14px; font-weight:500; color:#bfdbfe; line-height:20px; padding-top:4px; letter-spacing:0.2px;">
                                            Your Story. Our Platform.
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- ============ GREETING ============ -->
                    <tr>
                        <td class="sys-px" style="padding:32px 44px 0 44px;">
                            <p style="margin:0; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:17px; line-height:24px; color:#334155; font-weight:400;">
                                Hello <strong style="color:#0f172a; font-weight:700;">{{ $name }}</strong>,
                            </p>
                        </td>
                    </tr>

                    <!-- ============ HERO HEADING ============ -->
                    <tr>
                        <td class="sys-px" style="padding:10px 44px 0 44px;">
                            <h1 class="sys-heading" style="margin:0; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:28px; line-height:44px; font-weight:800; color:#0f172a; letter-spacing:-0.8px;">
                                {{ $heading }}
                            </h1>
                        </td>
                    </tr>

                    <!-- ============ LEAD ============ -->
                    <tr>
                        <td class="sys-px" style="padding:14px 44px 0 44px;">
                            <p style="margin:0; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:16px; line-height:26px; color:#64748b;">
                                {{ $lead }}
                            </p>
                        </td>
                    </tr>

                    <!-- ============ MOTIVATION BOX ============ -->
                    <tr>
                        <td class="sys-px" style="padding:22px 44px 0 44px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#eff6ff; border:1px solid #dbeafe; border-radius:12px; box-shadow:0 2px 8px rgba(37,99,235,0.06);">
                                <tr>
                                    <td valign="top" width="48" style="padding:20px 0 20px 20px;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td align="center" valign="middle" width="44" height="44" style="background-color:#dbeafe; border-radius:10px; font-size:20px; line-height:44px;">
                                                    &#128227;
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td valign="middle" style="padding:18px 22px 18px 16px; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                                        <div style="font-size:16px; line-height:23px; color:#0f172a; font-weight:700;">
                                            @if($userType === 'student')
                                                Top firms are actively searching for candidates like you.
                                            @elseif($userType === 'firm')
                                                Candidates trust complete profiles more and apply faster.
                                            @elseif($userType === 'creator')
                                                Complete profiles get more visibility and project opportunities.
                                            @endif
                                        </div>
                                        <div style="font-size:15px; line-height:23px; color:#475569; padding-top:4px;">
                                            {{ $lead }}
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- ============ BENEFITS (card rows) ============ -->
                    <tr>
                        <td class="sys-px" style="padding:26px 44px 0 44px;">
                            <p style="margin:0 0 16px 0; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:19px; line-height:25px; color:#0f172a; font-weight:700;">
                                @if($verified)
                                    With a complete profile, you can:
                                @else
                                    Once verified, you can:
                                @endif
                            </p>

                            @php $sysIcons = ['&#128188;','&#127919;','&#128065;','&#9889;','&#9989;']; @endphp
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                @foreach($benefits as $sysIndex => $benefit)
                                    <tr>
                                        <td style="padding-bottom:{{ $loop->last ? '0' : '10px' }};">
                                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f8fafc; border:1px solid #eef2f7; border-radius:12px; box-shadow:0 1px 3px rgba(15,40,90,0.04);">
                                                <tr>
                                                    <td valign="middle" width="56" style="padding:0px 0 0px 8px;">
                                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                                            <tr>
                                                                <td align="center" valign="middle" width="35" height="35" style="border-radius:10px; font-size:16px; line-height:38px;">
                                                                    {!! $sysIcons[$sysIndex % count($sysIcons)] !!}
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                    <td valign="middle" style="padding:6px 18px 6px 0px; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:14px; line-height:22px; color:#1e293b; font-weight:600;">
                                                        {{ $benefit }}
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>

                    <!-- ============ CTA HIERARCHY ============ -->
                    <tr>
                        <td class="sys-px" style="padding:28px 44px 0 44px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">

                                @if(! $verified)
                                    {{-- UNVERIFIED: Verify (primary, green) -> Complete Profile (secondary, outline) -> Login (tertiary) --}}
                                    <tr>
                                        <td style="padding-bottom:12px;">
                                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                                <tr>
                                                    <td align="center" bgcolor="#16a34a" style="background-color:#16a34a; background-image:linear-gradient(135deg,#22c55e,#15803d); border-radius:12px; box-shadow:0 8px 18px rgba(22,163,74,0.24);">
                                                        <a href="{{ $cta['verify'] }}" style="display:block; text-align:center; color:#ffffff; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:18px; font-weight:700; line-height:22px; padding:17px 24px; border-radius:12px;">
                                                            &#128737;&nbsp;&nbsp;Verify Email
                                                        </a>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding-bottom:22px;">
                                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                                <tr>
                                                    <td align="center" bgcolor="#ffffff" style="background-color:#ffffff; border:2px solid #2563eb; border-radius:12px; box-shadow:0 2px 6px rgba(37,99,235,0.08);">
                                                        <a href="{{ $cta['profile'] }}" style="display:block; text-align:center; color:#2563eb; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:18px; font-weight:700; line-height:22px; padding:15px 24px; border-radius:12px;">
                                                            &#128100;&nbsp;&nbsp;Complete Your Profile
                                                        </a>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td align="center" style="padding-bottom:4px;">
                                            <a href="{{ $cta['login'] }}" style="display:inline-block; color:#2563eb; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:15px; font-weight:700;">
                                                Or log in to Start Your Story&nbsp;&rarr;
                                            </a>
                                        </td>
                                    </tr>
                                @else
                                    {{-- VERIFIED: Complete Profile (primary, blue) -> Login (secondary) --}}
                                    <tr>
                                        <td style="padding-bottom:22px;">
                                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                                <tr>
                                                    <td align="center" bgcolor="#2563eb" style="background-color:#2563eb; background-image:linear-gradient(135deg,#3b82f6,#1e40af); border-radius:12px; box-shadow:0 8px 18px rgba(37,99,235,0.24);">
                                                        <a href="{{ $cta['profile'] }}" style="display:block; text-align:center; color:#ffffff; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:18px; font-weight:700; line-height:22px; padding:17px 24px; border-radius:12px;">
                                                            &#128100;&nbsp;&nbsp;Complete Your Profile
                                                        </a>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td align="center" style="padding-bottom:4px;">
                                            <a href="{{ $cta['login'] }}" style="display:inline-block; color:#2563eb; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:15px; font-weight:700;">
                                                Or log in to Start Your Story&nbsp;&rarr;
                                            </a>
                                        </td>
                                    </tr>
                                @endif

                            </table>
                        </td>
                    </tr>

                    <!-- ============ INFO BOX ============ -->
                    <tr>
                        <td class="sys-px" style="padding:26px 44px 36px 44px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#fffbeb; border:1px solid #fde68a; border-radius:12px; box-shadow:0 2px 8px rgba(217,119,6,0.06);">
                                <tr>
                                    <td valign="middle" width="44" style="padding:18px 0 18px 20px;">
                                        <span style="font-size:24px; line-height:24px;">&#9200;</span>
                                    </td>
                                    <td valign="middle" style="padding:18px 16px; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:15px; line-height:23px; color:#334155;">
                                        Finishing your setup takes <strong style="color:#0f172a;">less than two minutes</strong> and makes a big difference.
                                    </td>
                                    <td valign="middle" align="right" width="60" style="padding:14px 18px 14px 0; font-size:30px; line-height:30px;">
                                        &#9201;&#9989;
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- ============ FOOTER ============ -->
                    <tr>
                        <td class="sys-footer" align="center" style="background-color:#f1f5f9; border-top:1px solid #e2e8f0; padding:28px 44px 6px 44px;">
                            <p style="margin:0 0 5px 0; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:15px; line-height:22px; color:#475569; font-weight:600;">
                                Need help? We&rsquo;re here for you.
                            </p>
                            <p style="margin:0 0 16px 0; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:15px; line-height:22px;">
                                <a href="mailto:contact@startyourstory.in" style="color:#2563eb; font-weight:600; text-decoration:none;">contact@startyourstory.in</a>
                            </p>

                            {{-- Social icons — hidden for now; re-enable once handles/icons are confirmed.
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center">
                                <tr>
                                    <td style="padding:0 8px;">
                                        <a href="https://www.linkedin.com/company/startyourstory" style="text-decoration:none;">
                                            <img src="https://cdn.jsdelivr.net/gh/MajesticIcons/social-rounded/linkedin.png" width="34" height="34" alt="LinkedIn" style="display:block; border-radius:50%;">
                                        </a>
                                    </td>
                                    <td style="padding:0 8px;">
                                        <a href="https://www.instagram.com/startyourstory" style="text-decoration:none;">
                                            <img src="https://cdn.jsdelivr.net/gh/MajesticIcons/social-rounded/instagram.png" width="34" height="34" alt="Instagram" style="display:block; border-radius:50%;">
                                        </a>
                                    </td>
                                    <td style="padding:0 8px;">
                                        <a href="https://www.youtube.com/@startyourstory" style="text-decoration:none;">
                                            <img src="https://cdn.jsdelivr.net/gh/MajesticIcons/social-rounded/youtube.png" width="34" height="34" alt="YouTube" style="display:block; border-radius:50%;">
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            --}}
                        </td>
                    </tr>

                    <!-- Divider + copyright -->
                    <tr>
                        <td class="sys-footer" align="center" style="background-color:#f1f5f9; padding:0 44px 30px 44px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr><td style="border-top:1px solid #e2e8f0; padding-top:18px;">
                                    <p style="margin:0; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:12px; line-height:19px; color:#94a3b8; text-align:center;">
                                        &copy; {{ date('Y') }} StartYourStory. All rights reserved.<br>
                                        Connecting CA Students, Professionals and Firms.
                                    </p>
                                </td></tr>
                            </table>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>
