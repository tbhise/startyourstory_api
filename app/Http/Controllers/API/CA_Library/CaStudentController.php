<?php

namespace App\Http\Controllers\API\CA_Library;

use App\Http\Controllers\Controller;
use App\Mail\CaLibraryVerifyMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * CA Library student access + download tracking. Completely isolated from SYS
 * auth: own students/sessions tables (ca_library DB) and own ca_auth_token
 * cookie (see CaStudentAuthMiddleware). First download is email-verification
 * only — password stays NULL until the student optionally sets one.
 */
class CaStudentController extends Controller
{
    private const SESSION_DAYS = 7;

    private function db(string $table)
    {
        return DB::connection('ca_library')->table($table);
    }

    /** Resolve the authenticated student from the cookie, or null (for routes without middleware). */
    private function studentFromCookie(Request $request): ?object
    {
        $token = $request->cookie('ca_auth_token');
        if (! $token) {
            return null;
        }
        $session = $this->db('ca_sessions')->where('token', $token)->first();
        if (! $session || ($session->expires_at && now()->gt($session->expires_at))) {
            return null;
        }
        return $this->db('ca_students')
            ->where('id', $session->student_id)
            ->where('status', 'active')
            ->first();
    }

    private function startSession(int $studentId): string
    {
        $token = bin2hex(random_bytes(32));
        $this->db('ca_sessions')->insert([
            'student_id' => $studentId,
            'token'      => $token,
            'expires_at' => now()->addDays(self::SESSION_DAYS),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return $token;
    }

    /** Same cookie parameters as the SYS auth_token cookie, different name. */
    private function authCookie(JsonResponse $response, string $token): JsonResponse
    {
        return $response->cookie('ca_auth_token', $token, 60 * 24 * self::SESSION_DAYS, '/', null, false, false, false);
    }

    private function serializeStudent(object $s): array
    {
        return [
            'id'             => $s->id,
            'email'          => $s->email,
            'email_verified' => (bool) $s->email_verified_at,
            'has_password'   => $s->password !== null,
        ];
    }

    /** One row per student+material; repeat requests refresh the existing row. */
    private function upsertDownloadRequest(int $studentId, int $materialId, string $status): void
    {
        $existing = $this->db('ca_download_requests')
            ->where('student_id', $studentId)
            ->where('study_material_id', $materialId)
            ->first();

        if ($existing) {
            $this->db('ca_download_requests')->where('id', $existing->id)->update([
                'status'      => $status === 'ready' ? 'ready' : $existing->status,
                'verified_at' => $status === 'ready' ? ($existing->verified_at ?? now()) : $existing->verified_at,
                'updated_at'  => now(),
            ]);
        } else {
            $this->db('ca_download_requests')->insert([
                'student_id'        => $studentId,
                'study_material_id' => $materialId,
                'status'            => $status,
                'verified_at'       => $status === 'ready' ? now() : null,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PUBLIC — Request a download (authenticated → ready; guest → email flow)
    // ─────────────────────────────────────────────────────────────────────────

    public function requestDownload(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'material_id' => 'required|integer',
                'email'       => 'nullable|email|max:255',
            ]);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }

            $material = $this->db('ca_library_study_materials')
                ->where('id', (int) $request->input('material_id'))
                ->where('is_active', 1)
                ->first();
            if (! $material) {
                return response()->json(['status' => false, 'message' => 'Resource not found'], 404);
            }

            // Already authenticated as a CA Library student → instant access.
            $student = $this->studentFromCookie($request);
            if ($student) {
                $this->upsertDownloadRequest($student->id, $material->id, 'ready');
                return response()->json([
                    'status' => true,
                    'data'   => ['authenticated' => true, 'material_id' => $material->id],
                ]);
            }

            // Guest → needs an email to start the verification flow.
            $email = strtolower(trim((string) $request->input('email')));
            if (! $email) {
                return response()->json([
                    'status' => true,
                    'data'   => ['authenticated' => false, 'email_required' => true],
                ]);
            }

            // Find-or-create by normalized email (unique index guards races).
            $studentRow = $this->db('ca_students')->where('email', $email)->first();
            if (! $studentRow) {
                try {
                    $id = $this->db('ca_students')->insertGetId([
                        'email'      => $email,
                        'status'     => 'active',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } catch (\Illuminate\Database\UniqueConstraintViolationException) {
                    $id = $this->db('ca_students')->where('email', $email)->value('id');
                }
                $studentRow = $this->db('ca_students')->where('id', $id)->first();
            }
            if ($studentRow->status !== 'active') {
                return response()->json(['status' => false, 'message' => 'This account is not active.'], 403);
            }

            $this->upsertDownloadRequest($studentRow->id, $material->id, 'pending');

            // Fresh single-use token per request; only the hash is stored.
            $rawToken = bin2hex(random_bytes(32));
            $this->db('ca_students')->where('id', $studentRow->id)->update([
                'verify_token_hash'       => hash('sha256', $rawToken),
                'verify_token_expires_at' => now()->addMinutes(60),
                'updated_at'              => now(),
            ]);

            $frontend  = rtrim((string) config('app.frontend_url'), '/');
            $verifyUrl = $frontend . '/ca-library/verify?sid=' . $studentRow->id . '&token=' . $rawToken;

            Mail::to($email)->queue(new CaLibraryVerifyMail($verifyUrl, $material->title));

            return response()->json([
                'status'  => true,
                'message' => 'Verification link sent. Please check your email.',
                'data'    => ['authenticated' => false, 'verification_sent' => true],
            ]);
        } catch (\Exception $e) {
            Log::error('CaStudent requestDownload error', ['msg' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PUBLIC — Verify email link → authenticate + unlock pending requests
    // ─────────────────────────────────────────────────────────────────────────

    public function verify(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'sid'   => 'required|integer',
                'token' => 'required|string|size:64',
            ]);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => 'Invalid verification link.'], 422);
            }

            $student = $this->db('ca_students')->where('id', (int) $request->input('sid'))->first();
            $hash    = hash('sha256', (string) $request->input('token'));

            if (
                ! $student
                || $student->status !== 'active'
                || ! $student->verify_token_hash
                || ! hash_equals($student->verify_token_hash, $hash)
            ) {
                return response()->json(['status' => false, 'message' => 'This verification link is invalid or has already been used.'], 422);
            }
            if (! $student->verify_token_expires_at || now()->gt($student->verify_token_expires_at)) {
                return response()->json(['status' => false, 'message' => 'This verification link has expired. Please request the download again.'], 422);
            }

            // Single-use: consume the token, mark verified, unlock requests.
            $this->db('ca_students')->where('id', $student->id)->update([
                'email_verified_at'       => $student->email_verified_at ?? now(),
                'verify_token_hash'       => null,
                'verify_token_expires_at' => null,
                'updated_at'              => now(),
            ]);
            $this->db('ca_download_requests')
                ->where('student_id', $student->id)
                ->where('status', 'pending')
                ->update(['status' => 'ready', 'verified_at' => now(), 'updated_at' => now()]);

            $token   = $this->startSession($student->id);
            $student = $this->db('ca_students')->where('id', $student->id)->first();

            return $this->authCookie(response()->json([
                'status'  => true,
                'message' => 'Email verified.',
                'data'    => ['student' => $this->serializeStudent($student)],
            ]), $token);
        } catch (\Exception $e) {
            Log::error('CaStudent verify error', ['msg' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PUBLIC — Email/password login (only for students who set a password)
    // ─────────────────────────────────────────────────────────────────────────

    public function login(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email'    => 'required|email|max:255',
                'password' => 'required|string',
            ]);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }

            $email   = strtolower(trim((string) $request->input('email')));
            $student = $this->db('ca_students')->where('email', $email)->where('status', 'active')->first();

            // password NULL → password login not available yet for this account.
            if (! $student || ! $student->password || ! Hash::check($request->input('password'), $student->password)) {
                return response()->json(['status' => false, 'message' => 'Invalid email or password.'], 401);
            }

            $token = $this->startSession($student->id);

            return $this->authCookie(response()->json([
                'status'  => true,
                'message' => 'Login successful.',
                'data'    => ['student' => $this->serializeStudent($student)],
            ]), $token);
        } catch (\Exception $e) {
            Log::error('CaStudent login error', ['msg' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AUTH (ca.student) — Current student
    // ─────────────────────────────────────────────────────────────────────────

    public function me(Request $request): JsonResponse
    {
        $student = $request->attributes->get('ca_student');
        return response()->json(['status' => true, 'data' => ['student' => $this->serializeStudent($student)]]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AUTH (ca.student) — My Library (ready download requests)
    // ─────────────────────────────────────────────────────────────────────────

    public function myLibrary(Request $request): JsonResponse
    {
        try {
            $student = $request->attributes->get('ca_student');

            $rows = $this->db('ca_download_requests as r')
                ->join('ca_library_study_materials as m', 'r.study_material_id', '=', 'm.id')
                ->join('ca_library_subjects as s', 'm.subject_id', '=', 's.id')
                ->join('ca_library_resource_types as t', 'm.resource_type_id', '=', 't.id')
                ->where('r.student_id', $student->id)
                ->where('r.status', 'ready')
                ->where('m.is_active', 1)
                ->orderByDesc('r.updated_at')
                ->get([
                    'r.id as request_id', 'r.verified_at', 'r.downloaded_at', 'r.created_at as requested_at',
                    'm.id as material_id', 'm.title', 'm.course', 'm.group', 'm.exam_attempt',
                    'm.original_file_name', 'm.file_size',
                    's.name as subject_name', 't.name as resource_type_name', 't.id as resource_type_id',
                ]);

            $items = $rows->map(fn ($r) => [
                'request_id'         => $r->request_id,
                'material_id'        => $r->material_id,
                'title'              => $r->title,
                'course'             => $r->course,
                'group'              => $r->group,
                'subject'            => $r->subject_name,
                'resource_type'      => ['id' => $r->resource_type_id, 'name' => $r->resource_type_name],
                'exam_attempt'       => $r->exam_attempt,
                'original_file_name' => $r->original_file_name,
                'file_size'          => (int) $r->file_size,
                'requested_at'       => $r->requested_at,
                'downloaded_at'      => $r->downloaded_at,
            ]);

            return response()->json(['status' => true, 'data' => ['items' => $items]]);
        } catch (\Exception $e) {
            Log::error('CaStudent myLibrary error', ['msg' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AUTH (ca.student) — Secure PDF download
    // ─────────────────────────────────────────────────────────────────────────

    public function download(Request $request, int $materialId)
    {
        try {
            $student = $request->attributes->get('ca_student');

            // Authorization chain: active material + a ready request for THIS student.
            $material = $this->db('ca_library_study_materials')
                ->where('id', $materialId)
                ->where('is_active', 1)
                ->first();
            if (! $material) {
                return response()->json(['status' => false, 'message' => 'Resource not found'], 404);
            }

            $req = $this->db('ca_download_requests')
                ->where('student_id', $student->id)
                ->where('study_material_id', $materialId)
                ->where('status', 'ready')
                ->first();
            if (! $req) {
                return response()->json(['status' => false, 'message' => 'No download access for this resource.'], 403);
            }

            $rel = ltrim((string) $material->file_path, '/');
            if (str_contains($rel, '..') || str_contains($rel, "\0") || ! Storage::disk('public')->exists($rel)) {
                return response()->json(['status' => false, 'message' => 'File missing'], 404);
            }

            $this->db('ca_download_requests')->where('id', $req->id)->update([
                'downloaded_at' => now(),
                'updated_at'    => now(),
            ]);

            $safeName = preg_replace('/[^\w\s.\-()\[\]]+/u', '_', (string) $material->original_file_name) ?: 'download.pdf';
            return response()->download(Storage::disk('public')->path($rel), $safeName);
        } catch (\Exception $e) {
            Log::error('CaStudent download error', ['msg' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AUTH (ca.student) — Optional password (for future direct login)
    // ─────────────────────────────────────────────────────────────────────────

    public function setPassword(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'password' => 'required|string|min:8|max:100',
            ]);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => 'Password must be at least 8 characters.', 'errors' => $validator->errors()], 422);
            }

            $student = $request->attributes->get('ca_student');
            $this->db('ca_students')->where('id', $student->id)->update([
                'password'   => Hash::make($request->input('password')),
                'updated_at' => now(),
            ]);

            return response()->json(['status' => true, 'message' => 'Password set. You can now log in with your email and password.']);
        } catch (\Exception $e) {
            Log::error('CaStudent setPassword error', ['msg' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AUTH (ca.student) — Logout (CA Library session only; SYS untouched)
    // ─────────────────────────────────────────────────────────────────────────

    public function logout(Request $request): JsonResponse
    {
        try {
            $token = $request->cookie('ca_auth_token');
            $this->db('ca_sessions')->where('token', $token)->delete();
            return response()->json(['status' => true, 'message' => 'Logged out.'])
                ->cookie('ca_auth_token', '', -1);
        } catch (\Exception $e) {
            return response()->json(['status' => false]);
        }
    }
}
