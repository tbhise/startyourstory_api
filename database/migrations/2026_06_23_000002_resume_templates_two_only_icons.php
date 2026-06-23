<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Resume Builder cleanup:
 *   1. Retire Executive Sidebar + Creative Professional (is_active = false). Only
 *      Classic Professional and Modern Minimal remain offered. Drafts pointing at a
 *      retired key are coerced to Classic by ResumeController::coerceTemplateKey.
 *   2. Add inline-SVG section/contact icons to the Classic + Modern DB templates so
 *      the backend HTML preview (and eventual PDF) match the React editor preview,
 *      which already shows lucide icons. Inline SVG renders in the browser preview
 *      and is the most portable option for mPDF too.
 *
 * Overwrites Classic + Modern html_content unconditionally (controlled rollout):
 * this is the canonical content for the two offered templates. CSS gets the icon
 * rules appended once (guarded so re-runs don't duplicate).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('resume_templates')) {
            return;
        }

        // 1. Retire the two templates we no longer offer.
        DB::table('resume_templates')
            ->whereIn('template_key', ['executive_sidebar', 'creative_professional'])
            ->update(['is_active' => false, 'updated_at' => now()]);

        $ic = $this->icons();

        // 2a. Classic Professional — section + contact icons.
        if (DB::table('resume_templates')->where('template_key', 'classic_professional')->exists()) {
            DB::table('resume_templates')
                ->where('template_key', 'classic_professional')
                ->update([
                    'html_content' => strtr($this->classicHtml(), $ic),
                    'css_content'  => $this->withIconCss(
                        (string) DB::table('resume_templates')->where('template_key', 'classic_professional')->value('css_content')
                    ),
                    'is_active'    => true,
                    'updated_at'   => now(),
                ]);
        }

        // 2b. Modern Minimal — contact icons only (its section headings are text-only,
        // matching the React template).
        if (DB::table('resume_templates')->where('template_key', 'modern_minimal')->exists()) {
            DB::table('resume_templates')
                ->where('template_key', 'modern_minimal')
                ->update([
                    'html_content' => strtr($this->modernHtml(), $ic),
                    'css_content'  => $this->withIconCss(
                        (string) DB::table('resume_templates')->where('template_key', 'modern_minimal')->value('css_content')
                    ),
                    'is_active'    => true,
                    'updated_at'   => now(),
                ]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('resume_templates')) {
            return;
        }
        // Re-activate the retired templates; leave template HTML as-is (no-op on
        // content to avoid clobbering any later admin edits).
        DB::table('resume_templates')
            ->whereIn('template_key', ['executive_sidebar', 'creative_professional'])
            ->update(['is_active' => true, 'updated_at' => now()]);
    }

    /** Appends the inline-icon CSS to an existing css_content (idempotent). */
    private function withIconCss(string $css): string
    {
        if (strpos($css, '.ic{') !== false || strpos($css, '.ic ') !== false) {
            return $css; // already present
        }
        return $css . "\n" . <<<'CSS'
  /* Inline section + contact icons (shared by HTML preview + PDF) */
  .ic { width: 13px; height: 13px; vertical-align: -2px; }
  .classic .sec .ic { margin-right: 6px; }
  .classic .contact .ic { width: 11px; height: 11px; vertical-align: -1px; margin-left: 5px; }
  .modern .contact .ci { display: inline-block; margin: 0 7px; }
  .modern .contact .ci .ic { width: 11px; height: 11px; vertical-align: -1px; margin-right: 4px; }
CSS;
    }

    /** lucide-matching inline SVGs, keyed by the placeholder used in the templates. */
    private function icons(): array
    {
        $open = '<svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">';
        return [
            '%IC_MAIL%'     => $open . '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>',
            '%IC_PHONE%'    => $open . '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>',
            '%IC_PIN%'      => $open . '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>',
            '%IC_LINKEDIN%' => $open . '<path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect width="4" height="12" x="2" y="9"/><circle cx="4" cy="4" r="2"/></svg>',
            '%IC_GLOBE%'    => $open . '<circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/></svg>',
            '%IC_USER%'     => $open . '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
            '%IC_CAP%'      => $open . '<path d="M21.42 10.922a1 1 0 0 0-.019-1.838L12.83 5.18a2 2 0 0 0-1.66 0L2.6 9.08a1 1 0 0 0 0 1.832l8.57 3.908a2 2 0 0 0 1.66 0z"/><path d="M22 10v6"/><path d="M6 12.5V16a6 3 0 0 0 12 0v-3.5"/></svg>',
            '%IC_BRIEF%'    => $open . '<path d="M16 20V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/><rect width="20" height="14" x="2" y="6" rx="2"/></svg>',
            '%IC_CHART%'    => $open . '<path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/></svg>',
            '%IC_BADGE%'    => $open . '<path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"/><path d="m9 12 2 2 4-4"/></svg>',
            '%IC_AWARD%'    => $open . '<path d="m15.477 12.89 1.515 8.526a.5.5 0 0 1-.81.47l-3.58-2.687a1 1 0 0 0-1.197 0l-3.586 2.686a.5.5 0 0 1-.81-.469l1.514-8.526"/><circle cx="12" cy="8" r="6"/></svg>',
        ];
    }

    /** Classic Professional — P0 layout + inline icons (placeholders resolved via strtr). */
    private function classicHtml(): string
    {
        return <<<'BLADE'
<div class="classic">
  <table>
    <tr>
      <td style="width:60%">
        <div class="name">{{ $d['name'] ?: 'Your Name' }}</div>
        @if($d['title'])<div class="sub" style="margin-top:2px">{{ $d['title'] }}</div>@endif
      </td>
      <td style="width:40%">
        <div class="contact">
          @if($d['email'])<div>{{ $d['email'] }}%IC_MAIL%</div>@endif
          @if($d['mobile'])<div>{{ $d['mobile'] }}%IC_PHONE%</div>@endif
          @if($d['location'])<div>{{ $d['location'] }}%IC_PIN%</div>@endif
          @if($d['linkedin'])<div>{{ $d['linkedin'] }}%IC_LINKEDIN%</div>@endif
          @if($d['website'])<div>{{ $d['website'] }}%IC_GLOBE%</div>@endif
        </div>
      </td>
    </tr>
  </table>
  <div class="rule"></div>

  @if($d['summary'])
    <div class="sec">%IC_USER%Summary</div><div class="secrule"></div>
    <p style="font-size:11.5px;color:#334155">{{ $d['summary'] }}</p>
  @endif

  @foreach($d['sectionOrder'] as $key)
    @if($key === 'education' && count($d['education']))
      <div class="sec">%IC_CAP%Education</div><div class="secrule"></div>
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
      <div class="sec">%IC_BRIEF%Experience</div><div class="secrule"></div>
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
      <div class="sec">%IC_CHART%Skills</div><div class="secrule"></div>
      <div>@foreach($d['skills'] as $s)<span class="chip">{{ $s }}</span> @endforeach</div>
    @endif
  @endforeach

  @if($d['showCertifications'] && count($d['certifications']))
    <div class="sec">%IC_BADGE%Certifications</div><div class="secrule"></div>
    <ul>@foreach($d['certifications'] as $c)<li>{{ $c }}</li>@endforeach</ul>
  @endif
  @if($d['showAchievements'] && count($d['achievements']))
    <div class="sec">%IC_AWARD%Achievements</div><div class="secrule"></div>
    <ul>@foreach($d['achievements'] as $a)<li>{{ $a }}</li>@endforeach</ul>
  @endif
</div>
BLADE;
    }

    /** Modern Minimal — P0 layout + per-contact icons (section headings stay text-only). */
    private function modernHtml(): string
    {
        return <<<'BLADE'
<div class="modern">
  <div class="name">{{ $d['name'] ?: 'Your Name' }}</div>
  @if($d['title'])<div class="title">{{ $d['title'] }}</div>@endif
  <div class="contact">
    @if($d['mobile'])<span class="ci">%IC_PHONE%{{ $d['mobile'] }}</span>@endif
    @if($d['email'])<span class="ci">%IC_MAIL%{{ $d['email'] }}</span>@endif
    @if($d['location'])<span class="ci">%IC_PIN%{{ $d['location'] }}</span>@endif
    @if($d['linkedin'])<span class="ci">%IC_LINKEDIN%{{ $d['linkedin'] }}</span>@endif
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
    }
};
