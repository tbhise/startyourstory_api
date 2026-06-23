<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Classic Professional — Skills chip restyle (Classic ONLY).
 *
 * Premium pill chips: soft background, subtle border, 20px radius, roomier padding
 * (5px 13px) and proper inter-chip gaps. Skill text stays plain & selectable, so
 * ATS parsing is unaffected. Only the `.classic .chip` CSS rule changes — the HTML
 * markup is untouched, and Modern Minimal / data structure are not affected.
 *
 * Idempotent: a plain str_replace of the old rule; if it is absent (already patched
 * or admin-edited) the content is left unchanged.
 */
return new class extends Migration
{
    private const OLD_RULE = '.classic .chip { border: 1px solid #cbd5e1; padding: 1px 6px; font-size: 10px; color: #334155; }';
    private const NEW_RULE = '.classic .chip { display: inline-block; border: 1px solid #e2e8f0; background-color: #f8fafc; border-radius: 20px; padding: 5px 13px; margin: 0 5px 7px 0; font-size: 12px; color: #334155; }';

    public function up(): void
    {
        $this->swap(self::OLD_RULE, self::NEW_RULE);
    }

    public function down(): void
    {
        $this->swap(self::NEW_RULE, self::OLD_RULE);
    }

    private function swap(string $from, string $to): void
    {
        if (!Schema::hasTable('resume_templates')) {
            return;
        }
        $row = DB::table('resume_templates')->where('template_key', 'classic_professional')->first();
        if (!$row || strpos((string) $row->css_content, $from) === false) {
            return;
        }
        DB::table('resume_templates')
            ->where('template_key', 'classic_professional')
            ->update([
                'css_content' => str_replace($from, $to, (string) $row->css_content),
                'updated_at'  => now(),
            ]);
    }
};
