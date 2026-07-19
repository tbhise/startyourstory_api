<?php

namespace App\Http\Controllers\API\CA_Library;

use App\Http\Controllers\Controller;
use App\Mail\CaLibraryEvaluatedMail;
use App\Services\Payment\PhonePeGateway;
use App\Services\SystemSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * CA Library answer-sheet evaluation: upload → snapshot fee → PhonePe payment
 * → admin/faculty evaluation → evaluated paper → email → My Library download.
 *
 * Business data lives in the separate ca_library DB (ca_test_submissions +
 * ca_payments, owned by ca_students.id — never SYS user ids). Only the generic
 * PhonePeGateway / SystemSettingService / Mail infrastructure is reused.
 * Answer sheets + evaluated papers live on the PRIVATE `local` disk.
 */
class CaTestSubmissionController extends Controller
{
    private const DISK = 'local';

    private function db(string $table)
    {
        return DB::connection('ca_library')->table($table);
    }

    /** Current evaluation fee (₹, integer) from Platform Settings — main SYS DB. */
    private function currentFee(): int
    {
        return max(1, (int) SystemSettingService::get('ca_library_evaluation_fee', 99));
    }

    private function serializeSubmission(object $s, ?object $material = null): array
    {
        return [
            'id'                => $s->id,
            'material_id'       => $s->study_material_id,
            'title'             => $material->title ?? ($s->material_title ?? null),
            'subject'           => $s->subject_name ?? null,
            'resource_type'     => $s->resource_type_name ?? null,
            'exam_attempt'      => $material->exam_attempt ?? ($s->material_exam_attempt ?? null),
            'amount'            => (float) $s->amount, // snapshotted fee — never recalculated
            'currency'          => $s->currency,
            'payment_status'    => $s->payment_status,
            'evaluation_status' => $s->evaluation_status,
            'answer_sheet_name' => $s->answer_sheet_original_name,
            'has_evaluated_file' => (bool) $s->evaluated_file_path,
            // Latest manual-payment attempt state (null when none exists).
            'manual_payment_status' => $s->manual_payment_status ?? null,
            'submitted_at'      => $s->submitted_at,
            'paid_at'           => $s->paid_at,
            'completed_at'      => $s->completed_at,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STUDENT (ca.student) — Create submission (upload answer sheet)
    // ─────────────────────────────────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        try {
            $student = $request->attributes->get('ca_student');

            $validator = Validator::make($request->all(), [
                'material_id'     => 'required|integer',
                // Answer sheet limit is intentionally 10 MB — separate from the
                // 50 MB study-material/evaluated-paper limits elsewhere.
                'file'            => 'nullable|file|mimes:pdf|max:10240',
                'replace'         => 'nullable|boolean',
                // Must be explicitly true — Laravel's `accepted` rule rejects
                // false/0/null/missing, so a checkbox left unchecked cannot pass.
                'terms_accepted'  => 'required|accepted',
            ], [
                'file.max'             => 'Answer sheet must be 10 MB or smaller.',
                'terms_accepted.required' => 'Please accept the Terms & Conditions to continue.',
                'terms_accepted.accepted' => 'Please accept the Terms & Conditions to continue.',
            ]);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
            }

            // Backend re-validates what the frontend button only implies:
            // active material whose resource type is flagged as a question paper.
            $material = $this->db('ca_library_study_materials as m')
                ->join('ca_library_resource_types as t', 'm.resource_type_id', '=', 't.id')
                ->where('m.id', (int) $request->input('material_id'))
                ->where('m.is_active', 1)
                ->select('m.*', 't.is_question_paper')
                ->first();
            if (! $material) {
                return response()->json(['status' => false, 'message' => 'Resource not found'], 404);
            }
            if (! $material->is_question_paper) {
                return response()->json(['status' => false, 'message' => 'Answer sheets can only be uploaded for question papers.'], 422);
            }

            // Duplicate handling: one active unpaid submission per student+material.
            $existing = $this->db('ca_test_submissions')
                ->where('student_id', $student->id)
                ->where('study_material_id', $material->id)
                ->where('payment_status', 'pending')
                ->whereNotIn('evaluation_status', ['cancelled'])
                ->first();

            if ($existing && ! $request->boolean('replace')) {
                return response()->json([
                    'status' => true,
                    'data'   => [
                        'existing'   => true,
                        'submission' => $this->serializeSubmission($existing, $material),
                    ],
                ]);
            }

            if (! $request->hasFile('file')) {
                return response()->json(['status' => false, 'message' => 'Please choose your answer sheet PDF.', 'errors' => ['file' => ['Answer sheet file is required.']]], 422);
            }

            $file = $request->file('file');
            $path = $file->store('ca-library/answer-sheets', self::DISK);

            if ($existing) {
                // Replace the answer sheet on the SAME unpaid submission — the
                // snapshotted fee stays; no duplicate submission is created.
                if ($existing->answer_sheet_path) {
                    Storage::disk(self::DISK)->delete($existing->answer_sheet_path);
                }
                $this->db('ca_test_submissions')->where('id', $existing->id)->update([
                    'answer_sheet_path'          => $path,
                    'answer_sheet_original_name' => $file->getClientOriginalName(),
                    'answer_sheet_file_size'     => $file->getSize(),
                    'submitted_at'               => now(),
                    'terms_accepted_at'          => now(),
                    'updated_at'                 => now(),
                ]);
                $submission = $this->db('ca_test_submissions')->where('id', $existing->id)->first();
            } else {
                // PRICE SNAPSHOT: the backend reads the CURRENT Platform Setting
                // here — the frontend-displayed price is never trusted.
                $id = $this->db('ca_test_submissions')->insertGetId([
                    'student_id'                 => $student->id,
                    'study_material_id'          => $material->id,
                    'answer_sheet_path'          => $path,
                    'answer_sheet_original_name' => $file->getClientOriginalName(),
                    'answer_sheet_file_size'     => $file->getSize(),
                    'amount'                     => $this->currentFee(),
                    'currency'                   => 'INR',
                    'payment_status'             => 'pending',
                    'evaluation_status'          => 'pending_payment',
                    'submitted_at'               => now(),
                    'terms_accepted_at'          => now(),
                    'created_at'                 => now(),
                    'updated_at'                 => now(),
                ]);
                $submission = $this->db('ca_test_submissions')->where('id', $id)->first();
            }

            return response()->json([
                'status'  => true,
                'message' => 'Answer sheet uploaded.',
                'data'    => ['existing' => false, 'submission' => $this->serializeSubmission($submission, $material)],
            ]);
        } catch (\Exception $e) {
            Log::error('CaTestSubmission store error', ['msg' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STUDENT (ca.student) — My Test Papers
    // ─────────────────────────────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        try {
            $student = $request->attributes->get('ca_student');

            $rows = $this->db('ca_test_submissions as ts')
                ->join('ca_library_study_materials as m', 'ts.study_material_id', '=', 'm.id')
                ->join('ca_library_subjects as s', 'm.subject_id', '=', 's.id')
                ->join('ca_library_resource_types as t', 'm.resource_type_id', '=', 't.id')
                ->where('ts.student_id', $student->id)
                ->orderByDesc('ts.created_at')
                ->select(
                    'ts.*',
                    'm.title as material_title',
                    'm.exam_attempt as material_exam_attempt',
                    's.name as subject_name',
                    't.name as resource_type_name'
                )
                ->get();

            // Latest manual attempt per submission → shown as the payment state
            // ("verification pending" / "rejected") in My Library.
            $manualStatuses = $this->db('ca_payments')
                ->whereIn('test_submission_id', $rows->pluck('id'))
                ->where('gateway', 'manual')
                ->orderBy('id')
                ->pluck('status', 'test_submission_id');
            $rows->each(function ($r) use ($manualStatuses) {
                $r->manual_payment_status = $manualStatuses[$r->id] ?? null;
            });

            return response()->json([
                'status' => true,
                'data'   => ['items' => $rows->map(fn ($r) => $this->serializeSubmission($r))],
            ]);
        } catch (\Exception $e) {
            Log::error('CaTestSubmission index error', ['msg' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STUDENT (ca.student) — Initiate payment (new ca_payments attempt)
    // ─────────────────────────────────────────────────────────────────────────

    public function pay(Request $request, int $id): JsonResponse
    {
        try {
            $student = $request->attributes->get('ca_student');

            $submission = $this->db('ca_test_submissions')
                ->where('id', $id)
                ->where('student_id', $student->id)
                ->first();
            if (! $submission) {
                return response()->json(['status' => false, 'message' => 'Submission not found'], 404);
            }
            if ($submission->payment_status === 'paid') {
                return response()->json(['status' => false, 'message' => 'This submission is already paid.'], 422);
            }

            // A manual payment under admin review blocks a parallel online attempt.
            $manualPending = $this->db('ca_payments')
                ->where('test_submission_id', $submission->id)
                ->where('gateway', 'manual')
                ->where('status', 'pending_verification')
                ->exists();
            if ($manualPending) {
                return response()->json(['status' => false, 'message' => 'Your manual payment is under verification. Please wait for admin review.'], 422);
            }

            // Reuse the SAME existing online-payments toggle used by Firm/Wallet
            // (platform_settings.online_payments_enabled, main SYS DB — NOT a new
            // CA Library setting). Same default-true semantics as AdminSettingsController.
            $onlinePay = DB::table('platform_settings')->where('key', 'online_payments_enabled')->value('value');
            $onlinePaymentsEnabled = ($onlinePay === null) ? true : ($onlinePay === 'true' || $onlinePay === '1');
            if (! $onlinePaymentsEnabled) {
                return response()->json(['status' => false, 'message' => 'Online payment is currently disabled. Please use Manual Payment.'], 422);
            }

            // New attempt row per try — failed attempts are history, never overwritten.
            $paymentId = $this->db('ca_payments')->insertGetId([
                'student_id'         => $student->id,
                'test_submission_id' => $submission->id,
                'amount'             => $submission->amount, // snapshot, not current setting
                'currency'           => $submission->currency,
                'gateway'            => 'phonepe',
                'status'             => 'pending',
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);

            // merchantOrderId: unique, alphanumeric, max 38 chars.
            $merchantTxnId = 'CAP' . $paymentId . 'T' . time();

            $frontendUrl = rtrim((string) config('app.frontend_url'), '/');
            $gateway     = new PhonePeGateway();
            $order       = $gateway->createOrder((float) $submission->amount, $merchantTxnId, [
                'redirect_url' => $frontendUrl . '/ca-library/my-library?phonepe_txn=' . $merchantTxnId,
                'callback_url' => url('/api/ca-library/payments/phonepe/webhook'),
            ]);

            $this->db('ca_payments')->where('id', $paymentId)->update([
                'gateway_order_id' => $merchantTxnId,
                'updated_at'       => now(),
            ]);

            return response()->json([
                'status' => true,
                'data'   => [
                    'redirect_url'   => $order['redirect_url'],
                    'transaction_id' => $merchantTxnId,
                    'amount'         => (float) $submission->amount,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('CaTestSubmission pay error', ['msg' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Failed to initiate payment'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Shared verified-payment processing (verify endpoint + webhook)
    // Returns 'paid' | 'already' | 'failed'. Row-locked so a racing webhook and
    // verify call can never double-process.
    // ─────────────────────────────────────────────────────────────────────────

    private function settlePayment(object $payment, array $statusData): string
    {
        $isSuccess        = ($statusData['state'] ?? '') === 'COMPLETED';
        $gatewayPaymentId = $statusData['paymentDetails'][0]['transactionId'] ?? null;

        // Amount verification in integer paise — the gateway-confirmed amount
        // must equal the snapshotted attempt amount.
        $expectedPaise = (int) round(((float) $payment->amount) * 100);
        $actualPaise   = isset($statusData['amount']) ? (int) $statusData['amount'] : null;
        if ($isSuccess && $actualPaise !== null && $actualPaise !== $expectedPaise) {
            Log::warning('CaTestSubmission payment amount mismatch', [
                'payment_id' => $payment->id, 'expected' => $expectedPaise, 'actual' => $actualPaise,
            ]);
            $isSuccess = false;
        }

        return DB::connection('ca_library')->transaction(function () use ($payment, $isSuccess, $gatewayPaymentId) {
            $fresh = $this->db('ca_payments')->where('id', $payment->id)->lockForUpdate()->first();
            if ($fresh->status === 'paid') {
                return 'already';
            }

            if ($isSuccess) {
                $this->db('ca_payments')->where('id', $fresh->id)->update([
                    'status'                 => 'paid',
                    'gateway_transaction_id' => $gatewayPaymentId,
                    'paid_at'                => now(),
                    'updated_at'             => now(),
                ]);
                $this->db('ca_test_submissions')
                    ->where('id', $fresh->test_submission_id)
                    ->where('payment_status', '!=', 'paid')
                    ->update([
                        'payment_status'    => 'paid',
                        'evaluation_status' => 'awaiting_evaluation',
                        'paid_at'           => now(),
                        'updated_at'        => now(),
                    ]);
                return 'paid';
            }

            $this->db('ca_payments')->where('id', $fresh->id)->update([
                'status'     => 'failed',
                'updated_at' => now(),
            ]);
            return 'failed';
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STUDENT (ca.student) — Verify payment after redirect back from PhonePe
    // ─────────────────────────────────────────────────────────────────────────

    public function verifyPayment(Request $request): JsonResponse
    {
        try {
            $student = $request->attributes->get('ca_student');

            $validator = Validator::make($request->all(), [
                'transaction_id' => 'required|string|max:100',
            ]);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => 'Invalid transaction.'], 422);
            }

            $payment = $this->db('ca_payments')
                ->where('gateway_order_id', $request->input('transaction_id'))
                ->where('student_id', $student->id)
                ->first();
            if (! $payment) {
                return response()->json(['status' => false, 'message' => 'Transaction not found'], 404);
            }

            if ($payment->status === 'paid') {
                return response()->json(['status' => true, 'message' => 'Payment already processed.']);
            }

            // Server-side truth: query PhonePe's status API, never the client.
            $statusData = (new PhonePeGateway())->fetchPayment($payment->gateway_order_id);
            $outcome    = $this->settlePayment($payment, $statusData);

            if ($outcome === 'paid' || $outcome === 'already') {
                return response()->json(['status' => true, 'message' => 'Payment successful. Your answer sheet is awaiting evaluation.']);
            }
            return response()->json(['status' => false, 'message' => 'Payment was not successful. You can retry from My Library.']);
        } catch (\Exception $e) {
            Log::error('CaTestSubmission verifyPayment error', ['msg' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Payment verification failed'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PUBLIC — PhonePe S2S webhook (signature-verified, fail closed)
    // ─────────────────────────────────────────────────────────────────────────

    public function webhook(Request $request): JsonResponse
    {
        try {
            $gateway = new PhonePeGateway();
            if (! $gateway->verifySignature(['authorization' => $request->header('Authorization')])) {
                Log::warning('CaLibrary PhonePe webhook: signature verification failed');
                return response()->json(['status' => false], 401);
            }

            $payload       = $request->json()->all()['payload'] ?? [];
            $merchantTxnId = $payload['merchantOrderId'] ?? null;
            if (! $merchantTxnId) {
                return response()->json(['status' => false], 422);
            }

            $payment = $this->db('ca_payments')->where('gateway_order_id', $merchantTxnId)->first();
            if (! $payment) {
                // Not a CA Library order (e.g. wallet txn) — acknowledge and ignore.
                return response()->json(['status' => true]);
            }

            $this->settlePayment($payment, $payload);
            return response()->json(['status' => true]);
        } catch (\Exception $e) {
            Log::error('CaTestSubmission webhook error', ['msg' => $e->getMessage()]);
            return response()->json(['status' => false], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STUDENT (ca.student) — Download evaluated paper (own + completed only)
    // ─────────────────────────────────────────────────────────────────────────

    public function downloadEvaluated(Request $request, int $id)
    {
        try {
            $student = $request->attributes->get('ca_student');

            $submission = $this->db('ca_test_submissions')
                ->where('id', $id)
                ->where('student_id', $student->id)
                ->where('evaluation_status', 'completed')
                ->first();
            if (! $submission || ! $submission->evaluated_file_path) {
                return response()->json(['status' => false, 'message' => 'Evaluated paper not available.'], 404);
            }

            return $this->streamPrivate($submission->evaluated_file_path, $submission->evaluated_file_original_name ?: 'evaluated.pdf');
        } catch (\Exception $e) {
            Log::error('CaTestSubmission downloadEvaluated error', ['msg' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    private function streamPrivate(string $rel, string $name)
    {
        $rel = ltrim($rel, '/');
        if (str_contains($rel, '..') || str_contains($rel, "\0") || ! Storage::disk(self::DISK)->exists($rel)) {
            return response()->json(['status' => false, 'message' => 'File missing'], 404);
        }
        $safeName = preg_replace('/[^\w\s.\-()\[\]]+/u', '_', $name) ?: 'download.pdf';
        return response()->download(Storage::disk(self::DISK)->path($rel), $safeName);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ADMIN — Evaluation queue (AdminAuthMiddleware guards /admin/*)
    // Unpaid submissions can never appear: payment_status = paid is enforced here.
    // ─────────────────────────────────────────────────────────────────────────

    public function adminIndex(Request $request): JsonResponse
    {
        try {
            $status = $request->query('status', 'awaiting_evaluation');
            if (! in_array($status, ['awaiting_evaluation', 'under_evaluation', 'completed'], true)) {
                return response()->json(['status' => false, 'message' => 'Invalid status'], 422);
            }

            $rows = $this->db('ca_test_submissions as ts')
                ->join('ca_students as st', 'ts.student_id', '=', 'st.id')
                ->join('ca_library_study_materials as m', 'ts.study_material_id', '=', 'm.id')
                ->join('ca_library_subjects as s', 'm.subject_id', '=', 's.id')
                ->where('ts.payment_status', 'paid')
                ->where('ts.evaluation_status', $status)
                ->orderBy('ts.paid_at')
                ->select(
                    'ts.*',
                    'st.email as student_email',
                    'm.title as material_title',
                    'm.exam_attempt as material_exam_attempt',
                    's.name as subject_name'
                )
                ->limit(200)
                ->get();

            $items = $rows->map(fn ($r) => [
                'id'                => $r->id,
                'student_email'     => $r->student_email,
                'title'             => $r->material_title,
                'subject'           => $r->subject_name,
                'exam_attempt'      => $r->material_exam_attempt,
                'amount'            => (float) $r->amount,
                'payment_status'    => $r->payment_status,
                'evaluation_status' => $r->evaluation_status,
                'answer_sheet_name' => $r->answer_sheet_original_name,
                'submitted_at'      => $r->submitted_at,
                'paid_at'           => $r->paid_at,
                'completed_at'      => $r->completed_at,
            ]);

            return response()->json(['status' => true, 'data' => ['items' => $items]]);
        } catch (\Exception $e) {
            Log::error('CaTestSubmission adminIndex error', ['msg' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    public function adminDownloadAnswerSheet(Request $request, int $id)
    {
        try {
            $submission = $this->db('ca_test_submissions')
                ->where('id', $id)
                ->where('payment_status', 'paid')
                ->first();
            if (! $submission) {
                return response()->json(['status' => false, 'message' => 'Submission not found'], 404);
            }
            return $this->streamPrivate($submission->answer_sheet_path, $submission->answer_sheet_original_name ?: 'answer-sheet.pdf');
        } catch (\Exception $e) {
            Log::error('CaTestSubmission adminDownloadAnswerSheet error', ['msg' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    public function adminStart(Request $request, int $id): JsonResponse
    {
        try {
            $admin = $request->attributes->get('admin_user');

            $updated = $this->db('ca_test_submissions')
                ->where('id', $id)
                ->where('payment_status', 'paid')
                ->where('evaluation_status', 'awaiting_evaluation')
                ->update([
                    'evaluation_status'     => 'under_evaluation',
                    'evaluation_started_at' => now(),
                    'faculty_id'            => $admin?->id,
                    'updated_at'            => now(),
                ]);

            if (! $updated) {
                return response()->json(['status' => false, 'message' => 'Submission is not awaiting evaluation.'], 422);
            }
            return response()->json(['status' => true, 'message' => 'Evaluation started.']);
        } catch (\Exception $e) {
            Log::error('CaTestSubmission adminStart error', ['msg' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STUDENT (ca.student) — Manual payment: screenshot → admin verification.
    // Identity/email comes ONLY from the authenticated session; amount is the
    // submission's snapshot. Never marks anything paid by itself.
    // ─────────────────────────────────────────────────────────────────────────

    public function submitManualPayment(Request $request, int $id): JsonResponse
    {
        try {
            $student = $request->attributes->get('ca_student');

            $validator = Validator::make($request->all(), [
                'screenshot' => 'required|image|mimes:jpg,jpeg,png|max:5120',
            ]);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => 'Please upload a JPG/PNG screenshot (max 5MB).', 'errors' => $validator->errors()], 422);
            }

            $submission = $this->db('ca_test_submissions')
                ->where('id', $id)
                ->where('student_id', $student->id)
                ->first();
            if (! $submission) {
                return response()->json(['status' => false, 'message' => 'Submission not found'], 404);
            }
            if ($submission->payment_status === 'paid') {
                return response()->json(['status' => false, 'message' => 'This submission is already paid.'], 422);
            }

            $file = $request->file('screenshot');
            $path = $file->store('ca-library/payment-screenshots', self::DISK);

            // Re-submitting replaces the screenshot on the still-pending attempt
            // instead of stacking duplicate verification requests.
            $pending = $this->db('ca_payments')
                ->where('test_submission_id', $submission->id)
                ->where('gateway', 'manual')
                ->where('status', 'pending_verification')
                ->first();

            if ($pending) {
                if ($pending->screenshot_path) {
                    Storage::disk(self::DISK)->delete($pending->screenshot_path);
                }
                $this->db('ca_payments')->where('id', $pending->id)->update([
                    'screenshot_path'          => $path,
                    'screenshot_original_name' => $file->getClientOriginalName(),
                    'updated_at'               => now(),
                ]);
            } else {
                $this->db('ca_payments')->insert([
                    'student_id'               => $student->id,
                    'test_submission_id'       => $submission->id,
                    'amount'                   => $submission->amount, // snapshot, not current setting
                    'currency'                 => $submission->currency,
                    'gateway'                  => 'manual',
                    'status'                   => 'pending_verification',
                    'screenshot_path'          => $path,
                    'screenshot_original_name' => $file->getClientOriginalName(),
                    'created_at'               => now(),
                    'updated_at'               => now(),
                ]);
            }

            return response()->json([
                'status'  => true,
                'message' => 'Payment submitted for verification. We will confirm it shortly.',
            ]);
        } catch (\Exception $e) {
            Log::error('CaTestSubmission submitManualPayment error', ['msg' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ADMIN — Manual payment verification queue
    // ─────────────────────────────────────────────────────────────────────────

    public function adminManualPayments(Request $request): JsonResponse
    {
        try {
            $status = $request->query('status', 'pending_verification');
            if (! in_array($status, ['pending_verification', 'paid', 'rejected'], true)) {
                return response()->json(['status' => false, 'message' => 'Invalid status'], 422);
            }

            $rows = $this->db('ca_payments as p')
                ->join('ca_test_submissions as ts', 'p.test_submission_id', '=', 'ts.id')
                ->join('ca_students as st', 'p.student_id', '=', 'st.id')
                ->join('ca_library_study_materials as m', 'ts.study_material_id', '=', 'm.id')
                ->join('ca_library_subjects as s', 'm.subject_id', '=', 's.id')
                ->where('p.gateway', 'manual')
                ->where('p.status', $status)
                ->orderBy('p.created_at')
                ->select(
                    'p.id', 'p.amount', 'p.status', 'p.screenshot_original_name',
                    'p.created_at', 'p.reviewed_at',
                    'st.email as student_email',
                    'ts.id as submission_id', 'ts.payment_status as submission_payment_status',
                    'm.title as material_title', 'm.exam_attempt as material_exam_attempt',
                    's.name as subject_name'
                )
                ->limit(200)
                ->get();

            $items = $rows->map(fn ($r) => [
                'id'            => $r->id,
                'submission_id' => $r->submission_id,
                'student_email' => $r->student_email,
                'title'         => $r->material_title,
                'subject'       => $r->subject_name,
                'exam_attempt'  => $r->material_exam_attempt,
                'amount'        => (float) $r->amount,
                'status'        => $r->status,
                'screenshot_name' => $r->screenshot_original_name,
                'submitted_at'  => $r->created_at,
                'reviewed_at'   => $r->reviewed_at,
            ]);

            return response()->json(['status' => true, 'data' => ['items' => $items]]);
        } catch (\Exception $e) {
            Log::error('CaTestSubmission adminManualPayments error', ['msg' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    public function adminManualPaymentScreenshot(Request $request, int $id)
    {
        try {
            $payment = $this->db('ca_payments')->where('id', $id)->where('gateway', 'manual')->first();
            if (! $payment || ! $payment->screenshot_path) {
                return response()->json(['status' => false, 'message' => 'Screenshot not found'], 404);
            }
            $rel = ltrim($payment->screenshot_path, '/');
            if (str_contains($rel, '..') || str_contains($rel, "\0") || ! Storage::disk(self::DISK)->exists($rel)) {
                return response()->json(['status' => false, 'message' => 'File missing'], 404);
            }
            // Inline so admins can view it in the browser tab.
            return response()->file(Storage::disk(self::DISK)->path($rel));
        } catch (\Exception $e) {
            Log::error('CaTestSubmission adminManualPaymentScreenshot error', ['msg' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /** Approve or reject a pending manual payment (row-locked; approve = paid). */
    public function adminReviewManualPayment(Request $request, int $id): JsonResponse
    {
        try {
            $admin = $request->attributes->get('admin_user');

            $validator = Validator::make($request->all(), [
                'action' => 'required|in:approve,reject',
            ]);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => 'Invalid action'], 422);
            }
            $approve = $request->input('action') === 'approve';

            $outcome = DB::connection('ca_library')->transaction(function () use ($id, $approve, $admin) {
                $payment = $this->db('ca_payments')
                    ->where('id', $id)
                    ->where('gateway', 'manual')
                    ->lockForUpdate()
                    ->first();
                if (! $payment || $payment->status !== 'pending_verification') {
                    return 'not_pending';
                }

                if (! $approve) {
                    $this->db('ca_payments')->where('id', $payment->id)->update([
                        'status'      => 'rejected',
                        'reviewed_by' => $admin?->id,
                        'reviewed_at' => now(),
                        'updated_at'  => now(),
                    ]);
                    return 'rejected';
                }

                $submission = $this->db('ca_test_submissions')
                    ->where('id', $payment->test_submission_id)
                    ->lockForUpdate()
                    ->first();
                if (! $submission || $submission->payment_status === 'paid') {
                    // Already paid (e.g. via PhonePe) — don't double-approve.
                    return 'already_paid';
                }

                $this->db('ca_payments')->where('id', $payment->id)->update([
                    'status'      => 'paid',
                    'paid_at'     => now(),
                    'reviewed_by' => $admin?->id,
                    'reviewed_at' => now(),
                    'updated_at'  => now(),
                ]);
                $this->db('ca_test_submissions')->where('id', $submission->id)->update([
                    'payment_status'    => 'paid',
                    'evaluation_status' => 'awaiting_evaluation',
                    'paid_at'           => now(),
                    'updated_at'        => now(),
                ]);
                return 'approved';
            });

            return match ($outcome) {
                'approved'     => response()->json(['status' => true, 'message' => 'Payment approved — submission is awaiting evaluation.']),
                'rejected'     => response()->json(['status' => true, 'message' => 'Payment rejected.']),
                'already_paid' => response()->json(['status' => false, 'message' => 'This submission is already paid.'], 422),
                default        => response()->json(['status' => false, 'message' => 'Payment is not pending verification.'], 422),
            };
        } catch (\Exception $e) {
            Log::error('CaTestSubmission adminReviewManualPayment error', ['msg' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /** Upload evaluated paper + complete in one atomic action (file required). */
    public function adminComplete(Request $request, int $id): JsonResponse
    {
        try {
            $admin = $request->attributes->get('admin_user');

            $validator = Validator::make($request->all(), [
                'file' => 'required|file|mimes:pdf|max:51200',
            ]);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => 'Evaluated PDF is required.', 'errors' => $validator->errors()], 422);
            }

            $submission = $this->db('ca_test_submissions')
                ->where('id', $id)
                ->where('payment_status', 'paid')
                ->whereIn('evaluation_status', ['awaiting_evaluation', 'under_evaluation'])
                ->first();
            if (! $submission) {
                return response()->json(['status' => false, 'message' => 'Submission not found or already completed.'], 404);
            }

            $file = $request->file('file');
            $path = $file->store('ca-library/evaluated', self::DISK);
            if ($submission->evaluated_file_path) {
                Storage::disk(self::DISK)->delete($submission->evaluated_file_path);
            }

            $this->db('ca_test_submissions')->where('id', $submission->id)->update([
                'evaluated_file_path'          => $path,
                'evaluated_file_original_name' => $file->getClientOriginalName(),
                'evaluation_status'            => 'completed',
                'completed_at'                 => now(),
                'faculty_id'                   => $submission->faculty_id ?? $admin?->id,
                'updated_at'                   => now(),
            ]);

            // Notify the student — link to My Library only, never the PDF itself.
            $studentEmail = $this->db('ca_students')->where('id', $submission->student_id)->value('email');
            $materialTitle = $this->db('ca_library_study_materials')->where('id', $submission->study_material_id)->value('title');
            if ($studentEmail) {
                $myLibraryUrl = rtrim((string) config('app.frontend_url'), '/') . '/ca-library/my-library';
                Mail::to($studentEmail)->queue(new CaLibraryEvaluatedMail((string) $materialTitle, $myLibraryUrl));
            }

            return response()->json(['status' => true, 'message' => 'Evaluated paper uploaded and submission completed.']);
        } catch (\Exception $e) {
            Log::error('CaTestSubmission adminComplete error', ['msg' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }
}
