<?php

namespace App\Http\Controllers\API;

use App\Helpers\ImageHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * Admin CRUD for backend-managed resume templates (Part 4). These rows drive the
 * Resume PDF (mPDF) rendering in ResumeController::renderTemplateHtml. Auth is
 * enforced by AdminAuthMiddleware on the /admin/* routes (admin_user attribute).
 *
 * Query-builder only, matching the rest of the admin API.
 */
class ResumeTemplateController extends Controller
{
    private const PREVIEW_DIR = 'resume-templates';

    private function format(object $r): array
    {
        return [
            'id'            => $r->id,
            'template_name' => $r->template_name,
            'template_key'  => $r->template_key,
            'html_content'  => $r->html_content,
            'css_content'   => $r->css_content,
            'preview_image' => $r->preview_image ? asset('storage/' . $r->preview_image) : null,
            'is_active'     => (bool) $r->is_active,
            'updated_at'    => $r->updated_at,
            'created_at'    => $r->created_at,
        ];
    }

    /** GET /admin/resume-templates */
    public function index(): JsonResponse
    {
        $rows = DB::table('resume_templates')->orderBy('id')->get();
        return response()->json([
            'status' => true,
            'data'   => $rows->map(fn($r) => $this->format($r))->all(),
        ]);
    }

    /** POST /admin/resume-templates */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'template_name' => 'required|string|max:120',
            'template_key'  => 'required|string|max:50|regex:/^[a-z0-9_]+$/|unique:resume_templates,template_key',
            'html_content'  => 'required|string',
            'css_content'   => 'nullable|string',
            'is_active'     => 'nullable|boolean',
        ], [
            'template_key.regex'  => 'Template key may only contain lowercase letters, numbers and underscores.',
            'template_key.unique' => 'A template with this key already exists.',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        try {
            $id = DB::table('resume_templates')->insertGetId([
                'template_name' => trim($request->template_name),
                'template_key'  => trim($request->template_key),
                'html_content'  => (string) $request->html_content,
                'css_content'   => (string) $request->input('css_content', ''),
                'is_active'     => $request->boolean('is_active', true),
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            $row = DB::table('resume_templates')->where('id', $id)->first();
            return response()->json(['status' => true, 'message' => 'Template created.', 'data' => $this->format($row)]);
        } catch (\Throwable $e) {
            Log::error('ResumeTemplateController::store', ['message' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Failed to create template.'], 500);
        }
    }

    /** POST /admin/resume-templates/{id} */
    public function update(Request $request, int $id): JsonResponse
    {
        $row = DB::table('resume_templates')->where('id', $id)->first();
        if (!$row) {
            return response()->json(['status' => false, 'message' => 'Template not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'template_name' => 'required|string|max:120',
            'template_key'  => 'required|string|max:50|regex:/^[a-z0-9_]+$/|unique:resume_templates,template_key,' . $id,
            'html_content'  => 'required|string',
            'css_content'   => 'nullable|string',
            'is_active'     => 'nullable|boolean',
        ], [
            'template_key.regex'  => 'Template key may only contain lowercase letters, numbers and underscores.',
            'template_key.unique' => 'A template with this key already exists.',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        try {
            DB::table('resume_templates')->where('id', $id)->update([
                'template_name' => trim($request->template_name),
                'template_key'  => trim($request->template_key),
                'html_content'  => (string) $request->html_content,
                'css_content'   => (string) $request->input('css_content', ''),
                'is_active'     => $request->boolean('is_active', (bool) $row->is_active),
                'updated_at'    => now(),
            ]);

            $row = DB::table('resume_templates')->where('id', $id)->first();
            return response()->json(['status' => true, 'message' => 'Template updated.', 'data' => $this->format($row)]);
        } catch (\Throwable $e) {
            Log::error('ResumeTemplateController::update', ['message' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Failed to update template.'], 500);
        }
    }

    /** POST /admin/resume-templates/{id}/toggle-active */
    public function toggleActive(int $id): JsonResponse
    {
        $row = DB::table('resume_templates')->where('id', $id)->first();
        if (!$row) {
            return response()->json(['status' => false, 'message' => 'Template not found.'], 404);
        }

        DB::table('resume_templates')->where('id', $id)->update([
            'is_active'  => !$row->is_active,
            'updated_at' => now(),
        ]);

        return response()->json([
            'status'  => true,
            'message' => $row->is_active ? 'Template disabled.' : 'Template enabled.',
            'data'    => ['is_active' => !$row->is_active],
        ]);
    }

    /** POST /admin/resume-templates/{id}/preview  (multipart image) */
    public function uploadPreview(Request $request, int $id): JsonResponse
    {
        $row = DB::table('resume_templates')->where('id', $id)->first();
        if (!$row) {
            return response()->json(['status' => false, 'message' => 'Template not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'preview_image' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ], [
            'preview_image.required' => 'Please choose a preview image to upload.',
            'preview_image.image'    => 'The preview must be an image file.',
            'preview_image.mimes'    => 'Allowed formats: JPG, PNG, WEBP.',
            'preview_image.max'      => 'The preview image must be 5 MB or smaller.',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        try {
            $oldPath = (string) $row->preview_image;
            $newPath = ImageHelper::optimizeToWebp($request->file('preview_image'), self::PREVIEW_DIR, 'public');

            DB::table('resume_templates')->where('id', $id)->update([
                'preview_image' => $newPath,
                'updated_at'    => now(),
            ]);

            if ($oldPath && $oldPath !== $newPath) {
                Storage::disk('public')->delete($oldPath);
            }

            return response()->json([
                'status'  => true,
                'message' => 'Preview image uploaded.',
                'data'    => ['preview_image' => asset('storage/' . $newPath)],
            ]);
        } catch (\Throwable $e) {
            Log::error('ResumeTemplateController::uploadPreview', ['message' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Failed to upload preview image.'], 500);
        }
    }

    /** DELETE /admin/resume-templates/{id} */
    public function destroy(int $id): JsonResponse
    {
        $row = DB::table('resume_templates')->where('id', $id)->first();
        if (!$row) {
            return response()->json(['status' => false, 'message' => 'Template not found.'], 404);
        }

        if ($row->preview_image) {
            Storage::disk('public')->delete($row->preview_image);
        }
        DB::table('resume_templates')->where('id', $id)->delete();

        return response()->json(['status' => true, 'message' => 'Template deleted.']);
    }
}
