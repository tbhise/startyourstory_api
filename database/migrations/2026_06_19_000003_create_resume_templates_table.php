<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Backend-managed resume templates (Part 4). Each row holds an admin-editable
 * Blade/HTML body + CSS for a template key. The Resume PDF (mPDF) renders from
 * the ACTIVE row for the selected key, falling back to the static `resume.pdf`
 * Blade view when the table is empty/missing (so nothing breaks).
 *
 * The seed rows below are the exact mPDF templates that previously lived inline
 * in `resources/views/resume/pdf.blade.php`, with two substitutions so the
 * admin-editable content needs no custom PHP helpers:
 *   - responsibilities bullets use the precomputed `$x['lines']`
 *   - the Executive photo uses the precomputed `$d['initials']`
 * (both produced by ResumeController::normalizeResume).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('resume_templates')) {
            Schema::create('resume_templates', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('template_name', 120);
                $table->string('template_key', 50)->unique();
                $table->longText('html_content')->nullable();
                $table->longText('css_content')->nullable();
                $table->string('preview_image')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Seed once — never clobber admin edits on re-run.
        if (DB::table('resume_templates')->count() > 0) {
            return;
        }

        $css = $this->sharedCss();
        $now = now();

        $rows = [
            ['classic_professional', 'Classic Professional', $this->classicHtml()],
            ['modern_minimal',       'Modern Minimal',       $this->modernHtml()],
            ['executive_sidebar',    'Executive Sidebar',    $this->executiveHtml()],
            ['creative_professional','Creative Professional',$this->creativeHtml()],
        ];

        foreach ($rows as [$key, $name, $html]) {
            DB::table('resume_templates')->insert([
                'template_name' => $name,
                'template_key'  => $key,
                'html_content'  => $html,
                'css_content'   => $css,
                'preview_image' => null,
                'is_active'     => true,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('resume_templates');
    }

    /** Shared mPDF CSS (identical to the inline <style> in resume/pdf.blade.php). */
    private function sharedCss(): string
    {
        return <<<'CSS'
  * { margin: 0; padding: 0; }
  body { font-family: dejavusans, sans-serif; color: #1e293b; font-size: 12px; line-height: 1.42; }
  td, p, h1, h2, h3, li, span, div { word-wrap: break-word; overflow-wrap: break-word; }
  ul { margin: 2px 0 0 14px; }
  li { margin-bottom: 1px; }
  table { border-collapse: collapse; width: 100%; }
  td { vertical-align: top; }
  .muted { color: #64748b; }
  .ital { font-style: italic; }

  /* Classic */
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

  /* Modern */
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

  /* Executive */
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

  /* Creative */
  .cre .head { padding: 22px 28px 12px; }
  .cre .name { font-size: 26px; font-weight: bold; color: #0f172a; }
  .cre .title { font-size: 13px; font-weight: bold; color: #0d9488; margin-top: 3px; }
  .cre .contact { font-size: 11px; color: #475569; text-align: right; }
  .cre .contact div { margin-bottom: 2px; }
  .cre .band { background-color: #0d9488; color: #ffffff; font-size: 11px; padding: 12px 28px; }
  .cre .body { padding: 16px 28px; }
  .cre .colL { float: left; width: 60%; padding-right: 4%; }
  .cre .colR { float: left; width: 36%; }
  .cre .h { font-size: 14px; font-weight: bold; color: #0d9488; margin: 12px 0 5px; }
  .cre .h .dot { color: #0d9488; }
  .cre .org { font-size: 12.5px; font-weight: bold; color: #0f172a; }
  .cre .sub { font-size: 11px; color: #334155; }
  .cre .meta { font-size: 10px; color: #64748b; font-style: italic; }
  .cre .pill { background-color: #0d9488; color: #ffffff; font-size: 9px; padding: 1px 6px; }
  .cre ul { font-size: 10.5px; color: #334155; }
CSS;
    }

    private function classicHtml(): string
    {
        return <<<'BLADE'
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
          @if(count($x['lines']))<ul>@foreach($x['lines'] as $l)<li>{{ $l }}</li>@endforeach</ul>@endif
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
BLADE;
    }

    private function modernHtml(): string
    {
        return <<<'BLADE'
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
          @if(count($x['lines']))<ul>@foreach($x['lines'] as $l)<li>{{ $l }}</li>@endforeach</ul>@endif
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
BLADE;
    }

    private function executiveHtml(): string
    {
        return <<<'BLADE'
<div class="exec">
  <div class="side">
    <div class="photo">{{ $d['initials'] }}</div>

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
            @if(count($x['lines']))<ul>@foreach($x['lines'] as $l)<li>{{ $l }}</li>@endforeach</ul>@endif
          </div>
        @endforeach
      @endif
    @endforeach
  </div>
  <div style="clear:both"></div>
</div>
BLADE;
    }

    private function creativeHtml(): string
    {
        return <<<'BLADE'
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
                @if(count($x['lines']))<ul>@foreach($x['lines'] as $l)<li>{{ $l }}</li>@endforeach</ul>@endif
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
BLADE;
    }
};
