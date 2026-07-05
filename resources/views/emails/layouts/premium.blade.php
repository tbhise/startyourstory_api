<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title>{{ $title ?? 'StartYourStory' }}</title>
    <!--[if mso]>
    <noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript>
    <![endif]-->
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 0; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table { border-collapse: collapse; border-spacing: 0; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        a { text-decoration: none; }

        /* ── Mobile (≤ 700px): stack columns, tighten padding ─────────────── */
        @media only screen and (max-width: 700px) {
            .wrapper-pad   { padding: 12px 8px !important; }
            .content-pad   { padding: 24px 18px 8px !important; }
            .header-pad    { padding: 18px 18px !important; }
            .footer-pad    { padding: 22px 18px 16px !important; }
            .hide-sm       { display: none !important; width: 0 !important; max-height: 0 !important; overflow: hidden !important; }
            .stack-col     { display: block !important; width: 100% !important; max-width: 100% !important; }
            .stack-gap     { padding: 0 0 10px 0 !important; }
            .stack-card    { display: block !important; width: 100% !important; max-width: 100% !important; margin-bottom: 10px !important; }
            .gap-col       { display: none !important; width: 0 !important; max-height: 0 !important; overflow: hidden !important; }
            .btn-block     { width: 100% !important; }
            .btn-block a   { display: block !important; }
            .h1-sm         { font-size: 24px !important; line-height: 32px !important; }
            .cta-pad       { padding: 20px 18px !important; }
            .foot-col      { padding: 0 0 18px 0 !important; }
        }

        /* ── Dark mode (Apple Mail, Outlook apps via data-ogsc) ───────────── */
        @media (prefers-color-scheme: dark) {
            /* Page slightly lighter than the email panel so the mail reads as
               an elevated surface instead of one dominant black field. */
            .dm-bg      { background-color: #161B22 !important; }
            .dm-panel   { background-color: #0D1117 !important; }
            .dm-card    { background-color: #161B22 !important; border-color: #30363D !important; }
            .dm-cta     { background-color: #161B22 !important; border-color: #30363D !important; }
            .dm-icon    { background-color: #1E2836 !important; }
            .dm-h       { color: #F8FAFC !important; }
            .dm-p       { color: #94A3B8 !important; }
            .dm-hi      { color: #3B82F6 !important; }
            .dm-line    { background-color: #30363D !important; border-color: #30363D !important; }
            .dm-btn     { background-color: #3B82F6 !important; }
            .dm-row-a   { background-color: #161B22 !important; }
            .dm-row-b   { background-color: #0D1117 !important; }
        }
        [data-ogsc] .dm-bg      { background-color: #161B22 !important; }
        [data-ogsc] .dm-panel   { background-color: #0D1117 !important; }
        [data-ogsc] .dm-card    { background-color: #161B22 !important; border-color: #30363D !important; }
        [data-ogsc] .dm-cta     { background-color: #161B22 !important; border-color: #30363D !important; }
        [data-ogsc] .dm-icon    { background-color: #1E2836 !important; }
        [data-ogsc] .dm-h       { color: #F8FAFC !important; }
        [data-ogsc] .dm-p       { color: #94A3B8 !important; }
        [data-ogsc] .dm-hi      { color: #3B82F6 !important; }
        [data-ogsc] .dm-line    { background-color: #30363D !important; border-color: #30363D !important; }
        [data-ogsc] .dm-btn     { background-color: #3B82F6 !important; }
        [data-ogsc] .dm-row-a   { background-color: #161B22 !important; }
        [data-ogsc] .dm-row-b   { background-color: #0D1117 !important; }
    </style>
</head>

<body class="dm-bg" style="margin:0;padding:0;background-color:#F5F7FA;font-family:'Inter',Arial,Helvetica,sans-serif;">

    {{-- Hidden inbox preview text --}}
    @isset($preheader)
        <div style="display:none;max-height:0;overflow:hidden;mso-hide:all;font-size:1px;line-height:1px;color:#F5F7FA;">
            {{ $preheader }}&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;
        </div>
    @endisset

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" class="dm-bg wrapper-pad" style="background-color:#F5F7FA;padding:24px 12px;">
        <tr>
            <td align="center">

                <table role="presentation" width="680" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:680px;">

                    {{-- ════════════════════ HEADER (reusable) ════════════════════ --}}
                    <tr>
                        <td class="header-pad" style="background-color:#1D4ED8;border-radius:14px 14px 0 0;padding:20px 24px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    {{-- Logo + brand --}}
                                    <td align="left" valign="middle">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td valign="middle" width="44" height="44" align="center" style="width:44px;height:44px;background-color:#FFFFFF;border-radius:10px;text-align:center;">
                                                    <img src="https://startyourstory.in/favicon.ico" width="28" height="28" alt="SYS" style="display:inline-block;width:28px;height:28px;border:0;vertical-align:middle;">
                                                </td>
                                                <td width="12" style="width:12px;font-size:0;line-height:0;">&nbsp;</td>
                                                <td valign="middle">
                                                    <p style="margin:0;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:19px;font-weight:800;color:#FFFFFF;line-height:24px;">StartYourStory</p>
                                                    <p style="margin:2px 0 0;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:11px;font-weight:500;color:#BFDBFE;line-height:14px;">Your Story. Our Platform.</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    {{-- Right tagline (hidden on mobile) --}}
                                    <td align="right" valign="middle" class="hide-sm">
                                        <p style="margin:0;padding-left:16px;border-left:1px solid #4A73E8;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:11.5px;font-weight:500;color:#C7D2FE;line-height:17px;text-align:right;">
                                            Connecting CA Students,<br>Professionals and Firms.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- ════════════════════ BODY (per-email) ═════════════════════ --}}
                    <tr>
                        <td class="dm-panel content-pad" style="background-color:#FFFFFF;padding:42px 34px 18px;">
                            @yield('content')
                        </td>
                    </tr>

                    {{-- Body → footer transition: settle space + hairline divider --}}
                    <tr>
                        <td class="dm-panel" style="background-color:#FFFFFF;padding:6px 34px 26px;">
                            <div class="dm-line" style="height:1px;line-height:1px;font-size:0;background-color:#E5E7EB;">&nbsp;</div>
                        </td>
                    </tr>

                    {{-- ════════════════════ FOOTER (reusable) ════════════════════ --}}
                    <tr>
                        <td class="footer-pad" style="background-color:#0B2E6B;border-radius:0 0 14px 14px;padding:26px 28px 18px;">

                            {{-- 4 columns: brand / quick links / company / follow --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td class="stack-col foot-col" width="32%" valign="top" style="padding:0 14px 0 0;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td valign="middle" width="30" height="30" align="center" style="width:30px;height:30px;background-color:#FFFFFF;border-radius:7px;text-align:center;">
                                                    <img src="https://startyourstory.in/favicon.ico" width="20" height="20" alt="SYS" style="display:inline-block;width:20px;height:20px;border:0;vertical-align:middle;">
                                                </td>
                                                <td width="8" style="width:8px;font-size:0;">&nbsp;</td>
                                                <td valign="middle">
                                                    <p style="margin:0;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:14px;font-weight:700;color:#FFFFFF;">StartYourStory</p>
                                                </td>
                                            </tr>
                                        </table>
                                        <p style="margin:10px 0 0;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:11px;color:#9DB4DE;line-height:17px;">
                                            Connecting CA Students, Professionals and Firms.
                                        </p>
                                    </td>




                                    {{-- <td class="stack-col" width="23%" valign="top">
                                        <p style="margin:0 0 9px;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:12px;font-weight:700;color:#FFFFFF;">Follow Us</p>

                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td width="28" height="28" align="center" style="width:28px;height:28px;background-color:#0A66C2;border-radius:14px;text-align:center;"><a href="{{ $front }}" style="font-family:Arial,sans-serif;font-size:11px;font-weight:700;color:#FFFFFF;">in</a></td>
                                                <td width="8" style="width:8px;font-size:0;">&nbsp;</td>
                                                <td width="28" height="28" align="center" style="width:28px;height:28px;background-color:#E4405F;border-radius:14px;text-align:center;"><a href="{{ $front }}" style="font-family:Arial,sans-serif;font-size:11px;font-weight:700;color:#FFFFFF;">ig</a></td>
                                                <td width="8" style="width:8px;font-size:0;">&nbsp;</td>
                                                <td width="28" height="28" align="center" style="width:28px;height:28px;background-color:#FF0000;border-radius:14px;text-align:center;"><a href="{{ $front }}" style="font-family:Arial,sans-serif;font-size:10px;font-weight:700;color:#FFFFFF;">&#9654;</a></td>
                                            </tr>
                                            <tr><td colspan="5" height="8" style="height:8px;font-size:0;">&nbsp;</td></tr>
                                            <tr>
                                                <td width="28" height="28" align="center" style="width:28px;height:28px;background-color:#1877F2;border-radius:14px;text-align:center;"><a href="{{ $front }}" style="font-family:Arial,sans-serif;font-size:12px;font-weight:700;color:#FFFFFF;">f</a></td>
                                                <td width="8" style="width:8px;font-size:0;">&nbsp;</td>
                                                <td width="28" height="28" align="center" style="width:28px;height:28px;background-color:#111111;border-radius:14px;text-align:center;"><a href="{{ $front }}" style="font-family:Arial,sans-serif;font-size:11px;font-weight:700;color:#FFFFFF;">X</a></td>
                                                <td>&nbsp;</td>
                                            </tr>
                                        </table>
                                    </td> --}}
                                </tr>
                            </table>

                            {{-- Divider --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr><td height="1" style="height:1px;line-height:1px;font-size:0;background-color:#274B8F;padding:0;">&nbsp;</td></tr>
                            </table>

                            {{-- Contact row --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:14px;">
                                <tr>
                                    <td align="center">
                                        <p style="margin:0;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:11.5px;color:#B9CBEF;line-height:18px;">
                                            &#9993;&nbsp; <a href="mailto:contact@startyourstory.in" style="color:#B9CBEF;">contact@startyourstory.in</a>
                                            &nbsp;&nbsp;|&nbsp;&nbsp;
                                            &#127760;&nbsp; <a href="https://www.startyourstory.in" style="color:#B9CBEF;">www.startyourstory.in</a>
                                        </p>
                                        <p style="margin:10px 0 0;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:11px;color:#8FA3CC;">
                                            © {{ date('Y') }} StartYourStory. All rights reserved.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>

</html>
