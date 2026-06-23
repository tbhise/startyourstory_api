@php
    // ---- Map the normalized $d contract to local view variables -------------
    $d = $d ?? [];
    $fullName = $d['name'] ?? '';
    $title = $d['title'] ?? '';
    $summary = $d['summary'] ?? '';
    $education = $d['education'] ?? [];
    $experience = $d['experience'] ?? [];
    $skills = $d['skills'] ?? [];
    $certs = $d['certifications'] ?? [];
    $achieve = $d['achievements'] ?? [];
    $showCerts = $d['showCertifications'] ?? true;
    $showAch = $d['showAchievements'] ?? true;

    // Header contact items — built then joined with separators (mPDF-safe).
    $contacts = array_filter(
        [
            $d['email'] ?? null,
            $d['mobile'] ?? null,
            $d['location'] ?? null,
            $d['linkedin'] ?? null,
            $d['website'] ?? null,
        ],
        fn($v) => !empty(trim((string) $v)),
    );
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>{{ $fullName !== '' ? $fullName : 'Resume' }}</title>
    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, "Calibri", sans-serif;
            color: #111827;
            font-size: 11px;
            line-height: 1.5;
            background: #ffffff;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 20mm 20mm;
            background: #ffffff;
        }

        /* ---------------- Header ---------------- */
        .name {
            font-size: 28px;
            font-weight: bold;
            color: #1e3a8a;
            letter-spacing: 0.3px;
            line-height: 1.1;
        }

        .title {
            font-size: 13px;
            color: #6b7280;
            font-weight: bold;
            margin-top: 3px;
            letter-spacing: 0.4px;
        }

        .contact {
            font-size: 10.5px;
            color: #6b7280;
            margin-top: 8px;
        }

        .contact .sep {
            color: #e5e7eb;
            padding: 0 5px;
        }

        .header-rule {
            border-bottom: 2px solid #1e3a8a;
            margin-top: 12px;
        }

        /* ---------------- Sections ---------------- */
        .section {
            margin-top: 16px;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #1e3a8a;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 3px;
            margin-bottom: 8px;
        }

        .summary-text {
            font-size: 11.5px;
            color: #111827;
            text-align: justify;
        }

        /* ---------------- Entry rows (table-based for mPDF) ---------------- */
        .entry {
            margin-bottom: 10px;
        }

        .entry-row {
            width: 100%;
            border-collapse: collapse;
        }

        .entry-row td {
            vertical-align: top;
            padding: 0;
        }

        .entry-left {
            text-align: left;
        }

        .entry-right {
            text-align: right;
            white-space: nowrap;
        }

        .entry-primary {
            font-size: 12px;
            font-weight: bold;
            color: #111827;
        }

        .entry-secondary {
            font-size: 11px;
            color: #6b7280;
            margin-top: 1px;
        }

        .entry-meta {
            font-size: 10.5px;
            color: #6b7280;
            font-weight: bold;
        }

        /* ---------------- Bullets ---------------- */
        ul.bullets {
            margin: 5px 0 0 0;
            padding-left: 16px;
        }

        ul.bullets li {
            font-size: 11px;
            color: #111827;
            margin-bottom: 2px;
            line-height: 1.45;
        }

        /* ---------------- Skills ---------------- */
        .skill-chip {
            display: inline-block;
            font-size: 10.5px;
            color: #1e3a8a;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            padding: 3px 9px;
            margin: 0 5px 5px 0;
            border-radius: 3px;
        }

        /* ---------------- Simple lists ---------------- */
        ul.plain {
            margin: 0;
            padding-left: 16px;
        }

        ul.plain li {
            font-size: 11px;
            color: #111827;
            margin-bottom: 3px;
        }
    </style>
</head>

<body>
    <div class="page">

        {{-- ================= HEADER ================= --}}
        <div class="header">
            <div class="name">{{ $fullName }}</div>
            @if (!empty($title))
                <div class="title">{{ $title }}</div>
            @endif

            @if (!empty($contacts))
                <div class="contact">
                    @foreach ($contacts as $i => $item)
                        @if ($i > 0)
                            <span class="sep">|</span>
                        @endif{{ $item }}
                    @endforeach
                </div>
            @endif

            <div class="header-rule"></div>
        </div>

        {{-- ================= PROFESSIONAL SUMMARY ================= --}}
        @if (!empty(trim((string) $summary)))
            <div class="section">
                <div class="section-title">Professional Summary</div>
                <div class="summary-text">{{ $summary }}</div>
            </div>
        @endif

        {{-- ================= EXPERIENCE ================= --}}
        @if (!empty($experience) && is_array($experience))
            <div class="section">
                <div class="section-title">Experience</div>
                @foreach ($experience as $exp)
                    <div class="entry">
                        <table class="entry-row">
                            <tr>
                                <td class="entry-left">
                                    <span class="entry-primary">{{ $exp['company'] ?? '' }}</span>
                                </td>
                                <td class="entry-right">
                                    @if (!empty(trim((string) ($exp['duration'] ?? ''))))
                                        <span class="entry-meta">{{ $exp['duration'] }}</span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                        @if (!empty($exp['role']))
                            <div class="entry-secondary">{{ $exp['role'] }}</div>
                        @endif

                        @php
                            // normalizeResume() pre-splits responsibilities into `lines`.
                            $points = $exp['lines'] ?? [];
                        @endphp
                        @if (!empty($points) && is_array($points))
                            <ul class="bullets">
                                @foreach ($points as $point)
                                    @if (!empty(trim((string) $point)))
                                        <li>{{ $point }}</li>
                                    @endif
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        {{-- ================= EDUCATION ================= --}}
        @if (!empty($education) && is_array($education))
            <div class="section">
                <div class="section-title">Education</div>
                @foreach ($education as $edu)
                    <div class="entry">
                        <table class="entry-row">
                            <tr>
                                <td class="entry-left">
                                    <span class="entry-primary">{{ $edu['degree'] ?? '' }}</span>
                                </td>
                                <td class="entry-right">
                                    @if (!empty(trim((string) ($edu['year'] ?? ''))))
                                        <span class="entry-meta">{{ $edu['year'] }}</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="entry-left">
                                    <span class="entry-secondary">{{ $edu['institute'] ?? '' }}</span>
                                </td>
                                <td class="entry-right">
                                    @if (!empty(trim((string) ($edu['score'] ?? ''))))
                                        <span class="entry-secondary">{{ $edu['score'] }}</span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- ================= SKILLS ================= --}}
        @if (!empty($skills) && is_array($skills))
            <div class="section">
                <div class="section-title">Skills</div>
                
                <div class="skills-wrap">
                    @foreach ($skills as $skill)
                        @if (!empty(trim((string) $skill)))
                            <span class="skill-chip">{{ $skill }}</span>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ================= CERTIFICATIONS ================= --}}
        @if ($showCerts && !empty($certs) && is_array($certs))
            <div class="section">
                <div class="section-title">Certifications</div>
                <ul class="plain">
                    @foreach ($certs as $cert)
                        @php $certName = is_array($cert) ? ($cert['name'] ?? ($cert['title'] ?? '')) : $cert; @endphp
                        @if (!empty(trim((string) $certName)))
                            <li>{{ $certName }}@if (is_array($cert) && !empty($cert['issuer']))
                                    — {{ $cert['issuer'] }}
                                @endif
                            </li>
                        @endif
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- ================= ACHIEVEMENTS ================= --}}
        @if ($showAch && !empty($achieve) && is_array($achieve))
            <div class="section">
                <div class="section-title">Achievements</div>
                <ul class="plain">
                    @foreach ($achieve as $item)
                        @php $achName = is_array($item) ? ($item['name'] ?? ($item['title'] ?? '')) : $item; @endphp
                        @if (!empty(trim((string) $achName)))
                            <li>{{ $achName }}</li>
                        @endif
                    @endforeach
                </ul>
            </div>
        @endif

    </div>
</body>

</html>
