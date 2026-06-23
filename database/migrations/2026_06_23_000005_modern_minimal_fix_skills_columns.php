<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Modern Minimal — fix "Undefined variable $c1" on PDF download (Modern Minimal ONLY).
 *
 * The seeded Modern Minimal template split skills into two columns via an inline
 * `@php $c1 = ...; $c2 = ...; @endphp` block. ResumeController::renderTemplateHtml()
 * strips ALL @php..@endphp blocks before rendering admin-authored content, so the
 * assignments were removed and the later `@foreach($c1 ...)` referenced an undefined
 * variable → 500 on download/preview.
 *
 * Fix: point the two skill-column loops at the controller-precomputed halves
 * ($d['skills_c1'] / $d['skills_c2'], built in normalizeResume()), which exist
 * precisely so DB templates need no @php. The now-dead @php line is also removed.
 *
 * Surgical + idempotent: matches only the exact buggy loops; if already patched
 * (e.g. fixed manually) the str_replace finds nothing and the row is left as-is.
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

        $html = (string) $row->html_content;

        // 1) Use the precomputed skill halves instead of the stripped @php locals.
        $html = str_replace('@foreach($c1 as $s)', "@foreach(\$d['skills_c1'] as \$s)", $html);
        $html = str_replace('@foreach($c2 as $s)', "@foreach(\$d['skills_c2'] as \$s)", $html);

        // 2) Drop the now-dead @php skill-split line (stripped at render time anyway).
        $html = preg_replace('/^[ \t]*@php .*?\$c1 = array_slice.*?@endphp[ \t]*\R/m', '', $html);

        if ($html === (string) $row->html_content) {
            return; // nothing matched — already patched / admin-edited
        }

        DB::table('resume_templates')
            ->where('template_key', 'modern_minimal')
            ->update([
                'html_content' => $html,
                'updated_at'   => now(),
            ]);
    }

    public function down(): void
    {
        // No-op: restoring the broken @php/$c1 variant offers no value and would
        // re-introduce the 500. Presentation-only forward fix.
    }
};
