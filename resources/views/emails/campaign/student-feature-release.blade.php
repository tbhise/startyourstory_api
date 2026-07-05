
@extends('emails.layouts.premium', [
    'title'     => 'Big updates are now live on StartYourStory!',
    'preheader' => 'Direct messaging, resume builder, app install and instant notifications — top CA firms are actively hiring.',
])

@section('content')

    {{-- ── Hero ─────────────────────────────────────────────────────────── --}}
    <p class="dm-p" style="margin:0 0 14px;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:14px;color:#475569;">
        Hello {{ $name ?? 'Candidate' }},
    </p>

    <h1 class="dm-h h1-sm" style="margin:0 0 14px;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:28px;line-height:36px;font-weight:800;color:#0F172A;">
        Big updates are now live on StartYourStory!
    </h1>

    <p class="dm-p" style="margin:0 0 12px;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:14px;line-height:22px;color:#475569;">
        We&rsquo;ve launched powerful new features to make your job search experience faster, smarter and more effective.
    </p>

    <p class="dm-hi" style="margin:0 0 26px;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:14px;line-height:21px;font-weight:700;color:#1D4ED8;">
        Top CA firms are actively hiring talented students like you.
    </p>

    {{-- ── Section title with flanking lines ────────────────────────────── --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 18px;">
        <tr>
            <td valign="middle"><div class="dm-line" style="height:1px;line-height:1px;font-size:0;background-color:#E5E7EB;">&nbsp;</div></td>
            <td valign="middle" width="180" align="center" style="width:180px;white-space:nowrap;padding:0 6px;">
                <span class="dm-h" style="font-family:'Inter',Arial,Helvetica,sans-serif;font-size:17px;font-weight:800;color:#0F172A;">What&rsquo;s New for You</span>
            </td>
            <td valign="middle"><div class="dm-line" style="height:1px;line-height:1px;font-size:0;background-color:#E5E7EB;">&nbsp;</div></td>
        </tr>
    </table>

    {{-- ── 4 feature cards (equal height: the td IS the card; stack on mobile) ── --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 24px;">
        <tr>
            <td class="stack-card dm-card" width="24%" align="center" valign="top" style="background-color:#FFFFFF;border:1px solid #E5E7EB;border-radius:12px;padding:18px 10px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                    <tr><td class="dm-icon" width="52" height="52" align="center" valign="middle" style="width:52px;height:52px;background-color:#EAF1FF;border-radius:26px;text-align:center;font-size:22px;line-height:52px;">&#128172;</td></tr>
                </table>
                <p class="dm-h" style="margin:12px 0 6px;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:12.5px;font-weight:700;color:#0F172A;line-height:17px;">Direct Messaging<br>with CA Firms</p>
                <p class="dm-p" style="margin:0;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:11px;line-height:16px;color:#64748B;">Connect directly with firms and communicate faster during your hiring journey.</p>
            </td>
            <td class="gap-col" width="8" style="width:8px;font-size:0;">&nbsp;</td>
            <td class="stack-card dm-card" width="24%" align="center" valign="top" style="background-color:#FFFFFF;border:1px solid #E5E7EB;border-radius:12px;padding:18px 10px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                    <tr><td class="dm-icon" width="52" height="52" align="center" valign="middle" style="width:52px;height:52px;background-color:#E6F6EC;border-radius:26px;text-align:center;font-size:22px;line-height:52px;">&#128196;</td></tr>
                </table>
                <p class="dm-h" style="margin:12px 0 6px;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:12.5px;font-weight:700;color:#0F172A;line-height:17px;">Resume Builder</p>
                <p class="dm-p" style="margin:0;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:11px;line-height:16px;color:#64748B;">Create professional, ATS-friendly resumes in minutes and stand out.</p>
            </td>
            <td class="gap-col" width="8" style="width:8px;font-size:0;">&nbsp;</td>
            <td class="stack-card dm-card" width="24%" align="center" valign="top" style="background-color:#FFFFFF;border:1px solid #E5E7EB;border-radius:12px;padding:18px 10px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                    <tr><td class="dm-icon" width="52" height="52" align="center" valign="middle" style="width:52px;height:52px;background-color:#F1EBFF;border-radius:26px;text-align:center;font-size:22px;line-height:52px;">&#128241;</td></tr>
                </table>
                <p class="dm-h" style="margin:12px 0 6px;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:12.5px;font-weight:700;color:#0F172A;line-height:17px;">Install as App</p>
                <p class="dm-p" style="margin:0;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:11px;line-height:16px;color:#64748B;">Install StartYourStory as an app and access your dashboard anytime, anywhere.</p>
            </td>
            <td class="gap-col" width="8" style="width:8px;font-size:0;">&nbsp;</td>
            <td class="stack-card dm-card" width="24%" align="center" valign="top" style="background-color:#FFFFFF;border:1px solid #E5E7EB;border-radius:12px;padding:18px 10px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                    <tr><td class="dm-icon" width="52" height="52" align="center" valign="middle" style="width:52px;height:52px;background-color:#FFF3DF;border-radius:26px;text-align:center;font-size:22px;line-height:52px;">&#128276;</td></tr>
                </table>
                <p class="dm-h" style="margin:12px 0 6px;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:12.5px;font-weight:700;color:#0F172A;line-height:17px;">Instant Notifications</p>
                <p class="dm-p" style="margin:0;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:11px;line-height:16px;color:#64748B;">Get real-time alerts for jobs, messages and interview activities.</p>
            </td>
        </tr>
    </table>

    {{-- ── Re-engagement CTA card ───────────────────────────────────────── --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" class="dm-cta" style="background-color:#EFF4FF;border:1px solid #DBE7FF;border-radius:12px;margin:0 0 26px;">
        <tr>
            <td class="cta-pad" style="padding:22px 24px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td class="stack-col stack-gap" width="55%" valign="middle" style="padding:0 16px 0 0;">
                            <p class="dm-h" style="margin:0 0 8px;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:17px;font-weight:800;color:#0F172A;">We miss you!</p>
                            <p class="dm-p" style="margin:0 0 8px;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:12.5px;line-height:19px;color:#475569;">
                                Complete your profile and stay active to improve your visibility and unlock better opportunities.
                            </p>
                            <p class="dm-p" style="margin:0;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:12.5px;line-height:19px;color:#475569;">
                                If your profile is already complete, log in and explore our newly launched features.
                            </p>
                        </td>
                        <td class="stack-col" width="45%" valign="middle" align="center">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" class="btn-block" style="margin:0 auto;">
                                <tr>
                                    <td class="dm-btn" align="center" style="background-color:#1D4ED8;border-radius:9px;">
                                        <a href="https://startyourstory.in/login" style="display:inline-block;padding:13px 26px;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:13.5px;font-weight:700;color:#FFFFFF;">Explore StartYourStory</a>
                                    </td>
                                </tr>
                            </table>
                            <p class="dm-p" style="margin:10px 0 0;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:10px;color:#64748B;line-height:15px;white-space:nowrap;">
                                &#128737;&#65039; Secure &bull; Trusted &bull; Built for CA Community
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ── Stay-updated benefits ────────────────────────────────────────── --}}
    <p class="dm-h" align="center" style="margin:0 0 14px;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:15px;font-weight:800;color:#0F172A;text-align:center;">
        Stay updated so you never miss:
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 26px;">
        <tr>
            <td class="stack-card dm-card" width="32%" valign="middle" style="background-color:#FFFFFF;border:1px solid #E5E7EB;border-radius:10px;padding:12px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td class="dm-icon" width="34" height="34" align="center" valign="middle" style="width:34px;height:34px;background-color:#EAF1FF;border-radius:8px;text-align:center;font-size:15px;line-height:34px;">&#128188;</td>
                        <td width="10" style="width:10px;font-size:0;">&nbsp;</td>
                        <td valign="middle">
                            <p class="dm-h" style="margin:0 0 2px;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:11.5px;font-weight:700;color:#0F172A;">New job opportunities</p>
                            <p class="dm-p" style="margin:0;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:10.5px;line-height:15px;color:#64748B;">Discover the latest jobs that match your career goals.</p>
                        </td>
                    </tr>
                </table>
            </td>
            <td class="gap-col" width="8" style="width:8px;font-size:0;">&nbsp;</td>
            <td class="stack-card dm-card" width="33%" valign="middle" style="background-color:#FFFFFF;border:1px solid #E5E7EB;border-radius:10px;padding:12px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td class="dm-icon" width="34" height="34" align="center" valign="middle" style="width:34px;height:34px;background-color:#E6F6EC;border-radius:8px;text-align:center;font-size:15px;line-height:34px;">&#128172;</td>
                        <td width="10" style="width:10px;font-size:0;">&nbsp;</td>
                        <td valign="middle">
                            <p class="dm-h" style="margin:0 0 2px;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:11.5px;font-weight:700;color:#0F172A;">Messages from CA firms</p>
                            <p class="dm-p" style="margin:0;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:10.5px;line-height:15px;color:#64748B;">Never miss important messages and hiring updates.</p>
                        </td>
                    </tr>
                </table>
            </td>
            <td class="gap-col" width="8" style="width:8px;font-size:0;">&nbsp;</td>
            <td class="stack-card dm-card" width="32%" valign="middle" style="background-color:#FFFFFF;border:1px solid #E5E7EB;border-radius:10px;padding:12px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td class="dm-icon" width="34" height="34" align="center" valign="middle" style="width:34px;height:34px;background-color:#FFF3DF;border-radius:8px;text-align:center;font-size:15px;line-height:34px;">&#128197;</td>
                        <td width="10" style="width:10px;font-size:0;">&nbsp;</td>
                        <td valign="middle">
                            <p class="dm-h" style="margin:0 0 2px;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:11.5px;font-weight:700;color:#0F172A;">Interview invites</p>
                            <p class="dm-p" style="margin:0;font-family:'Inter',Arial,Helvetica,sans-serif;font-size:10.5px;line-height:15px;color:#64748B;">Get notified instantly about interview opportunities.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

@endsection
