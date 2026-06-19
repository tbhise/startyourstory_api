<?php
/**
 * Server-side resume PDF templates (mPDF replica of the React templates).
 * mPDF has no flexbox/grid, so two-column layouts use tables. Authored to match
 * the reference designs as closely as the engine allows: same section order,
 * typography hierarchy, colours, spacing and structure.
 *
 * $t = template key, $d = normalized resume data (see ResumeController::normalizeResume).
 */
if (!function_exists('rb_lines')) {
    function rb_lines($s) {
        $p = preg_split('/\r\n|\r|\n/', (string) $s);
        return array_values(array_filter(array_map('trim', $p), fn($x) => $x !== ''));
    }
}
if (!function_exists('rb_initials')) {
    function rb_initials($name) {
        $parts = preg_split('/\s+/', trim((string) $name));
        $i = '';
        foreach ($parts as $p) {
            if ($p !== '') $i .= mb_substr($p, 0, 1);
            if (mb_strlen($i) >= 2) break;
        }
        return mb_strtoupper($i !== '' ? $i : 'CV');
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  * { margin: 0; padding: 0; }
  body { font-family: dejavusans, sans-serif; color: #1e293b; font-size: 12px; line-height: 1.42; }
  /* long content must wrap, never overflow the page */
  td, p, h1, h2, h3, li, span, div { word-wrap: break-word; overflow-wrap: break-word; }
  ul { margin: 2px 0 0 14px; }
  li { margin-bottom: 1px; }
  table { border-collapse: collapse; width: 100%; }
  td { vertical-align: top; }
  .muted { color: #64748b; }
  .ital { font-style: italic; }

  /* ── Classic Professional ── */
  .classic { padding: 28px 34px; }
  .classic .name { font-size: 26px; font-weight: bold; color: #0f172a; }
  .classic .contact { font-size: 11px; color: #475569; text-align: right; }
  .classic .contact div { margin-bottom: 2px; }
  .classic .rule { border-bottom: 2px solid #0f172a; height: 1px; margin-top: 8px; }
  .classic .sec { font-size: 13px; font-weight: bold; color: #0f172a; text-transform: uppercase; letter-spacing: 0.6px; margin-top: 14px; }
  .classic .secrule { border-bottom: 1px solid #cbd5e1; height: 1px; margin: 3px 0 6px; }
  .classic .org { font-size: 12.5px; font-weight: bold; color: #0f172a; }
  .classic .sub { font-size: 11.5px; color: #334155; }
  .classic .meta { font-size: 10px; color: #64748b; }
  .classic .chip { border: 1px solid #cbd5e1; padding: 1px 6px; font-size: 10px; color: #334155; }
  .classic ul { font-size: 11px; color: #334155; }

  /* ── Modern Minimal ── */
  .modern { padding: 30px 40px; }
  .modern .name { font-size: 25px; font-weight: bold; color: #0f172a; text-align: center; }
  .modern .title { font-size: 13px; color: #64748b; text-align: center; margin-top: 2px; }
  .modern .contact { font-size: 11px; color: #475569; text-align: center; margin-top: 6px; }
  .modern .rule { border-bottom: 1px solid #cbd5e1; height: 1px; margin-top: 8px; }
  .modern .sec { font-size: 12.5px; font-weight: bold; color: #0f172a; text-transform: uppercase; letter-spacing: 1.2px; margin-top: 14px; }
  .modern .secrule { border-bottom: 1px solid #cbd5e1; height: 1px; margin: 3px 0 6px; }
  .modern .row { font-size: 12.5px; font-weight: bold; color: #0f172a; }
  .modern .meta { font-size: 11px; color: #64748b; }
  .modern .sub { font-size: 11px; color: #64748b; font-style: italic; }
  .modern ul { font-size: 11px; color: #334155; }
  .modern .skills li { font-size: 11.5px; }

  /* ── Executive Sidebar ── */
  /* Float layout (not a table) so the main column paginates across pages and
     long content is never clipped. The sidebar band paints its own height. */
  .exec { font-family: dejavuserif, serif; }
  .exec .side { float: left; width: 33%; background-color: #2b3a4f; color: #e2e8f0; padding: 18px 14px; }
  .exec .main { margin-left: 34%; padding: 18px 22px; }
  .exec .photo { width: 84px; height: 84px; background-color: #475569; color: #ffffff; font-size: 26px; font-weight: bold; text-align: center; padding-top: 26px; }
  .exec .sh { font-size: 14px; font-weight: bold; color: #ffffff; border-bottom: 1px solid #64748b; padding-bottom: 3px; margin: 16px 0 6px; }
  .exec .side p, .exec .side li { font-size: 11px; color: #e2e8f0; }
  .exec .side ul { margin-left: 14px; }
  .exec .name { font-size: 28px; font-weight: bold; color: #0f172a; }
  .exec .title { font-size: 13px; color: #64748b; margin-top: 2px; }
  .exec .mh { font-size: 16px; font-weight: bold; color: #334155; margin-top: 14px; }
  .exec .mhrule { border-bottom: 1px solid #cbd5e1; height: 1px; margin: 2px 0 6px; }
  .exec .org { font-size: 12.5px; font-weight: bold; color: #0f172a; }
  .exec .sub { font-size: 11.5px; color: #334155; }
  .exec .meta { font-size: 11px; color: #64748b; font-style: italic; }
  .exec .main ul { font-size: 11px; color: #334155; }

  /* ── Creative Professional ── */
  .cre .head { padding: 22px 28px 12px; }
  .cre .name { font-size: 26px; font-weight: bold; color: #0f172a; }
  .cre .title { font-size: 13px; font-weight: bold; color: #0d9488; margin-top: 3px; }
  .cre .contact { font-size: 11px; color: #475569; text-align: right; }
  .cre .contact div { margin-bottom: 2px; }
  .cre .band { background-color: #0d9488; color: #ffffff; font-size: 11px; padding: 12px 28px; }
  .cre .body { padding: 16px 28px; }
  /* float columns (not a table) so the wider left column paginates without clipping */
  .cre .colL { float: left; width: 60%; padding-right: 4%; }
  .cre .colR { float: left; width: 36%; }
  .cre .h { font-size: 14px; font-weight: bold; color: #0d9488; margin: 12px 0 5px; }
  .cre .h .dot { color: #0d9488; }
  .cre .org { font-size: 12.5px; font-weight: bold; color: #0f172a; }
  .cre .sub { font-size: 11px; color: #334155; }
  .cre .meta { font-size: 10px; color: #64748b; font-style: italic; }
  .cre .pill { background-color: #0d9488; color: #ffffff; font-size: 9px; padding: 1px 6px; }
  .cre ul { font-size: 10.5px; color: #334155; }
</style>
</head>
<body>

@switch($t)

{{-- ════════════════════════════ CLASSIC ════════════════════════════ --}}
@case('classic_professional')
  <div class="classic">
    <table>
      <tr>
        <td style="width:60%"><div class="name">{{ $d['name'] ?: 'Your Name' }}</div></td>
        <td style="width:40%">
          <div class="contact">
            @if($d['email'])<div>{{ $d['email'] }}</div>@endif
            @if($d['mobile'])<div>{{ $d['mobile'] }}</div>@endif
            @if($d['location'])<div>{{ $d['location'] }}</div>@endif
          </div>
        </td>
      </tr>
    </table>
    <div class="rule"></div>

    @if($d['summary'])
      <div class="sec">Summary</div><div class="secrule"></div>
      <p style="font-size:11.5px;color:#334155">{{ $d['summary'] }}</p>
    @endif

    @foreach($d['sectionOrder'] as $key)
      @if($key === 'education' && count($d['education']))
        <div class="sec">Education</div><div class="secrule"></div>
        @foreach($d['education'] as $e)
          <table style="margin-bottom:5px"><tr>
            <td style="width:75%">
              <div class="org">{{ $e['institute'] }}</div>
              <div class="sub">{{ $e['degree'] }}</div>
              <div class="meta">{{ $e['year'] }}</div>
            </td>
            <td style="width:25%;text-align:right">@if($e['score'])<span class="meta ital">{{ $e['score'] }}</span>@endif</td>
          </tr></table>
        @endforeach
      @endif

      @if($key === 'experience' && count($d['experience']))
        <div class="sec">Experience</div><div class="secrule"></div>
        @foreach($d['experience'] as $x)
          <div style="margin-bottom:7px">
            <div class="org">{{ $x['company'] }}</div>
            <div class="sub">{{ $x['role'] }}</div>
            <div class="meta">{{ $x['duration'] }}</div>
            @php $lines = rb_lines($x['responsibilities']); @endphp
            @if(count($lines))<ul>@foreach($lines as $l)<li>{{ $l }}</li>@endforeach</ul>@endif
          </div>
        @endforeach
      @endif

      @if($key === 'skills' && count($d['skills']))
        <div class="sec">Skills</div><div class="secrule"></div>
        <div>@foreach($d['skills'] as $s)<span class="chip">{{ $s }}</span> @endforeach</div>
      @endif
    @endforeach

    @if($d['showCertifications'] && count($d['certifications']))
      <div class="sec">Certifications</div><div class="secrule"></div>
      <ul>@foreach($d['certifications'] as $c)<li>{{ $c }}</li>@endforeach</ul>
    @endif
    @if($d['showAchievements'] && count($d['achievements']))
      <div class="sec">Achievements</div><div class="secrule"></div>
      <ul>@foreach($d['achievements'] as $a)<li>{{ $a }}</li>@endforeach</ul>
    @endif
  </div>
@break

{{-- ════════════════════════════ MODERN ════════════════════════════ --}}
@case('modern_minimal')
  <div class="modern">
    <div class="name">{{ $d['name'] ?: 'Your Name' }}</div>
    @if($d['title'])<div class="title">{{ $d['title'] }}</div>@endif
    <div class="contact">
      {{ collect([$d['mobile'],$d['email'],$d['location'],$d['linkedin']])->filter()->implode('   •   ') }}
    </div>
    <div class="rule"></div>

    @if($d['summary'])
      <div class="sec">Summary</div><div class="secrule"></div>
      <p style="font-size:11.5px;color:#334155">{{ $d['summary'] }}</p>
    @endif

    @foreach($d['sectionOrder'] as $key)
      @if($key === 'skills' && count($d['skills']))
        <div class="sec">Skills</div><div class="secrule"></div>
        @php $half = (int) ceil(count($d['skills']) / 2); $c1 = array_slice($d['skills'],0,$half); $c2 = array_slice($d['skills'],$half); @endphp
        <table class="skills"><tr>
          <td style="width:50%"><ul>@foreach($c1 as $s)<li>{{ $s }}</li>@endforeach</ul></td>
          <td style="width:50%"><ul>@foreach($c2 as $s)<li>{{ $s }}</li>@endforeach</ul></td>
        </tr></table>
      @endif

      @if($key === 'experience' && count($d['experience']))
        <div class="sec">Experience</div><div class="secrule"></div>
        @foreach($d['experience'] as $x)
          <div style="margin-bottom:7px">
            <table><tr>
              <td style="width:70%"><span class="row">{{ $x['role'] }}</span></td>
              <td style="width:30%;text-align:right"><span class="meta">{{ $x['duration'] }}</span></td>
            </tr></table>
            <div class="sub">{{ $x['company'] }}</div>
            @php $lines = rb_lines($x['responsibilities']); @endphp
            @if(count($lines))<ul>@foreach($lines as $l)<li>{{ $l }}</li>@endforeach</ul>@endif
          </div>
        @endforeach
      @endif

      @if($key === 'education' && count($d['education']))
        <div class="sec">Education</div><div class="secrule"></div>
        @foreach($d['education'] as $e)
          <table style="margin-bottom:4px"><tr>
            <td style="width:70%"><span class="row">{{ $e['degree'] }}</span><div class="sub">{{ $e['institute'] }}</div></td>
            <td style="width:30%;text-align:right"><span class="meta">{{ trim($e['year'].' '.$e['score']) }}</span></td>
          </tr></table>
        @endforeach
      @endif
    @endforeach

    @if($d['showCertifications'] && count($d['certifications']))
      <div class="sec">Certifications</div><div class="secrule"></div>
      <ul>@foreach($d['certifications'] as $c)<li>{{ $c }}</li>@endforeach</ul>
    @endif
    @if($d['showAchievements'] && count($d['achievements']))
      <div class="sec">Achievements</div><div class="secrule"></div>
      <ul>@foreach($d['achievements'] as $a)<li>{{ $a }}</li>@endforeach</ul>
    @endif
  </div>
@break

{{-- ════════════════════════════ EXECUTIVE ════════════════════════════ --}}
@case('executive_sidebar')
  <div class="exec">
    <div class="side">
      {{-- Photo is intrinsic to this template — always shown (no manual toggle). --}}
      <div class="photo">{{ rb_initials($d['name']) }}</div>

      <div class="sh">Contact</div>
      @if($d['email'])<p>{{ $d['email'] }}</p>@endif
      @if($d['mobile'])<p>{{ $d['mobile'] }}</p>@endif
      @if($d['website'])<p>{{ $d['website'] }}</p>@endif
      @if($d['location'])<p>{{ $d['location'] }}</p>@endif

      @if(count($d['skills']))
        <div class="sh">Skills</div>
        <ul>@foreach($d['skills'] as $s)<li>{{ $s }}</li>@endforeach</ul>
      @endif
      @if($d['showCertifications'] && count($d['certifications']))
        <div class="sh">Certifications</div>
        <ul>@foreach($d['certifications'] as $c)<li>{{ $c }}</li>@endforeach</ul>
      @endif
      @if($d['showAchievements'] && count($d['achievements']))
        <div class="sh">Achievements</div>
        <ul>@foreach($d['achievements'] as $a)<li>{{ $a }}</li>@endforeach</ul>
      @endif
    </div>
    <div class="main">
      <div class="name">{{ $d['name'] ?: 'Your Name' }}</div>
      @if($d['title'])<div class="title">{{ $d['title'] }}</div>@endif

      @if($d['summary'])
        <div class="mh">Summary</div><div class="mhrule"></div>
        <p style="font-size:11.5px;color:#334155">{{ $d['summary'] }}</p>
      @endif

      @foreach($d['sectionOrder'] as $key)
        @if($key === 'education' && count($d['education']))
          <div class="mh">Education</div><div class="mhrule"></div>
          @foreach($d['education'] as $e)
            {{-- stacked (no nested table) so the outer two-column row can paginate --}}
            <div style="margin-bottom:5px">
              <div class="org">{{ $e['degree'] }}</div>
              <div class="sub">{{ $e['institute'] }}</div>
              <div class="meta">{{ trim($e['year'].($e['score'] ? '  •  '.$e['score'] : '')) }}</div>
            </div>
          @endforeach
        @endif

        @if($key === 'experience' && count($d['experience']))
          <div class="mh">Experience</div><div class="mhrule"></div>
          @foreach($d['experience'] as $x)
            <div style="margin-bottom:7px">
              <div class="org">{{ $x['role'] }}</div>
              <div class="sub">{{ trim($x['company'].($x['duration'] ? '  •  '.$x['duration'] : '')) }}</div>
              @php $lines = rb_lines($x['responsibilities']); @endphp
              @if(count($lines))<ul>@foreach($lines as $l)<li>{{ $l }}</li>@endforeach</ul>@endif
            </div>
          @endforeach
        @endif
      @endforeach
    </div>
    <div style="clear:both"></div>
  </div>
@break

{{-- ════════════════════════════ CREATIVE ════════════════════════════ --}}
@case('creative_professional')
  <div class="cre">
    <div class="head">
      <table><tr>
        <td style="width:60%">
          <div class="name">{{ $d['name'] ?: 'Your Name' }}</div>
          @if($d['title'])<div class="title">{{ $d['title'] }}</div>@endif
        </td>
        <td style="width:40%">
          <div class="contact">
            @if($d['mobile'])<div>{{ $d['mobile'] }}</div>@endif
            @if($d['email'])<div>{{ $d['email'] }}</div>@endif
            @if($d['linkedin'])<div>{{ $d['linkedin'] }}</div>@endif
            @if($d['location'])<div>{{ $d['location'] }}</div>@endif
          </div>
        </td>
      </tr></table>
    </div>

    @if($d['summary'])<div class="band">{{ $d['summary'] }}</div>@endif

    <div class="body">
      <div class="colL">
          @foreach($d['sectionOrder'] as $key)
            @if($key === 'experience' && count($d['experience']))
              <div class="h"><span class="dot">●</span> Experience</div>
              @foreach($d['experience'] as $x)
                <div style="margin-bottom:8px">
                  <table><tr>
                    <td style="width:72%"><span class="org">{{ $x['company'] }}</span><div class="sub">{{ $x['role'] }}</div></td>
                    <td style="width:28%;text-align:right">@if($x['duration'])<span class="pill">{{ $x['duration'] }}</span>@endif</td>
                  </tr></table>
                  @php $lines = rb_lines($x['responsibilities']); @endphp
                  @if(count($lines))<ul>@foreach($lines as $l)<li>{{ $l }}</li>@endforeach</ul>@endif
                </div>
              @endforeach
            @endif

            @if($key === 'education' && count($d['education']))
              <div class="h"><span class="dot">●</span> Education</div>
              @foreach($d['education'] as $e)
                <div style="margin-bottom:5px">
                  <div class="org" style="font-size:12px">{{ $e['institute'] }}</div>
                  <div class="sub" style="font-size:10.5px">{{ trim($e['degree'].($e['score'] ? ', '.$e['score'] : '')) }}</div>
                  <div class="meta">{{ $e['year'] }}</div>
                </div>
              @endforeach
            @endif
          @endforeach

          @if($d['showAchievements'] && count($d['achievements']))
            <div class="h"><span class="dot">●</span> Achievements</div>
            <ul>@foreach($d['achievements'] as $a)<li>{{ $a }}</li>@endforeach</ul>
          @endif
        </div>
        <div class="colR">
          @if(count($d['skills']))
            <div class="h"><span class="dot">●</span> Skills</div>
            <ul>@foreach($d['skills'] as $s)<li>{{ $s }}</li>@endforeach</ul>
          @endif
          @if($d['showCertifications'] && count($d['certifications']))
            <div class="h"><span class="dot">●</span> Certifications</div>
            <ul>@foreach($d['certifications'] as $c)<li>{{ $c }}</li>@endforeach</ul>
          @endif
        </div>
        <div style="clear:both"></div>
    </div>
  </div>
@break

@endswitch

</body>
</html>
