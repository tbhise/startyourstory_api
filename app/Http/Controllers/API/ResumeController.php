<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

/**
 * Resume Builder drafts. One draft per authenticated user, stored in `resumes`
 * with the selected template + full resume content as JSON.
 *
 * Auth: routes are behind ApiAuthMiddleware, which injects `auth_user`.
 * Data access is query-builder only, consistent with the rest of the API.
 */
class ResumeController extends Controller
{
    // Currently-offered builtin keys (fallback when resume_templates table is
    // unavailable). Executive Sidebar / Creative Professional are retired — they
    // are no longer offered, so any draft referencing them is coerced to Classic.
    private const TEMPLATE_KEYS = [
        'classic_professional',
        'modern_minimal',
        'premium_minimal',
        'premium_resume',
    ];

    private const DEFAULT_TEMPLATE_KEY = 'classic_professional';

    /**
     * File-based templates: keys that render from a standalone Blade view in
     * resources/views/resume/ instead of an admin-managed DB row. Lets us drop in a
     * hand-authored template (its own full HTML/CSS) and switch to it by key.
     */
    private const FILE_TEMPLATES = [
        'premium_minimal' => 'resume.premium_minimal',
        'premium_resume'  => 'resume.premium_resume',
    ];

    /**
     * Returns the union of builtin keys and any currently-active DB keys.
     * Called on every save/pdf request so newly admin-created templates are
     * accepted immediately without a code deploy.
     */
    private function activeTemplateKeys(): array
    {
        $dbKeys = [];
        if (Schema::hasTable('resume_templates')) {
            $dbKeys = DB::table('resume_templates')
                ->where('is_active', true)
                ->pluck('template_key')
                ->toArray();
        }
        return array_values(array_unique(array_merge(self::TEMPLATE_KEYS, $dbKeys)));
    }

    /**
     * Coerces any requested key (legacy / retired / unknown) to a currently-offered
     * one, defaulting to Classic Professional. Lets old drafts and old clients that
     * still send a retired key save + render gracefully instead of 422-ing.
     */
    private function coerceTemplateKey($t): string
    {
        $t = is_string($t) ? $t : '';
        return in_array($t, $this->activeTemplateKeys(), true) ? $t : self::DEFAULT_TEMPLATE_KEY;
    }

    /** Shared field-level validation rules for the resume_data payload. */
    private function resumeDataRules(): array
    {
        return [
            'resume_data.name'                          => 'nullable|string|max:100',
            'resume_data.title'                         => 'nullable|string|max:100',
            'resume_data.email'                         => 'nullable|string|max:150',
            'resume_data.mobile'                        => 'nullable|string|max:25',
            'resume_data.location'                      => 'nullable|string|max:150',
            'resume_data.linkedin'                      => 'nullable|string|max:255',
            'resume_data.website'                       => 'nullable|string|max:255',
            'resume_data.summary'                       => 'nullable|string|max:2000',
            'resume_data.education'                     => 'nullable|array|max:10',
            'resume_data.education.*.degree'            => 'nullable|string|max:150',
            'resume_data.education.*.institute'         => 'nullable|string|max:150',
            'resume_data.education.*.year'              => 'nullable|string|max:20',
            'resume_data.education.*.score'             => 'nullable|string|max:20',
            'resume_data.experience'                    => 'nullable|array|max:10',
            'resume_data.experience.*.company'          => 'nullable|string|max:150',
            'resume_data.experience.*.role'             => 'nullable|string|max:150',
            'resume_data.experience.*.duration'         => 'nullable|string|max:60',
            'resume_data.experience.*.responsibilities' => 'nullable|string|max:3000',
            // Structured From/To Month-Year fields (kept in sync with `duration`).
            'resume_data.experience.*.startMonth'       => 'nullable|string|max:12',
            'resume_data.experience.*.startYear'        => 'nullable|string|max:4',
            'resume_data.experience.*.endMonth'         => 'nullable|string|max:12',
            'resume_data.experience.*.endYear'          => 'nullable|string|max:4',
            'resume_data.experience.*.current'          => 'nullable|boolean',
            'resume_data.skills'                        => 'nullable|array|max:20',
            'resume_data.skills.*'                      => 'string|max:80',
            'resume_data.certifications'                => 'nullable|array|max:20',
            'resume_data.certifications.*'              => 'string|max:150',
            'resume_data.achievements'                  => 'nullable|array|max:20',
            'resume_data.achievements.*'                => 'string|max:200',
        ];
    }

    public function getResume(Request $request)
    {
        $user = $request->attributes->get('auth_user');
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $row = DB::table('resumes')->where('user_id', $user->id)->first();

        if (!$row) {
            return response()->json(['status' => true, 'data' => null]);
        }

        $resumeData = $row->resume_data ? json_decode($row->resume_data, true) : null;

        return response()->json([
            'status' => true,
            'data'   => [
                'template_key' => $row->template_key,
                'resume_data'  => $resumeData,
                'updated_at'   => $row->updated_at,
            ],
        ]);
    }

    public function saveResume(Request $request)
    {
        $user = $request->attributes->get('auth_user');
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        // Retired/unknown template keys degrade to Classic (also migrates the
        // stored value for existing drafts that still point at a removed template).
        $request->merge(['template_key' => $this->coerceTemplateKey($request->input('template_key'))]);

        $validator = Validator::make($request->all(), array_merge([
            'template_key' => 'required|string|in:' . implode(',', $this->activeTemplateKeys()),
            'resume_data'  => 'required|array',
        ], $this->resumeDataRules()));
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        try {
            $payload = [
                'template_key' => $request->template_key,
                'resume_data'  => json_encode($request->input('resume_data')),
                'updated_at'   => now(),
            ];

            $exists = DB::table('resumes')->where('user_id', $user->id)->exists();

            if ($exists) {
                DB::table('resumes')->where('user_id', $user->id)->update($payload);
            } else {
                $payload['user_id']    = $user->id;
                $payload['created_at'] = now();
                DB::table('resumes')->insert($payload);
            }

            return response()->json(['status' => true, 'message' => 'Resume draft saved.']);
        } catch (\Throwable $e) {
            Log::error('ResumeController::saveResume', ['message' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Something went wrong.'], 500);
        }
    }

    public function deleteResume(Request $request)
    {
        $user = $request->attributes->get('auth_user');
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        DB::table('resumes')->where('user_id', $user->id)->delete();

        return response()->json(['status' => true, 'message' => 'Resume deleted.']);
    }

    /**
     * Backend PDF generation (headless Chromium via Spatie Browsershot). Renders the
     * server-side Blade replica of the selected template and streams an A4 PDF.
     *
     * The engine is a real browser: it renders the EXACT same HTML/CSS/SVG/fonts the
     * Backend Preview shows, so the downloaded PDF matches the preview far more
     * faithfully than the legacy mPDF path did (no dashed-border breakage, accurate
     * page breaks, crisp vector text). The endpoint, auth, validation, normalized
     * data contract and `<Name>_resume.pdf` filename are all UNCHANGED — only the
     * rendering engine swapped from mPDF to Browsershot.
     */
    public function downloadPdf(Request $request)
    {
        $user = $request->attributes->get('auth_user');
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        // Retired/unknown template keys degrade to Classic before rendering.
        $request->merge(['template_key' => $this->coerceTemplateKey($request->input('template_key'))]);

        $validator = Validator::make($request->all(), array_merge([
            'template_key' => 'required|string|in:' . implode(',', $this->activeTemplateKeys()),
            'resume_data'  => 'required|array',
        ], $this->resumeDataRules()));
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        try {
            $t = $request->template_key;
            $d = $this->normalizeResume($request->input('resume_data'));

            // Same Blade render the Backend Preview uses — the PDF and the preview
            // are produced from one identical HTML document.
            $html = $this->renderTemplateHtml($t, $d);

            // Filename: "<Name>_resume.pdf" (e.g. "Ananya_Iyer_resume.pdf"), or
            // "resume.pdf" when no name is set.
            $slug = trim(preg_replace('/[^A-Za-z0-9]+/', '_', trim((string) $d['name'])), '_');
            $filename = ($slug !== '' ? $slug . '_resume' : 'resume') . '.pdf';

            $content = $this->renderResumePdf($html);

            return response($content, 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control'       => 'no-store',
            ]);
        } catch (\Throwable $e) {
            Log::error('ResumeController::downloadPdf', ['message' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Failed to generate PDF.'], 500);
        }
    }

    /**
     * Render a full HTML document to an A4 PDF (binary string) with a real
     * headless-Chromium engine via Spatie Browsershot. Per-environment binary paths
     * live in config/resumepdf.php (.env keys), so the same code runs on a local box
     * and a Linux VPS with no edits.
     *
     *  - format A4, margins 0  → the templates own their page insets (full-bleed
     *    header/sidebar bands reach the page edge), identical to the old mPDF config.
     *  - showBackground        → colored bands / backgrounds print (Chrome strips
     *    them by default).
     *  - print media (default) → honors the templates' page-break-* rules so long /
     *    multi-page resumes break cleanly.
     *  - the document is fully self-contained (inline CSS + inline SVG, no external
     *    fonts/links), so Chromium makes no network requests while rendering.
     */
    private function renderResumePdf(string $html): string
    {
        // Default to [] so a missing/uncached config/resumepdf.php degrades to the
        // built-in defaults instead of throwing "array offset on null" below.
        $cfg = config('resumepdf') ?? [];

        $shot = \Spatie\Browsershot\Browsershot::html($html)
            ->format('A4')
            ->showBackground()
            ->margins(0, 0, 0, 0)
            ->timeout((int) ($cfg['timeout'] ?? 60));

        // Resolve puppeteer (and its bundled Chromium) from the project's
        // node_modules unless an explicit location is configured.
        $modulesBase = ($cfg['node_modules'] ?? '') ?: base_path();
        $shot->setNodeModulePath(rtrim($modulesBase, '/\\') . DIRECTORY_SEPARATOR . 'node_modules');

        if (!empty($cfg['node_binary'])) $shot->setNodeBinary($cfg['node_binary']);
        if (!empty($cfg['npm_binary']))  $shot->setNpmBinary($cfg['npm_binary']);
        if (!empty($cfg['chrome_path'])) $shot->setChromePath($cfg['chrome_path']);
        // Chromium refuses its sandbox when run as root (typical on a VPS). Safe to
        // disable here: the HTML is generated from our own templates, never user markup.
        if (!empty($cfg['no_sandbox']))  $shot->noSandbox();

        return $shot->pdf();
    }

    /**
     * TEMPORARY (template-development workflow) — render the selected template as
     * a full, A4-sized HTML document for in-browser preview instead of a PDF.
     *
     * Reuses the SAME normalizeResume() + renderTemplateHtml() pipeline the PDF
     * endpoint uses (so preview === eventual PDF), then wraps the document with
     * print/A4 page CSS so the browser tab mirrors the printed page. No mPDF is
     * involved, so this is cheap and safe to call repeatedly during dev.
     *
     * Returns text/html (NOT a binary download). Auth identical to downloadPdf.
     */
    public function previewHtml(Request $request)
    {
        $user = $request->attributes->get('auth_user');
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        // Retired/unknown template keys degrade to Classic before rendering.
        $request->merge(['template_key' => $this->coerceTemplateKey($request->input('template_key'))]);

        $validator = Validator::make($request->all(), array_merge([
            'template_key' => 'required|string|in:' . implode(',', $this->activeTemplateKeys()),
            'resume_data'  => 'required|array',
        ], $this->resumeDataRules()));
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        try {
            $t   = $request->template_key;
            $d   = $this->normalizeResume($request->input('resume_data'));
            $doc = $this->renderTemplateHtml($t, $d);

            // Inject A4/print CSS before </head> and frame the body in an A4 page
            // wrapper. Both render paths produce a standard <head>…</head><body>…
            // </body> document, so this string surgery is deterministic.
            $previewCss = <<<'CSS'
<style>
  @page { size: A4; margin: 0; }
  html, body { margin: 0; padding: 0; background: #525659; }
  .rb-a4-page {
    width: 210mm;
    min-height: 297mm;
    margin: 16px auto;
    background: #ffffff;
    box-shadow: 0 4px 24px rgba(0,0,0,0.35);
    overflow: hidden;
    position: relative;
  }
  @media print {
    html, body { background: #ffffff; }
    .rb-a4-page { margin: 0; box-shadow: none; width: auto; min-height: 0; }
  }
</style>
CSS;

            $html = preg_replace('/<\/head>/i', $previewCss . '</head>', $doc, 1);
            $html = preg_replace('/<body([^>]*)>/i', '<body$1><div class="rb-a4-page">', (string) $html, 1);
            $html = preg_replace('/<\/body>/i', '</div></body>', (string) $html, 1);

            return response((string) $html, 200, [
                'Content-Type'  => 'text/html; charset=UTF-8',
                'Cache-Control' => 'no-store',
            ]);
        } catch (\Throwable $e) {
            Log::error('ResumeController::previewHtml', ['message' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Failed to render preview.'], 500);
        }
    }

    /**
     * Resolve the PDF HTML for a template key. Backend-managed templates (Part 4)
     * win: the ACTIVE `resume_templates` row is rendered via Blade string render.
     * Falls back to the static `resume/pdf.blade.php` view when the table is
     * missing/empty or the key has no active row — so PDF generation never breaks.
     */
    private function renderTemplateHtml(string $t, array $d): string
    {
        // File-based templates render straight from their own Blade view (full
        // self-contained HTML doc), bypassing the DB rows entirely.
        if (isset(self::FILE_TEMPLATES[$t]) && view()->exists(self::FILE_TEMPLATES[$t])) {
            return view(self::FILE_TEMPLATES[$t], ['t' => $t, 'd' => $d])->render();
        }

        $tpl = null;
        if (Schema::hasTable('resume_templates')) {
            $tpl = DB::table('resume_templates')
                ->where('template_key', $t)
                ->where('is_active', true)
                ->first();
        }

        if (!$tpl || !trim((string) $tpl->html_content)) {
            return view('resume.pdf', ['t' => $t, 'd' => $d])->render();
        }

        $document =
            '<!DOCTYPE html><html><head><meta charset="utf-8"><style>'
            . (string) $tpl->css_content
            . '</style></head><body>'
            . (string) $tpl->html_content
            . '</body></html>';

        // Strip @php/@endphp blocks and raw PHP open-tags before Blade renders
        // admin-authored content. These are the only constructs that can execute
        // arbitrary server-side code; all safe Blade directives (@if, @foreach,
        // {{ }}) remain intact. Pre-computed fields in $d (skills_c1/c2, lines)
        // mean no legitimate template needs @php.
        $safe = preg_replace('/@php\b.*?@endphp/si', '', $document);
        $safe = preg_replace('/<\?(?:php|=)/i', '', (string) $safe);

        return Blade::render((string) $safe, ['d' => $d]);
    }

    /** Coerce the incoming resume_data into a clean, fully-typed structure for the view. */
    private function normalizeResume(mixed $d): array
    {
        $d = is_array($d) ? $d : [];

        $str = fn(string $k) => is_string($d[$k] ?? null) ? trim($d[$k]) : '';
        $strArr = function (string $k) use ($d): array {
            $v = $d[$k] ?? [];
            if (!is_array($v)) return [];
            $out = [];
            foreach ($v as $item) {
                if (is_string($item) && trim($item) !== '') $out[] = trim($item);
            }
            return $out;
        };

        $education = [];
        foreach ((array) ($d['education'] ?? []) as $e) {
            if (!is_array($e)) continue;
            $education[] = [
                'degree'    => trim((string) ($e['degree'] ?? '')),
                'institute' => trim((string) ($e['institute'] ?? '')),
                'year'      => trim((string) ($e['year'] ?? '')),
                'score'     => trim((string) ($e['score'] ?? '')),
            ];
        }

        $experience = [];
        foreach ((array) ($d['experience'] ?? []) as $x) {
            if (!is_array($x)) continue;
            $resp = (string) ($x['responsibilities'] ?? '');
            // Precompute bullet lines so DB-managed templates need no PHP helpers.
            $lines = array_values(array_filter(
                array_map('trim', preg_split('/\r\n|\r|\n/', $resp) ?: []),
                fn($l) => $l !== ''
            ));
            $experience[] = [
                'company'          => trim((string) ($x['company'] ?? '')),
                'duration'         => trim((string) ($x['duration'] ?? '')),
                'role'             => trim((string) ($x['role'] ?? '')),
                'responsibilities' => $resp,
                'lines'            => $lines,
            ];
        }

        $known = ['education', 'experience', 'skills'];
        $order = $d['sectionOrder'] ?? $known;
        if (!is_array($order)) $order = $known;
        $order = array_values(array_intersect($order, $known));
        foreach ($known as $k) {
            if (!in_array($k, $order, true)) $order[] = $k;
        }

        // Initials for the Executive photo block (templates avoid custom helpers).
        $initials = '';
        foreach (preg_split('/\s+/', trim($str('name'))) ?: [] as $w) {
            if ($w !== '') $initials .= mb_substr($w, 0, 1);
            if (mb_strlen($initials) >= 2) break;
        }
        $initials = mb_strtoupper($initials !== '' ? $initials : 'CV');

        $skills = $strArr('skills');
        $half   = (int) ceil(count($skills) / 2);

        return [
            'name'               => $str('name'),
            'initials'           => $initials,
            'title'              => $str('title'),
            'email'              => $str('email'),
            'mobile'             => $str('mobile'),
            'location'           => $str('location'),
            'linkedin'           => $str('linkedin'),
            'website'            => $str('website'),
            'summary'            => $str('summary'),
            'education'          => $education,
            'experience'         => $experience,
            'skills'             => $skills,
            // Pre-computed halves so DB-managed templates need no @php directive.
            'skills_c1'          => array_slice($skills, 0, $half),
            'skills_c2'          => array_slice($skills, $half),
            'certifications'     => $strArr('certifications'),
            'achievements'       => $strArr('achievements'),
            // Photo is intrinsic to the template (Executive Sidebar only) — never a user choice.
            'showCertifications' => (bool) ($d['showCertifications'] ?? true),
            'showAchievements'   => (bool) ($d['showAchievements'] ?? true),
            'sectionOrder'       => $order,
        ];
    }
}
