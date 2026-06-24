<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Modern Minimal — Education section layout FIX (Modern Minimal ONLY).
 *
 * The PDF for modern_minimal is rendered from the `resume_templates` DB row
 * (ResumeController::renderTemplateHtml), NOT from resume/pdf.blade.php — so the
 * blade edit never reached the downloaded PDF.
 *
 * Root cause of the broken PDF: the DB row's education block rendered a single
 * right cell `trim($e['year'].' '.$e['score'])`, i.e. Duration + Score MERGED into
 * one cell ("May - 2019 97"). The fix is a stable full-width two-row table putting
 * Duration and Score on separate rows/cells, with right cells right-aligned, padded
 * and nowrap so they can never merge; the score is highlighted (bold + #0f172a).
 *
 * Implementation: the whole `.modern` body is SET to a known-good template string
 * (deterministic — no fragile cross-section regex, which previously over-matched
 * and corrupted the Blade). Skills also render as a one-per-line bullet list to
 * match the editor preview + the Classic/Premium blades. Modern Minimal only;
 * Classic / Premium templates and the data contract are untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('resume_templates')) {
            return;
        }

        $row = DB::table('resume_templates')->where('template_key', 'modern_minimal')->first();
        if (!$row) {
            return;
        }

        DB::table('resume_templates')
            ->where('template_key', 'modern_minimal')
            ->update([
                'html_content' => $this->modernHtml(),
                'css_content'  => $this->withEducationCss((string) $row->css_content),
                'updated_at'   => now(),
            ]);
    }

    public function down(): void
    {
        // No-op: reverting presentation could clobber later admin edits, and the
        // old (broken) layout offers no functional value to restore.
    }

    /** Full, correct Modern Minimal body — fixed two-row Education + bullet Skills. */
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
      <ul>@foreach($d['skills'] as $s)<li>{{ $s }}</li>@endforeach</ul>
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
        <table style="width:100%;border-collapse:collapse;margin-bottom:7px">
          <tr>
            <td style="width:68%;text-align:left;vertical-align:top"><span class="ed-deg">{{ $e['degree'] }}</span></td>
            <td style="width:32%;text-align:right;vertical-align:top;white-space:nowrap;padding-left:14px">@if($e['year'])<span class="ed-dur">{{ $e['year'] }}</span>@endif</td>
          </tr>
          <tr>
            <td style="width:68%;text-align:left;vertical-align:top"><span class="ed-inst">{{ $e['institute'] }}</span></td>
            <td style="width:32%;text-align:right;vertical-align:top;white-space:nowrap;padding-left:14px">@if($e['score'])<span class="ed-score">{{ $e['score'] }}</span>@endif</td>
          </tr>
        </table>
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

    /**
     * Ensures the Education CSS exists with the highlighted score. If the ed-*
     * rules are absent, append them; otherwise just bump the muted score colour.
     */
    private function withEducationCss(string $css): string
    {
        if (strpos($css, '.ed-deg') === false) {
            return $css . "\n" . <<<'CSS'
  /* Education — Degree | Duration / Institute | Score (two-row layout) */
  .modern .ed-deg { font-size: 12.5px; font-weight: bold; color: #0f172a; }
  .modern .ed-dur { font-size: 10.5px; color: #94a3b8; }
  .modern .ed-inst { font-size: 11px; color: #475569; }
  .modern .ed-score { font-size: 10.5px; font-weight: bold; color: #0f172a; }
CSS;
        }

        // Highlight the score: muted #475569 → #0f172a (idempotent).
        return preg_replace(
            '/(\.modern\s+\.ed-score\s*\{[^}]*color:\s*)#475569/i',
            '${1}#0f172a',
            $css,
            1,
        ) ?? $css;
    }
};
