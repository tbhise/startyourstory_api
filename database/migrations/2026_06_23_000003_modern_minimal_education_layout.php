<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Modern Minimal — Education section redesign (Modern Minimal ONLY).
 *
 * Replaces the old single-line "year • score" education row with a premium
 * two-row layout:
 *     Degree                 Duration
 *     Institute              Score
 * (no bullet separator). Classic Professional and all other templates are
 * untouched; no controller/data-structure changes.
 *
 * Surgical: only the Modern education <table> (uniquely identified by its
 * `margin-bottom:4px` inline style) is swapped, and the education CSS is appended
 * once. Safe + idempotent — if the block was already patched or admin-edited, the
 * regex simply finds no match and the row is left as-is.
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

        $oldBlock = '/<table style="margin-bottom:4px">.*?<\/table>/s';
        $newBlock = <<<'BLADE'
<table style="margin-bottom:7px">
            <tr>
              <td style="width:70%"><span class="ed-deg">{{ $e['degree'] }}</span></td>
              <td style="width:30%;text-align:right">@if($e['year'])<span class="ed-dur">{{ $e['year'] }}</span>@endif</td>
            </tr>
            <tr>
              <td style="width:70%"><span class="ed-inst">{{ $e['institute'] }}</span></td>
              <td style="width:30%;text-align:right">@if($e['score'])<span class="ed-score">{{ $e['score'] }}</span>@endif</td>
            </tr>
          </table>
BLADE;

        $html = preg_replace($oldBlock, $newBlock, (string) $row->html_content, 1);
        $css  = $this->withEducationCss((string) $row->css_content);

        DB::table('resume_templates')
            ->where('template_key', 'modern_minimal')
            ->update([
                'html_content' => $html,
                'css_content'  => $css,
                'updated_at'   => now(),
            ]);
    }

    public function down(): void
    {
        // No-op: reverting presentation could clobber later admin edits, and the
        // old layout offers no functional value to restore.
    }

    /** Appends the Modern education CSS once (idempotent). */
    private function withEducationCss(string $css): string
    {
        if (strpos($css, '.ed-deg') !== false) {
            return $css;
        }
        return $css . "\n" . <<<'CSS'
  /* Education — Degree | Duration / Institute | Score (premium two-row layout) */
  .modern .ed-deg { font-size: 12.5px; font-weight: bold; color: #0f172a; }
  .modern .ed-dur { font-size: 10.5px; color: #94a3b8; }
  .modern .ed-inst { font-size: 11px; color: #475569; }
  .modern .ed-score { font-size: 10.5px; font-weight: bold; color: #475569; }
CSS;
    }
};
