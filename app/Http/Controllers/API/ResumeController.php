<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Resume Builder drafts. One draft per authenticated user, stored in `resumes`
 * with the selected template + full resume content as JSON.
 *
 * Auth: routes are behind ApiAuthMiddleware, which injects `auth_user`.
 * Data access is query-builder only, consistent with the rest of the API.
 */
class ResumeController extends Controller
{
    private const TEMPLATE_KEYS = [
        'classic_professional',
        'modern_minimal',
        'executive_sidebar',
        'creative_professional',
    ];

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

        $validator = Validator::make($request->all(), [
            'template_key' => 'required|string|in:' . implode(',', self::TEMPLATE_KEYS),
            'resume_data'  => 'required|array',
        ]);
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
     * Backend PDF generation (mPDF). Renders the server-side Blade replica of the
     * selected template and streams an A4 PDF. No browser / screenshot involved.
     */
    public function downloadPdf(Request $request)
    {
        $user = $request->attributes->get('auth_user');
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'template_key' => 'required|string|in:' . implode(',', self::TEMPLATE_KEYS),
            'resume_data'  => 'required|array',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        try {
            $t = $request->template_key;
            $d = $this->normalizeResume($request->input('resume_data'));

            $html = view('resume.pdf', ['t' => $t, 'd' => $d])->render();

            $tempDir = storage_path('app/mpdf');
            if (!is_dir($tempDir)) {
                @mkdir($tempDir, 0775, true);
            }

            $mpdf = new \Mpdf\Mpdf([
                'mode'             => 'utf-8',
                'format'           => 'A4',
                // Insets are handled inside the templates so full-bleed bands
                // (sidebar / header) can reach the page edge.
                'margin_left'      => 0,
                'margin_right'     => 0,
                'margin_top'       => 0,
                'margin_bottom'    => 0,
                'default_font'     => 'dejavusans',
                'useSubstitutions' => true,
                'tempDir'          => $tempDir,
            ]);
            $mpdf->SetTitle(($d['name'] ?: 'Resume') . ' — Resume');
            $mpdf->WriteHTML($html);

            $filename = Str::slug($d['name'] ?: 'resume') . '-resume.pdf';
            $content  = $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);

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

    /** Coerce the incoming resume_data into a clean, fully-typed structure for the view. */
    private function normalizeResume($d): array
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
            $experience[] = [
                'company'          => trim((string) ($x['company'] ?? '')),
                'duration'         => trim((string) ($x['duration'] ?? '')),
                'role'             => trim((string) ($x['role'] ?? '')),
                'responsibilities' => (string) ($x['responsibilities'] ?? ''),
            ];
        }

        $known = ['education', 'experience', 'skills'];
        $order = $d['sectionOrder'] ?? $known;
        if (!is_array($order)) $order = $known;
        $order = array_values(array_intersect($order, $known));
        foreach ($known as $k) {
            if (!in_array($k, $order, true)) $order[] = $k;
        }

        return [
            'name'               => $str('name'),
            'title'              => $str('title'),
            'email'              => $str('email'),
            'mobile'             => $str('mobile'),
            'location'           => $str('location'),
            'linkedin'           => $str('linkedin'),
            'website'            => $str('website'),
            'summary'            => $str('summary'),
            'education'          => $education,
            'experience'         => $experience,
            'skills'             => $strArr('skills'),
            'certifications'     => $strArr('certifications'),
            'achievements'       => $strArr('achievements'),
            'showPhoto'          => (bool) ($d['showPhoto'] ?? true),
            'showCertifications' => (bool) ($d['showCertifications'] ?? true),
            'showAchievements'   => (bool) ($d['showAchievements'] ?? true),
            'sectionOrder'       => $order,
        ];
    }
}
