<?php

namespace App\Http\Controllers\API;

use App\Helpers\NotificationHelper;
use App\Helpers\SupportTicketHelper;
use App\Http\Controllers\Controller;
use App\Services\Notifications\AdminNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * User-facing support tickets (students & firms).
 *
 * Every endpoint runs behind ApiAuthMiddleware, so $request->attributes->get('auth_user')
 * is always present. Ownership is enforced on every per-ticket action — a user may only
 * touch tickets where user_id === their own id.
 */
class SupportTicketController extends Controller
{
    /** GET /support-tickets — the current user's own tickets, newest first. */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->attributes->get('auth_user');

            $userType = $user->role === 'firm' ? 'firm' : 'student';

            $perPage = (int) $request->query('per_page', 15);
            $perPage = max(1, min($perPage, 50));

            $paginator = DB::table('support_tickets')
                ->where('user_id', $user->id)
                ->where('user_type', $userType)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->paginate($perPage);

            $tickets = collect($paginator->items())->map(function ($t) {
                return [
                    'id'              => (int) $t->id,
                    'ticket_no'       => $t->ticket_no,
                    'ticket_category' => $t->ticket_category,
                    'status'          => $t->status,
                    'status_label'    => SupportTicketHelper::statusLabel($t->status),
                    'created_at'      => $t->created_at,
                    'updated_at'      => $t->updated_at,
                ];
            })->all();

            return response()->json([
                'status'  => true,
                'message' => 'Tickets fetched.',
                'data'    => $tickets,
                'pagination' => [
                    'total'        => $paginator->total(),
                    'per_page'     => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'last_page'    => $paginator->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('SupportTicket@index: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /** POST /support-tickets — create a ticket (status = submitted). */
    public function create(Request $request): JsonResponse
    {
        try {
            $user = $request->attributes->get('auth_user');

            // role is the enum 'student'|'firm'|'admin'; only student/firm reach here.
            $userType = $user->role === 'firm' ? 'firm' : 'student';

            $validator = Validator::make($request->all(), [
                'ticket_category' => ['required', 'string', Rule::in(SupportTicketHelper::CATEGORIES)],
                'issue_brief'     => ['required', 'string', 'max:1000'],
                'attachments'     => ['nullable', 'array', 'max:3'],
                'attachments.*'   => ['file', 'mimes:jpg,jpeg,png,pdf,txt', 'max:5120'],
            ], [
                'ticket_category.in' => 'Please select a valid ticket category.',
                'issue_brief.max'    => 'Issue brief must be 1000 characters or less.',
                'attachments.max'    => 'You can upload at most 3 files.',
                'attachments.*.mimes'=> 'Allowed file types: jpg, jpeg, png, pdf, txt.',
                'attachments.*.max'  => 'Each file must be 5 MB or smaller.',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
            }

            DB::beginTransaction();

            $now = now();
            $ticketId = DB::table('support_tickets')->insertGetId([
                'user_id'         => $user->id,
                'user_type'       => $userType,
                'ticket_category' => $request->input('ticket_category'),
                'issue_brief'     => trim($request->input('issue_brief')),
                'status'          => 'submitted',
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);

            $ticketNo = SupportTicketHelper::ticketNo($ticketId);

            // Store attachments (preserve original format — support evidence is not transcoded).
            $stored = [];
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->storeAs(
                        'support-tickets/' . $ticketId,
                        'att_' . time() . '_' . Str::random(6) . '.' . $file->getClientOriginalExtension(),
                        'public'
                    );
                    $stored[] = [
                        'path' => $path,
                        'url'  => SupportTicketHelper::fileUrl($path),
                        'name' => $file->getClientOriginalName(),
                        'mime' => $file->getClientMimeType(),
                        'size' => $file->getSize(),
                    ];
                }
            }

            DB::table('support_tickets')->where('id', $ticketId)->update([
                'ticket_no'   => $ticketNo,
                'attachments' => $stored ? json_encode($stored) : null,
                'updated_at'  => $now,
            ]);

            // Seed the conversation thread with the user's opening message.
            DB::table('support_ticket_messages')->insert([
                'ticket_id'   => $ticketId,
                'sender_type' => $userType,
                'sender_id'   => $user->id,
                'message'     => trim($request->input('issue_brief')),
                'created_at'  => $now,
            ]);

            DB::commit();

            // In-app notification to the user (ticket created).
            NotificationHelper::create(
                $user->id,
                'Support ticket created',
                "Your support ticket {$ticketNo} has been submitted. We'll get back to you soon."
            );

            // Admin notification (+ FCM push to admin devices) — new ticket.
            AdminNotificationService::create(
                AdminNotificationService::TYPE_SUPPORT_TICKET,
                'New support ticket',
                ($user->name ?? 'A user') . " ({$userType}) raised ticket {$ticketNo}: {$request->input('ticket_category')}",
                '/admin/support-tickets?ticket=' . $ticketId,
                [
                    'ticket_id'  => $ticketId,
                    'ticket_no'  => $ticketNo,
                    'user_type'  => $userType,
                    'user_id'    => $user->id,
                    'category'   => $request->input('ticket_category'),
                ]
            );

            return response()->json([
                'status'  => true,
                'message' => 'Support ticket created successfully.',
                'data'    => ['id' => $ticketId, 'ticket_no' => $ticketNo],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SupportTicket@create: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /** GET /support-tickets/{id} — ticket detail + full thread (ownership enforced). */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->attributes->get('auth_user');
            $userType = $user->role === 'firm' ? 'firm' : 'student';

            $ticket = DB::table('support_tickets')->where('id', (int) $id)->first();
            if (!$ticket) {
                return response()->json(['status' => false, 'message' => 'Ticket not found'], 404);
            }
            // Ownership: the ticket must belong to this exact user AND user type
            // (a student can never reach a firm ticket and vice-versa).
            if ((int) $ticket->user_id !== (int) $user->id || $ticket->user_type !== $userType) {
                return response()->json(['status' => false, 'message' => 'You do not have access to this ticket'], 403);
            }

            return response()->json([
                'status'  => true,
                'message' => 'Ticket fetched.',
                'data'    => [
                    'ticket'   => $this->serializeTicket($ticket),
                    'messages' => $this->serializeMessages((int) $id),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('SupportTicket@show: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /** POST /support-tickets/{id}/messages — add a user reply (ownership enforced). */
    public function addMessage(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->attributes->get('auth_user');
            $userType = $user->role === 'firm' ? 'firm' : 'student';

            $ticket = DB::table('support_tickets')->where('id', (int) $id)->first();
            if (!$ticket) {
                return response()->json(['status' => false, 'message' => 'Ticket not found'], 404);
            }
            if ((int) $ticket->user_id !== (int) $user->id || $ticket->user_type !== $userType) {
                return response()->json(['status' => false, 'message' => 'You do not have access to this ticket'], 403);
            }
            if ($ticket->status === 'closed') {
                return response()->json(['status' => false, 'message' => 'This ticket is closed. Please create a new ticket.'], 422);
            }

            $validator = Validator::make($request->all(), [
                'message'    => ['required_without:attachment', 'nullable', 'string', 'max:2000'],
                'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,txt', 'max:5120'],
            ], [
                'message.required_without' => 'Please type a message or attach a file.',
                'attachment.mimes'         => 'Allowed file types: jpg, jpeg, png, pdf, txt.',
                'attachment.max'           => 'File must be 5 MB or smaller.',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
            }

            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $attachmentPath = $file->storeAs(
                    'support-tickets/' . $ticket->id,
                    'msg_' . time() . '_' . Str::random(6) . '.' . $file->getClientOriginalExtension(),
                    'public'
                );
            }

            $now = now();
            $msgId = DB::table('support_ticket_messages')->insertGetId([
                'ticket_id'       => $ticket->id,
                'sender_type'     => $userType,
                'sender_id'       => $user->id,
                'message'         => trim((string) $request->input('message')),
                'attachment_path' => $attachmentPath,
                'created_at'      => $now,
            ]);

            DB::table('support_tickets')->where('id', $ticket->id)->update(['updated_at' => $now]);

            $msg = DB::table('support_ticket_messages')->where('id', $msgId)->first();

            return response()->json([
                'status'  => true,
                'message' => 'Message sent.',
                'data'    => $this->serializeMessage($msg),
            ]);
        } catch (\Exception $e) {
            Log::error('SupportTicket@addMessage: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ── serializers (shared shape with admin) ───────────────────────────────

    private function serializeTicket($t): array
    {
        return [
            'id'              => (int) $t->id,
            'ticket_no'       => $t->ticket_no,
            'user_type'       => $t->user_type,
            'ticket_category' => $t->ticket_category,
            'issue_brief'     => $t->issue_brief,
            'status'          => $t->status,
            'status_label'    => SupportTicketHelper::statusLabel($t->status),
            'attachments'     => SupportTicketHelper::decodeAttachments($t->attachments),
            'resolution_note' => $t->resolution_note,
            'closed_at'       => $t->closed_at,
            'created_at'      => $t->created_at,
            'updated_at'      => $t->updated_at,
        ];
    }

    private function serializeMessages(int $ticketId): array
    {
        $rows = DB::table('support_ticket_messages')
            ->where('ticket_id', $ticketId)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        return $rows->map(fn ($m) => $this->serializeMessage($m))->all();
    }

    private function serializeMessage($m): array
    {
        return [
            'id'             => (int) $m->id,
            'sender_type'    => $m->sender_type,
            'sender_id'      => $m->sender_id !== null ? (int) $m->sender_id : null,
            'message'        => $m->message,
            'attachment_url' => SupportTicketHelper::fileUrl($m->attachment_path ?? null),
            'created_at'     => $m->created_at,
        ];
    }
}
