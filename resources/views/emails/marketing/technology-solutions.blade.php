@extends('emails.layouts.premium', [
    'title' => 'Website, Automation & Digital Solutions for Chartered Accountants',
    'preheader' => 'Websites, mobile apps, client portals and AI automation — built for CA firms.',
])

@section('content')
    @php
        // ── Our Technology Services (2 × 2 grid) ───────────────────────────
        $services = [
            [
                'icon' => '&#127760;', // globe
                'tint' => '#DCE7FF',
                'title' => 'Professional Website Development',
                'desc' => 'Modern, responsive, SEO-friendly websites that build credibility and generate enquiries.',
            ],
            [
                'icon' => '&#128241;', // mobile
                'tint' => '#D6F0E0',
                'title' => 'Mobile Application Development',
                'desc' => 'Android and iOS apps that simplify operations and improve client engagement.',
            ],
            [
                'icon' => '&#128187;', // laptop
                'tint' => '#FFE9C7',
                'title' => 'Custom Web Applications',
                'desc' => 'Client portals, practice management systems, dashboards and workflow automation.',
            ],
            [
                'icon' => '&#129302;', // robot
                'tint' => '#E7DCFB',
                'title' => 'AI Automation Solutions',
                'desc' => 'AI-powered workflows, document processing and chatbots that remove repetitive work.',
            ],
        ];

        // ── Already Have a Website? (2 columns of 4) ───────────────────────
        $upgrades = [
            ['Website redesign & modernization', 'Content updates'],
            ['Performance optimization', 'New feature development'],
            ['Security enhancements', 'Third-party integrations'],
            ['Regular maintenance', 'Custom enhancements'],
        ];

        // ── Why Choose Start Your Story? (same 2 × 2 card styling) ─────────
        $reasons = [
            [
                'icon' => '&#128101;', // people
                'title' => 'Built for CA Firms',
                'desc' => 'Built around how Chartered Accountant firms actually work.',
            ],
            [
                'icon' => '&lt;/&gt;', // code
                'title' => 'Modern &amp; Secure',
                'desc' => 'Current technologies, secure and reliable by default.',
            ],
            [
                'icon' => '&#128200;', // chart
                'title' => 'Scalable Solutions',
                'desc' => 'Grows with your firm and adapts to future needs.',
            ],
            [
                'icon' => '&#127911;', // headphones
                'title' => 'Dedicated Support',
                'desc' => 'With you from consultation to deployment and beyond.',
            ],
        ];

        // ── More Than Just Technology (full-width rows) ────────────────────
        // Tinted blocks are defined by their fill alone (no border) — only the white
        // 2-up cards need a border, since they sit on a white panel.
        $platform = [
            [
                'icon' => '&#127919;', // target
                'card' => '#ECFDF5',
                'tile' => '#10B981',
                // Mid-tone inks: readable on the light tint AND on the dark-mode card.
                'ink' => '#059669',
                'title' => 'Hire CA Talent',
                'desc' => 'Find Articleship, Semi-Qualified and Qualified CA candidates from one dedicated platform.',
            ],
            [
                'icon' => '&#11088;', // star
                'card' => '#F5F3FF',
                'tile' => '#8B5CF6',
                'ink' => '#7C3AED',
                'title' => 'Premium Hiring Tools',
                'desc' => 'Unlock advanced hiring features, premium visibility and enhanced recruitment capabilities.',
            ],
            [
                'icon' => '&#128187;', // laptop
                'card' => '#FFF7ED',
                'tile' => '#F97316',
                'ink' => '#EA580C',
                'title' => 'Technology Solutions',
                'desc' =>
                    'Partner with us for websites, mobile applications, custom software and AI-powered automation.',
            ],
        ];

        $sans = "'Inter',Arial,Helvetica,sans-serif";

        $radius = '16px';
        $pad = '28px 26px';
        $gapBlock = '16px';
    @endphp

    {{-- ══════════════════ HERO ══════════════════════════════════════════ --}}
    <p class="dm-p" style="margin:0 0 14px;font-family:{{ $sans }};font-size:14px;line-height:21px;color:#475569;">
        @if (!empty($firmName) && strtolower(trim($firmName)) !== 'there')
            Dear {{ $firmName }},
        @else
            Dear Sir/Madam,
        @endif
    </p>

    <h1 class="dm-h h1-sm"
        style="margin:0 0 18px;font-family:{{ $sans }};font-size:30px;line-height:40px;font-weight:800;color:#0F172A;">
        Digital Solutions Built<br>for CA Firms Like Yours
    </h1>

    {{-- Short brand rule under the headline --}}
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 24px;">
        <tr>
            <td width="84" height="4"
                style="width:84px;height:4px;line-height:4px;font-size:0;background-color:#1D4ED8;border-radius:2px;">
                &nbsp;</td>
        </tr>
    </table>

    <p class="dm-p"
        style="margin:0 0 16px;font-family:{{ $sans }};font-size:14.5px;line-height:24px;color:#475569;">
        In today&rsquo;s digital-first world, a professional online presence is no longer optional&mdash;it&rsquo;s an
        essential part of building credibility, attracting new clients, and delivering a better experience to existing
        ones.
    </p>

    <p class="dm-p"
        style="margin:0 0 16px;font-family:{{ $sans }};font-size:14.5px;line-height:24px;color:#475569;">
        Before contacting your firm, many clients search online to evaluate professional services. A strong digital presence
        helps create a lasting first impression and builds trust from the very beginning.
    </p>

    <p class="dm-p"
        style="margin:0 0 46px;font-family:{{ $sans }};font-size:14.5px;line-height:24px;color:#475569;">
        At <strong class="dm-h" style="color:#0F172A;">Start Your Story</strong>, we&rsquo;re excited to introduce
        <strong class="dm-h" style="color:#0F172A;">Technology Solutions</strong>&mdash;a new initiative dedicated to
        helping Chartered Accountant firms build modern, secure and scalable digital solutions tailored to their
        practice.
    </p>

    {{-- ══════════════════ OUR TECHNOLOGY SERVICES ═══════════════════════ --}}
    @include('emails.partials.section-title', ['label' => 'Our Technology Services'])
    @include('emails.partials.card-grid', ['items' => $services, 'gap' => 46])

    {{-- ══════════════════ ALREADY HAVE A WEBSITE? ═══════════════════════ border:1px solid #DBE7FF; --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" class="dm-cta"
        style="background-color:#EFF4FF;border-radius:{{ $radius }};margin:0 0 46px;">
        <tr>
            <td class="cta-pad" style="padding:{{ $pad }};">
                <p class="dm-hi"
                    style="margin:0 0 6px;font-family:{{ $sans }};font-size:20px;line-height:28px;font-weight:800;color:#1D4ED8;text-align:center;">
                    Already Have a Website?</p>
                <p class="dm-p"
                    style="margin:0 0 24px;font-family:{{ $sans }};font-size:13.5px;line-height:21px;color:#475569;text-align:center;">
                    We can help you take it to the next level.</p>

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                    @foreach ($upgrades as $pair)
                        <tr>
                            @foreach ($pair as $j => $item)
                                @if ($j > 0)
                                    <td class="gap-col" width="18" style="width:18px;font-size:0;line-height:0;">&nbsp;
                                    </td>
                                @endif
                                <td class="stack-col" width="49%" valign="top" style="width:49%;padding:0 0 12px;">
                                    <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                        <tr>
                                            <td width="24" valign="top" class="dm-hi"
                                                style="width:24px;font-family:{{ $sans }};font-size:14px;line-height:22px;color:#1D4ED8;font-weight:700;">
                                                &#10004;</td>
                                            <td valign="top" class="dm-p"
                                                style="font-family:{{ $sans }};font-size:13.5px;line-height:22px;color:#334155;">
                                                {{ $item }}</td>
                                        </tr>
                                    </table>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </table>
            </td>
        </tr>
    </table>

    {{-- ══════════════════ WHY CHOOSE START YOUR STORY? ══════════════════ --}}
    @include('emails.partials.section-title', ['label' => 'Why Choose Start Your Story?'])
    @include('emails.partials.card-grid', ['items' => $reasons, 'gap' => 46])

    {{-- ══════════════════ MORE THAN JUST TECHNOLOGY ═════════════════════ --}}
    @include('emails.partials.section-title', ['label' => 'More Than Just Technology', 'gap' => 18])

    <p class="dm-p"
        style="margin:0 0 26px;font-family:{{ $sans }};font-size:13.5px;line-height:22px;color:#475569;text-align:center;">
        Start Your Story is a growing platform built specifically for Chartered Accountants and CA Firms.
    </p>

    {{-- One wrapper table with real spacer ROWS between the cards: a margin on a
         <table> is unreliable in Outlook's Word engine, and if it were dropped these
         three tinted blocks would butt together into one two-tone slab. --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 26px;">
        @foreach ($platform as $p)
            <tr>
                <td class="dm-cta cta-pad"
                    style="background-color:{{ $p['card'] }};border-radius:{{ $radius }};padding:{{ $pad }};">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                        <tr>
                            <td width="64" valign="middle" style="width:64px;">
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="left">
                                    <tr>
                                        <td width="52" height="52" align="center" valign="middle"
                                            style="width:52px;height:52px;background-color:{{ $p['tile'] }};border-radius:26px;text-align:center;font-size:24px;line-height:52px;">
                                            {!! $p['icon'] !!}</td>
                                    </tr>
                                </table>
                            </td>
                            <td valign="middle">
                                <p
                                    style="margin:0 0 6px;font-family:{{ $sans }};font-size:15.5px;line-height:22px;font-weight:800;color:{{ $p['ink'] }};">
                                    {{ $p['title'] }}</p>
                                <p class="dm-p"
                                    style="margin:0;font-family:{{ $sans }};font-size:12.5px;line-height:20px;color:#475569;">
                                    {{ $p['desc'] }}</p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            @if (!$loop->last)
                <tr>
                    <td height="16" style="height:{{ $gapBlock }};font-size:0;line-height:0;">&nbsp;</td>
                </tr>
            @endif
        @endforeach
    </table>

    <p class="dm-h"
        style="margin:26px 0 46px;font-family:{{ $sans }};font-size:13.5px;line-height:21px;font-weight:700;color:#0F172A;text-align:center;">
        Our goal is to become your trusted hiring and technology partner.
    </p>

    {{-- ══════════════════ PRIMARY CTA ═══════════════════════════════════ --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
        style="background-color:#1D4ED8;border-radius:{{ $radius }};margin:0 0 {{ $gapBlock }};">
        <tr>
            <td class="cta-pad" style="padding:{{ $pad }};">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td class="stack-col stack-gap" width="54%" valign="middle"
                            style="width:54%;padding:0 18px 0 0;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td width="56" valign="middle" style="width:56px;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td width="46" height="46" align="center" valign="middle"
                                                    style="width:46px;height:46px;background-color:#FFFFFF;border-radius:23px;text-align:center;font-size:22px;line-height:46px;">
                                                    &#128197;</td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td valign="middle">
                                        <p
                                            style="margin:0 0 7px;font-family:{{ $sans }};font-size:16px;line-height:22px;font-weight:800;color:#FFFFFF;">
                                            Let&rsquo;s Discuss Your Requirements</p>
                                        <p class="txt-up"
                                            style="margin:0;font-family:{{ $sans }};font-size:12.5px;line-height:19px;color:#C7D2FE;">
                                            Whether it&rsquo;s a new website, custom software, system modernization or
                                            AI automation, we&rsquo;d be happy to understand your requirements.</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <td class="stack-col" width="46%" valign="middle" align="center" style="width:46%;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" class="btn-block"
                                align="center" style="margin:0 auto;">
                                <tr>
                                    <td align="center" style="background-color:#FFFFFF;border-radius:10px;">
                                        {{-- &nbsp; before the arrow keeps it welded to the last word, so the
                                             button never renders with an orphaned arrow on its own line. --}}
                                        <a href="{{ $ctaUrl }}" target="_blank"
                                            style="display:inline-block;padding:15px 22px;font-family:{{ $sans }};font-size:13.5px;font-weight:800;color:#1D4ED8;">Get
                                            a Free Consultation&nbsp;&rarr;</a>
                                    </td>
                                </tr>
                            </table>
                            <p class="txt-up"
                                style="margin:14px 0 0;font-family:{{ $sans }};font-size:11.5px;line-height:17px;color:#C7D2FE;text-align:center;">
                                or simply reply to this email with your project requirements.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ══════════════════ SECONDARY CTA ═════════════════════════════════  border:1px solid #A7F3D0 --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0;">
        <tr>
            {{-- Explicit spacer: the two CTA blocks are adjacent solid colours, so the
                 gap between them must not depend on a table margin Outlook may drop. --}}
            <td height="16" style="height:{{ $gapBlock }};font-size:0;line-height:0;">&nbsp;</td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" class="dm-cta"
        style="background-color:#ECFDF5;border-radius:{{ $radius }};margin:0 0 46px;">
        <tr>
            <td class="cta-pad" style="padding:{{ $pad }};">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td class="stack-col stack-gap" width="60%" valign="middle"
                            style="width:60%;padding:0 18px 0 0;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td width="56" valign="middle" style="width:56px;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td width="44" height="44" align="center" valign="middle"
                                                    style="width:44px;height:44px;background-color:#10B981;border-radius:22px;text-align:center;font-size:20px;line-height:44px;">
                                                    &#128100;</td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td valign="middle">
                                        <p class="dm-h"
                                            style="margin:0 0 6px;font-family:{{ $sans }};font-size:16px;line-height:22px;font-weight:800;color:#0F172A;">
                                            Looking to Hire CA Talent?</p>
                                        <p class="dm-p"
                                            style="margin:0;font-family:{{ $sans }};font-size:12.5px;line-height:19px;color:#475569;">
                                            Register your firm on Start Your Story and start connecting with
                                            Articleship, Semi-Qualified and Qualified CA candidates.</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <td class="stack-col" width="40%" valign="middle" align="center" style="width:40%;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" class="btn-block"
                                align="center" style="margin:0 auto;">
                                <tr>
                                    <td align="center" style="background-color:#059669;border-radius:10px;">
                                        <a href="{{ $registerUrl }}" target="_blank"
                                            style="display:inline-block;padding:15px 22px;font-family:{{ $sans }};font-size:13.5px;font-weight:800;color:#FFFFFF;">Register
                                            Your Firm&nbsp;&rarr;</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ══════════════════ SIGN-OFF ══════════════════════════════════════ --}}
    {{-- No email/website block here on purpose: the shared layout footer is the
         single place this email shows contact information. --}}
    <p class="dm-p"
        style="margin:0 0 12px;font-family:{{ $sans }};font-size:13px;line-height:21px;color:#475569;">
        Thank you for your time.<br>We look forward to partnering with your firm.
    </p>
    <p class="dm-p"
        style="margin:0 0 3px;font-family:{{ $sans }};font-size:13px;line-height:20px;color:#475569;">
        Warm Regards,</p>
    <p class="dm-h"
        style="margin:0;font-family:{{ $sans }};font-size:13.5px;line-height:20px;font-weight:800;color:#0F172A;">
        Team Start Your Story</p>
@endsection
