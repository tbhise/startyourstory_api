<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * P0 security + completeness patches for the seeded resume_templates rows.
 *
 * 1. modern_minimal  — replace the @php skills-split block with pre-computed
 *    $d['skills_c1'] / $d['skills_c2'] from normalizeResume(). The @php
 *    directive was used as the injection-fix strip target; removing it from the
 *    canonical seed ensures the DB row stays clean after the strip.
 *
 * 2. classic_professional — add title, linkedin, and website to the contact
 *    header. These fields exist in every student's resume_data but were never
 *    rendered in this template (data loss in PDF output).
 *
 * Idempotent: skips templates that were already manually edited by an admin
 * (detected by checking the updated_at timestamp; if it differs from
 * created_at the admin has touched it and we leave it alone).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('resume_templates')) {
            return;
        }

        $this->patchModernMinimal();
        $this->patchClassicProfessional();
    }

    public function down(): void
    {
        // Intentionally a no-op: reverting template content on rollback would
        // overwrite any admin edits made after the migration ran.
    }

    // ── Modern Minimal ────────────────────────────────────────────────────────

    private function patchModernMinimal(): void
    {
        $row = DB::table('resume_templates')
            ->where('template_key', 'modern_minimal')
            ->first();

        if (!$row || $row->updated_at !== $row->created_at) {
            return; // missing or admin-edited — leave untouched
        }

        $newHtml = <<<'BLADE'
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
      <table class="skills"><tr>
        <td style="width:50%"><ul>@foreach($d['skills_c1'] as $s)<li>{{ $s }}</li>@endforeach</ul></td>
        <td style="width:50%"><ul>@foreach($d['skills_c2'] as $s)<li>{{ $s }}</li>@endforeach</ul></td>
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

        DB::table('resume_templates')
            ->where('template_key', 'modern_minimal')
            ->update(['html_content' => $newHtml, 'updated_at' => now()]);
    }

    // ── Classic Professional ──────────────────────────────────────────────────

    private function patchClassicProfessional(): void
    {
        $row = DB::table('resume_templates')
            ->where('template_key', 'classic_professional')
            ->first();

        if (!$row || $row->updated_at !== $row->created_at) {
            return; // missing or admin-edited — leave untouched
        }

        $newHtml = <<<'BLADE'
<div class="classic">
  <table>
    <tr>
      <td style="width:60%">
        <div class="name">{{ $d['name'] ?: 'Your Name' }}</div>
        @if($d['title'])<div class="sub" style="margin-top:2px">{{ $d['title'] }}</div>@endif
      </td>
      <td style="width:40%">
        <div class="contact">
          @if($d['email'])<div>{{ $d['email'] }}</div>@endif
          @if($d['mobile'])<div>{{ $d['mobile'] }}</div>@endif
          @if($d['location'])<div>{{ $d['location'] }}</div>@endif
          @if($d['linkedin'])<div>{{ $d['linkedin'] }}</div>@endif
          @if($d['website'])<div>{{ $d['website'] }}</div>@endif
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

        DB::table('resume_templates')
            ->where('template_key', 'classic_professional')
            ->update(['html_content' => $newHtml, 'updated_at' => now()]);
    }
};
