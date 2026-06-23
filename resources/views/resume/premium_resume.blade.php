{{--
|--------------------------------------------------------------------------
| Premium Resume Template (icon-accented, corporate)
|--------------------------------------------------------------------------
| Path: resources/views/resume/premium_resume.blade.php
| Project: StartYourStory (CA Job Portal) - Resume Builder
|
| Blade conversion of the resume(1).html prototype, re-bound to the project's
| normalized $d contract (same as resume/pdf.blade.php and premium_minimal).
| $t = template key (unused). File-based template: ResumeController maps the
| `premium_resume` key straight to this view.
|
| mPDF adaptations from the browser prototype (so PDF export works):
|   - flexbox / CSS grid  -> table-based two-column rows (mPDF has no flex/grid)
|   - CSS custom properties (var(--x)) -> literal hex (mPDF does not resolve var())
|   - removed the local @page rule (a local @page + bordered inline pills trips an
|     mPDF bug; mPDF gets A4 + margins from the controller, preview injects @page)
|   - icons are decorative fill-based inline SVG (no text); all resume content is
|     plain selectable text, so the template stays ATS-friendly.
--}}
@php
    $d          = $d ?? [];
    $fullName   = $d['name']     ?? '';
    $roleTitle  = $d['title']    ?? '';
    $summary    = $d['summary']  ?? '';
    $education  = $d['education']      ?? [];
    $experience = $d['experience']     ?? [];
    $skills     = $d['skills']         ?? [];
    $certs      = $d['certifications'] ?? [];
    $achieve    = $d['achievements']   ?? [];
    $showCerts  = $d['showCertifications'] ?? true;
    $showAch    = $d['showAchievements']   ?? true;

    // Contact row. LinkedIn/Website show short labels (not the raw URL) so a long
    // link can never overflow or wrap the header.
    $contactItems = [];
    if (!empty(trim((string) ($d['mobile']   ?? '')))) $contactItems[] = ['phone',    $d['mobile']];
    if (!empty(trim((string) ($d['email']    ?? '')))) $contactItems[] = ['mail',     $d['email']];
    if (!empty(trim((string) ($d['location'] ?? '')))) $contactItems[] = ['pin',      $d['location']];
    if (!empty(trim((string) ($d['linkedin'] ?? '')))) $contactItems[] = ['linkedin', 'LinkedIn'];
    if (!empty(trim((string) ($d['website']  ?? '')))) $contactItems[] = ['globe',    'Website'];

    if (!function_exists('pr_icon')) {
        // Decorative fill-based inline SVG icons (mPDF-friendly; no text content).
        function pr_icon($name, $size = 13, $fill = '#1e3a8a') {
            $paths = [
                'phone'    => '<path d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1A17 17 0 0 1 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.2.2 2.4.6 3.6.1.4 0 .7-.2 1l-2.3 2.2z"/>',
                'mail'     => '<path d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm0 4-8 5-8-5V6l8 5 8-5v2z"/>',
                'pin'      => '<path d="M12 2a7 7 0 0 0-7 7c0 5 7 13 7 13s7-8 7-13a7 7 0 0 0-7-7zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5z"/>',
                'linkedin' => '<path d="M19 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2zM8.3 18H5.7V10h2.6v8zM7 8.8a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zM18.3 18h-2.6v-4c0-1-.4-1.7-1.3-1.7-.8 0-1.2.5-1.4 1-.1.2-.1.5-.1.8V18H10.3s.03-7.3 0-8h2.6v1.1c.3-.5 1-1.3 2.5-1.3 1.8 0 3 1.2 3 3.6V18z"/>',
                'globe'    => '<path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm0 2c1.4 0 2.9 2.9 3 7H9c.1-4.1 1.6-7 3-7zM5.1 11H7c.05-1.9.36-3.6.85-5A8 8 0 0 0 5.1 11zm0 2a8 8 0 0 0 2.75 5c-.49-1.4-.8-3.1-.85-5H5.1zM9 13h6c-.1 4.1-1.6 7-3 7s-2.9-2.9-3-7zm7.15 5A8 8 0 0 0 18.9 13H17c-.05 1.9-.36 3.6-.85 5zM17 11h1.9a8 8 0 0 0-2.75-5c.49 1.4.8 3.1.85 5z"/>',
                'user'     => '<path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5zm0 2c-3.3 0-10 1.7-10 5v3h20v-3c0-3.3-6.7-5-10-5z"/>',
                'brief'    => '<path d="M10 4h4a2 2 0 0 1 2 2v1h4a1 1 0 0 1 1 1v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a1 1 0 0 1 1-1h4V6a2 2 0 0 1 2-2zm0 3h4V6h-4v1z"/>',
                'cap'      => '<path d="M12 3 1 9l11 6 9-4.9V17h2V9zM5 13.2V17c0 1.7 3.6 3 7 3s7-1.3 7-3v-3.8l-7 3.8z"/>',
                'gear'     => '<path d="M19.4 13a7.5 7.5 0 0 0 0-2l2-1.6-2-3.4-2.4 1a7.6 7.6 0 0 0-1.7-1L15 3h-4l-.3 2.6a7.6 7.6 0 0 0-1.7 1l-2.4-1-2 3.4L6.6 11a7.5 7.5 0 0 0 0 2l-2 1.6 2 3.4 2.4-1a7.6 7.6 0 0 0 1.7 1l.3 2.6h4l.3-2.6a7.6 7.6 0 0 0 1.7-1l2.4 1 2-3.4-2-1.6zM12 15.5a3.5 3.5 0 1 1 0-7 3.5 3.5 0 0 1 0 7z"/>',
                'badge'    => '<path d="M12 2a6 6 0 1 0 0 12 6 6 0 0 0 0-12zm-3 13.5L8 22l4-2 4 2-1-6.5a8 8 0 0 1-6 0z"/>',
                'trophy'   => '<path d="M18 4V2H6v2H2v4a4 4 0 0 0 4 4 6 6 0 0 0 5 4.9V19H8v2h8v-2h-3v-2.1A6 6 0 0 0 18 12a4 4 0 0 0 4-4V4h-4zM4 8V6h2v4a2 2 0 0 1-2-2zm16 0a2 2 0 0 1-2 2V6h2v2z"/>',
            ];
            if (!isset($paths[$name])) return '';
            // Use the `fill` presentation ATTRIBUTE (not inline style): mPDF's SVG
            // parser honours it reliably, so white icons stay visible on the navy badge.
            return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="' . $fill . '">' . $paths[$name] . '</svg>';
        }
    }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $fullName !== '' ? $fullName : 'Resume' }}</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    html, body {
        background: #e9eaed;
        font-family: Arial, Helvetica, "Inter", sans-serif;
        color: #2b2b2b;
    }
    /* Long content (URLs, company names, skills) must wrap, never overflow. */
    td, p, span, div, li, h1 { word-wrap: break-word; overflow-wrap: break-word; }
    .page {
        width: 210mm;
        min-height: 297mm;
        background: #ffffff;
        margin: 16px auto;
        padding: 18mm 18mm 18mm 18mm;
        position: relative;
    }

    /* PDF / print only. The gray body canvas, the 16px outer margin and the full
       297mm min-height are on-screen "floating page" affordances; rendered to a
       real paginated PDF they print the gray canvas and spill a near-empty extra
       page. Neutralize them for print so the PDF is a clean single page when the
       content is short. The Backend Preview is screen media and is unaffected —
       it still looks exactly as before. Design/colors/spacing of the resume
       content itself are unchanged. */
    @media print {
        html, body { background: #ffffff; }
        .page { margin: 0; min-height: 0; }
    }

    /* ---------------- Header ---------------- */
    .name {
        text-align: center;
        font-size: 36px;
        font-weight: bold;
        letter-spacing: 1px;
        color: #1e3a8a;
        line-height: 1.05;
    }
    .role {
        text-align: center;
        color: #1e3a8a;
        font-size: 15px;
        margin-top: 4px;
    }
    .contact {
        text-align: center;
        margin-top: 13px;
        font-size: 12px;
        color: #333333;
    }
    .contact .item { white-space: nowrap; }
    .contact .item svg { vertical-align: -2px; margin-right: 4px; }
    .contact .sep { color: #c7c9cd; padding: 0 9px; }
    /* Solid border-based rules render cleanly in mPDF (dashed broke into "---"). */
    .header-divider { border-bottom: 2px solid #1e3a8a; margin-top: 12px; }

    /* ---------------- Section shell ---------------- */
    .section { margin-top: 18px; }
    .sec-head { width: 100%; border-collapse: collapse; margin-bottom: 10px; page-break-after: avoid; }
    .sec-head td { vertical-align: middle; padding: 0; }
    .sec-ic { width: 40px; vertical-align: middle; }
    /* Navy circular badge drawn as a single-cell table: mPDF paints cell
       background-color + border-radius reliably, so the white icon stays visible
       (an inline-block span background is not reliably painted by mPDF). */
    .ic-badge { width: 28px; border-collapse: collapse; }
    .ic-badge td {
        width: 28px;
        height: 28px;
        background-color: #1e3a8a;
        border-radius: 14px;
        text-align: center;
        vertical-align: middle;
        padding: 0;
    }
    .ic-badge svg { vertical-align: middle; }
    .section-title {
        color: #1e3a8a;
        font-size: 15px;
        font-weight: bold;
        letter-spacing: 1.2px;
        text-transform: uppercase;
    }
    .section-body { padding-left: 40px; }
    .section-divider { border-bottom: 1px solid #e5e7eb; margin-top: 14px; }

    p.summary { font-size: 12.5px; line-height: 1.6; color: #2b2b2b; text-align: justify; }

    /* ---------------- Experience ---------------- */
    .exp-item { margin-bottom: 12px; page-break-inside: avoid; }
    .exp-item .dashsep { border-bottom: 1px solid #e5e7eb; margin: 12px 0; }
    .hdr { width: 100%; border-collapse: collapse; }
    .hdr td { padding: 0; vertical-align: top; }
    .hdr .l { text-align: left; width: 68%; }
    .hdr .r { text-align: right; white-space: nowrap; width: 32%; padding-left: 8px; }
    .exp-company { font-weight: bold; font-size: 13.5px; color: #111111; }
    .exp-date { font-size: 12px; color: #333333; }
    .exp-role { color: #1e3a8a; font-size: 12.5px; margin-top: 2px; }
    ul.bullets { margin: 8px 0 0 0; padding-left: 18px; }
    ul.bullets li { font-size: 12.5px; line-height: 1.5; color: #2b2b2b; margin-bottom: 4px; }

    /* ---------------- Education ---------------- */
    .edu-item { margin-bottom: 12px; page-break-inside: avoid; }
    .edu-row { width: 100%; border-collapse: collapse; }
    .edu-row td { padding: 0; vertical-align: baseline; }
    .edu-deg { font-weight: bold; font-size: 13px; color: #111111; }
    .edu-date { text-align: right; font-size: 12px; color: #333333; white-space: nowrap; width: 30%; padding-left: 8px; }
    .edu-inst { font-size: 12.5px; color: #2b2b2b; }
    .edu-score { text-align: right; font-size: 12.5px; color: #1e3a8a; font-weight: bold; white-space: nowrap; width: 30%; padding-left: 8px; }

    /* ---------------- Skills (bordered pills; no @page so mPDF-safe) ----------------
       Consistent chip height via fixed line-height; container line-height gives even
       vertical rhythm between wrapped rows. Long skills wrap rather than overflow. */
    .skills-wrap { line-height: 2.2; }
    .chip {
        display: inline-block;
        border: 1px solid #b9c2e0;
        color: #1e3a8a;
        border-radius: 999px;
        padding: 4px 13px;
        margin: 0 7px 2px 0;
        font-size: 11.5px;
        line-height: 1.5;
        background: #ffffff;
    }

    /* ---------------- Two columns (Certifications | Achievements) ---------------- */
    .two-col { width: 100%; border-collapse: collapse; margin-top: 18px; }
    .two-col td { vertical-align: top; padding: 0; }
    .two-col .col { width: 47%; page-break-inside: avoid; }
    .two-col .gap { width: 6%; }
    .two-col .section { margin-top: 0; }

    /* ---------------- Footer accent ---------------- */
    .footer-bar { height: 3px; background: #1e3a8a; margin-top: 22px; font-size: 0; line-height: 0; }
</style>
</head>
<body>
<div class="page">

    {{-- ================= HEADER ================= --}}
    <div class="name">{{ $fullName }}</div>
    @if(!empty(trim((string) $roleTitle)))
        <div class="role">{{ $roleTitle }}</div>
    @endif
    @if(!empty($contactItems))
        <div class="contact">@foreach($contactItems as $idx => $ci)@if($idx > 0)<span class="sep">|</span>@endif<span class="item">{!! pr_icon($ci[0]) !!}{{ $ci[1] }}</span> @endforeach</div>
    @endif
    <div class="header-divider"></div>

    {{-- ================= SUMMARY ================= --}}
    @if(!empty(trim((string) $summary)))
        <div class="section">
            <table class="sec-head"><tr>
                <td class="sec-ic"><table class="ic-badge"><tr><td>{!! pr_icon('user', 14, '#ffffff') !!}</td></tr></table></td>
                <td><span class="section-title">Summary</span></td>
            </tr></table>
            <div class="section-body">
                <p class="summary">{{ $summary }}</p>
            </div>
            <div class="section-divider"></div>
        </div>
    @endif

    {{-- ================= EXPERIENCE ================= --}}
    @if(!empty($experience) && is_array($experience))
        <div class="section">
            <table class="sec-head"><tr>
                <td class="sec-ic"><table class="ic-badge"><tr><td>{!! pr_icon('brief', 14, '#ffffff') !!}</td></tr></table></td>
                <td><span class="section-title">Experience</span></td>
            </tr></table>
            <div class="section-body">
                @foreach($experience as $exp)
                    <div class="exp-item">
                        <table class="hdr"><tr>
                            <td class="l"><span class="exp-company">{{ $exp['company'] ?? '' }}</span></td>
                            <td class="r">@if(!empty(trim((string) ($exp['duration'] ?? ''))))<span class="exp-date">{{ $exp['duration'] }}</span>@endif</td>
                        </tr></table>
                        @if(!empty($exp['role']))<div class="exp-role">{{ $exp['role'] }}</div>@endif
                        @php $lines = $exp['lines'] ?? []; @endphp
                        @if(!empty($lines) && is_array($lines))
                            <ul class="bullets">
                                @foreach($lines as $line)@if(!empty(trim((string) $line)))<li>{{ $line }}</li>@endif @endforeach
                            </ul>
                        @endif
                    </div>
                    @if(!$loop->last)<div class="dashsep"></div>@endif
                @endforeach
            </div>
            <div class="section-divider"></div>
        </div>
    @endif

    {{-- ================= EDUCATION ================= --}}
    @if(!empty($education) && is_array($education))
        <div class="section">
            <table class="sec-head"><tr>
                <td class="sec-ic"><table class="ic-badge"><tr><td>{!! pr_icon('cap', 15, '#ffffff') !!}</td></tr></table></td>
                <td><span class="section-title">Education</span></td>
            </tr></table>
            <div class="section-body">
                @foreach($education as $edu)
                    <div class="edu-item">
                        <table class="edu-row"><tr>
                            <td><span class="edu-deg">{{ $edu['degree'] ?? '' }}</span></td>
                            <td class="edu-date">{{ $edu['year'] ?? '' }}</td>
                        </tr></table>
                        <table class="edu-row"><tr>
                            <td><span class="edu-inst">{{ $edu['institute'] ?? '' }}</span></td>
                            <td class="edu-score">{{ $edu['score'] ?? '' }}</td>
                        </tr></table>
                    </div>
                @endforeach
            </div>
            <div class="section-divider"></div>
        </div>
    @endif

    {{-- ================= SKILLS ================= --}}
    @if(!empty($skills) && is_array($skills))
        <div class="section">
            <table class="sec-head"><tr>
                <td class="sec-ic"><table class="ic-badge"><tr><td>{!! pr_icon('gear', 14, '#ffffff') !!}</td></tr></table></td>
                <td><span class="section-title">Skills</span></td>
            </tr></table>
            <div class="section-body skills-wrap">@foreach($skills as $skill)@if(!empty(trim((string) $skill)))<span class="chip">{{ $skill }}</span> @endif @endforeach</div>
        </div>
    @endif

    {{-- ============ CERTIFICATIONS | ACHIEVEMENTS ============ --}}
    @php
        $showCertBlock = $showCerts && !empty($certs) && is_array($certs);
        $showAchBlock  = $showAch && !empty($achieve) && is_array($achieve);
    @endphp
    @if($showCertBlock || $showAchBlock)
        <table class="two-col"><tr>
            <td class="col">
                @if($showCertBlock)
                    <div class="section">
                        <table class="sec-head"><tr>
                            <td class="sec-ic"><table class="ic-badge"><tr><td>{!! pr_icon('badge', 14, '#ffffff') !!}</td></tr></table></td>
                            <td><span class="section-title">Certifications</span></td>
                        </tr></table>
                        <div class="section-body">
                            <ul class="bullets">
                                @foreach($certs as $cert)@php $certName = is_array($cert) ? ($cert['name'] ?? ($cert['title'] ?? '')) : $cert; @endphp @if(!empty(trim((string) $certName)))<li>{{ $certName }}</li>@endif @endforeach
                            </ul>
                        </div>
                    </div>
                @endif
            </td>
            <td class="gap"></td>
            <td class="col">
                @if($showAchBlock)
                    <div class="section">
                        <table class="sec-head"><tr>
                            <td class="sec-ic"><table class="ic-badge"><tr><td>{!! pr_icon('trophy', 14, '#ffffff') !!}</td></tr></table></td>
                            <td><span class="section-title">Achievements</span></td>
                        </tr></table>
                        <div class="section-body">
                            <ul class="bullets">
                                @foreach($achieve as $item)@php $achName = is_array($item) ? ($item['name'] ?? ($item['title'] ?? '')) : $item; @endphp @if(!empty(trim((string) $achName)))<li>{{ $achName }}</li>@endif @endforeach
                            </ul>
                        </div>
                    </div>
                @endif
            </td>
        </tr></table>
    @endif

    <div class="footer-bar"></div>
</div>
</body>
</html>
