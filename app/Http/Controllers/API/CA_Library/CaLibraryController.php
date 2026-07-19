<?php

namespace App\Http\Controllers\API\CA_Library;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * CA Library — subjects, resource types and study materials.
 * All tables live in the separate `ca_library` database connection.
 * Courses / groups / exam attempts are static (config/ca_library.php).
 */
class CaLibraryController extends Controller
{
    private function db(string $table)
    {
        return DB::connection('ca_library')->table($table);
    }

    /** Shared validation: subject must match the selected course/group combo. */
    private function subjectMatchesCombo(object $subject, string $course, ?string $group): bool
    {
        if ($subject->course !== $course) {
            return false;
        }
        if ($course === 'CA Foundation') {
            return true; // Foundation has no groups
        }
        return $subject->group === $group
            || $subject->group === 'Both Groups'
            || $group === 'Both Groups';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PUBLIC — Filter options (static values + active subjects/resource types)
    // ─────────────────────────────────────────────────────────────────────────

    public function getFilters(): JsonResponse
    {
        try {
            $subjects = $this->db('ca_library_subjects')
                ->where('is_active', 1)
                ->orderBy('name')
                ->get(['id', 'course', 'group', 'name']);

            $types = $this->db('ca_library_resource_types')
                ->where('is_active', 1)
                ->orderBy('name')
                ->get(['id', 'name']);

            return response()->json([
                'status' => true,
                'data'   => [
                    'courses'        => config('ca_library.courses'),
                    'groups'         => config('ca_library.groups'),
                    'exam_attempts'  => config('ca_library.exam_attempts'),
                    'subjects'       => $subjects,
                    'resource_types' => $types,
                    // Current answer-sheet evaluation fee (display only — the
                    // backend re-reads and snapshots it at submission time).
                    'evaluation_fee' => (int) \App\Services\SystemSettingService::get('ca_library_evaluation_fee', 99),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('CaLibrary getFilters error', ['msg' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PUBLIC — Study material listing (active only)
    // ─────────────────────────────────────────────────────────────────────────

    public function getMaterials(Request $request): JsonResponse
    {
        try {
            $perPage = min(max((int) $request->query('per_page', 12), 1), 100);
            $page    = max((int) $request->query('page', 1), 1);

            $query = $this->materialsQuery($request, true);
            $total = $query->count();
            $items = $query->offset(($page - 1) * $perPage)->limit($perPage)->get();

            return response()->json([
                'status' => true,
                'data'   => [
                    'items'    => $items->map(fn ($m) => $this->serializeMaterial($m, false)),
                    'total'    => $total,
                    'page'     => $page,
                    'per_page' => $perPage,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('CaLibrary getMaterials error', ['msg' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    private function materialsQuery(Request $request, bool $activeOnly)
    {
        $q = $this->db('ca_library_study_materials as m')
            ->join('ca_library_subjects as s', 'm.subject_id', '=', 's.id')
            ->join('ca_library_resource_types as t', 'm.resource_type_id', '=', 't.id')
            ->select('m.*', 's.name as subject_name', 't.name as resource_type_name', 't.is_question_paper as resource_type_is_question_paper')
            ->orderByDesc('m.created_at');

        if ($activeOnly) {
            $q->where('m.is_active', 1);
        }
        if ($course = $request->query('course')) {
            $q->where('m.course', $course);
        }
        // "Both Groups" as a filter means everything in the course; a specific
        // group also matches materials stored as "Both Groups".
        $group = $request->query('group');
        if ($group && $group !== 'Both Groups') {
            $q->where(fn ($w) => $w->where('m.group', $group)->orWhere('m.group', 'Both Groups'));
        }
        if ($attempt = $request->query('exam_attempt')) {
            $q->where('m.exam_attempt', $attempt);
        }
        if ($subjectId = $request->query('subject_id')) {
            $q->where('m.subject_id', (int) $subjectId);
        }
        if ($typeId = $request->query('resource_type_id')) {
            $q->where('m.resource_type_id', (int) $typeId);
        }
        if ($search = trim((string) $request->query('search'))) {
            $q->where('m.title', 'like', '%' . $search . '%');
        }
        return $q;
    }

    private function serializeMaterial(object $m, bool $withFileUrl = true): array
    {
        return [
            'id'                 => $m->id,
            'title'              => $m->title,
            'course'             => $m->course,
            'group'              => $m->group,
            'subject'            => ['id' => $m->subject_id, 'name' => $m->subject_name],
            'resource_type'      => [
                'id'                => $m->resource_type_id,
                'name'              => $m->resource_type_name,
                'is_question_paper' => (bool) $m->resource_type_is_question_paper,
            ],
            'exam_attempt'       => $m->exam_attempt,
            'original_file_name' => $m->original_file_name,
            'file_size'          => (int) $m->file_size,
            // Public listing hides the direct URL — downloads go through the
            // authorized CA-student endpoint (CaStudentController@download).
            'file_url'           => $withFileUrl && $m->file_path ? asset('storage/' . ltrim($m->file_path, '/')) : null,
            'is_active'          => (bool) $m->is_active,
            'created_at'         => $m->created_at,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ADMIN — Subjects
    // ─────────────────────────────────────────────────────────────────────────

    public function adminGetSubjects(): JsonResponse
    {
        try {
            $subjects = $this->db('ca_library_subjects')
                ->orderBy('course')->orderBy('group')->orderBy('name')->get();
            return response()->json(['status' => true, 'data' => $subjects]);
        } catch (\Exception $e) {
            Log::error('CaLibrary adminGetSubjects error', ['msg' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    public function adminSaveSubject(Request $request, ?int $id = null): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'course'    => 'required|in:' . implode(',', config('ca_library.courses')),
                'group'     => 'required_unless:course,CA Foundation|nullable|in:' . implode(',', config('ca_library.groups')),
                'name'      => 'required|string|max:150',
                'is_active' => 'nullable|boolean',
            ]);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }

            $data = [
                'course'     => $request->input('course'),
                'group'      => $request->input('course') === 'CA Foundation' ? null : $request->input('group'),
                'name'       => $request->input('name'),
                'is_active'  => $request->boolean('is_active', true),
                'updated_at' => now(),
            ];

            if ($id) {
                $exists = $this->db('ca_library_subjects')->where('id', $id)->exists();
                if (! $exists) {
                    return response()->json(['status' => false, 'message' => 'Subject not found'], 404);
                }
                $this->db('ca_library_subjects')->where('id', $id)->update($data);
            } else {
                $id = $this->db('ca_library_subjects')->insertGetId($data + ['created_at' => now()]);
            }

            return response()->json(['status' => true, 'message' => 'Subject saved.', 'data' => ['id' => $id]]);
        } catch (\Exception $e) {
            Log::error('CaLibrary adminSaveSubject error', ['msg' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ADMIN — Resource types
    // ─────────────────────────────────────────────────────────────────────────

    public function adminGetResourceTypes(): JsonResponse
    {
        try {
            $types = $this->db('ca_library_resource_types')->orderBy('name')->get();
            return response()->json(['status' => true, 'data' => $types]);
        } catch (\Exception $e) {
            Log::error('CaLibrary adminGetResourceTypes error', ['msg' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    public function adminSaveResourceType(Request $request, ?int $id = null): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name'              => 'required|string|max:100',
                'is_question_paper' => 'nullable|boolean',
                'is_active'         => 'nullable|boolean',
            ]);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }

            $data = [
                'name'              => $request->input('name'),
                'is_question_paper' => $request->boolean('is_question_paper'),
                'is_active'         => $request->boolean('is_active', true),
                'updated_at'        => now(),
            ];

            if ($id) {
                $exists = $this->db('ca_library_resource_types')->where('id', $id)->exists();
                if (! $exists) {
                    return response()->json(['status' => false, 'message' => 'Resource type not found'], 404);
                }
                $this->db('ca_library_resource_types')->where('id', $id)->update($data);
            } else {
                $id = $this->db('ca_library_resource_types')->insertGetId($data + ['created_at' => now()]);
            }

            return response()->json(['status' => true, 'message' => 'Resource type saved.', 'data' => ['id' => $id]]);
        } catch (\Exception $e) {
            Log::error('CaLibrary adminSaveResourceType error', ['msg' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ADMIN — Study materials
    // ─────────────────────────────────────────────────────────────────────────

    public function adminGetMaterials(Request $request): JsonResponse
    {
        try {
            $items = $this->materialsQuery($request, false)->limit(200)->get();
            return response()->json([
                'status' => true,
                'data'   => [
                    'items' => $items->map(fn ($m) => $this->serializeMaterial($m)),
                    'total' => $items->count(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('CaLibrary adminGetMaterials error', ['msg' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    public function adminSaveMaterial(Request $request, ?int $id = null): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'course'           => 'required|in:' . implode(',', config('ca_library.courses')),
                'group'            => 'required_unless:course,CA Foundation|nullable|in:' . implode(',', config('ca_library.groups')),
                'subject_id'       => 'required|integer',
                'resource_type_id' => 'required|integer',
                'exam_attempt'     => 'required|in:' . implode(',', config('ca_library.exam_attempts')),
                'title'            => 'required|string|max:255',
                // File required on create, optional replace on edit.
                'file'             => ($id ? 'nullable' : 'required') . '|file|mimes:pdf|max:51200',
                'is_active'        => 'nullable|boolean',
            ]);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }

            $course = $request->input('course');
            $group  = $course === 'CA Foundation' ? null : $request->input('group');

            $subject = $this->db('ca_library_subjects')->where('id', (int) $request->input('subject_id'))->first();
            if (! $subject || ! $this->subjectMatchesCombo($subject, $course, $group)) {
                return response()->json(['status' => false, 'message' => 'Selected subject does not belong to the selected course/group.'], 422);
            }

            $typeExists = $this->db('ca_library_resource_types')->where('id', (int) $request->input('resource_type_id'))->exists();
            if (! $typeExists) {
                return response()->json(['status' => false, 'message' => 'Resource type not found.'], 422);
            }

            $existing = $id ? $this->db('ca_library_study_materials')->where('id', $id)->first() : null;
            if ($id && ! $existing) {
                return response()->json(['status' => false, 'message' => 'Study material not found'], 404);
            }

            $data = [
                'course'           => $course,
                'group'            => $group,
                'subject_id'       => (int) $request->input('subject_id'),
                'resource_type_id' => (int) $request->input('resource_type_id'),
                'exam_attempt'     => $request->input('exam_attempt'),
                'title'            => $request->input('title'),
                'is_active'        => $request->boolean('is_active', true),
                'updated_at'       => now(),
            ];

            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $data['file_path']          = $file->store('ca-library/materials', 'public');
                $data['original_file_name'] = $file->getClientOriginalName();
                $data['file_size']          = $file->getSize();
                // Remove the replaced PDF from storage.
                if ($existing && $existing->file_path) {
                    Storage::disk('public')->delete($existing->file_path);
                }
            }

            if ($id) {
                $this->db('ca_library_study_materials')->where('id', $id)->update($data);
            } else {
                $id = $this->db('ca_library_study_materials')->insertGetId($data + ['created_at' => now()]);
            }

            return response()->json(['status' => true, 'message' => 'Study material saved.', 'data' => ['id' => $id]]);
        } catch (\Exception $e) {
            Log::error('CaLibrary adminSaveMaterial error', ['msg' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ADMIN — Study Resources overview stats (all-time, backend-computed)
    // ─────────────────────────────────────────────────────────────────────────

    public function adminStats(): JsonResponse
    {
        try {
            $registeredStudents = $this->db('ca_students')->count();
            $pdfDownloads       = $this->db('ca_download_requests')->whereNotNull('downloaded_at')->count();
            $evaluationsSent    = $this->db('ca_test_submissions')->where('evaluation_status', 'completed')->count();

            // One row per submission (not per ca_payments attempt) — counting
            // ca_test_submissions.payment_status='paid' naturally avoids
            // double-counting retried/failed payment attempts.
            $paidSubmissions = $this->db('ca_test_submissions')->where('payment_status', 'paid');
            $paymentsDone    = (clone $paidSubmissions)->count();
            $revenue         = (float) (clone $paidSubmissions)->sum('amount');

            return response()->json([
                'status' => true,
                'data'   => [
                    'registered_students' => $registeredStudents,
                    'pdf_downloads'       => $pdfDownloads,
                    'evaluations_sent'    => $evaluationsSent,
                    'payments_done'       => $paymentsDone,
                    'revenue'             => round($revenue, 2),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('CaLibrary adminStats error', ['msg' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }
}
