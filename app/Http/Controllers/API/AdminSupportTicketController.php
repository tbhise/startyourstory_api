<?php

namespace App\Http\Controllers\API;

use App\Helpers\NotificationHelper;
use App\Helpers\SupportTicketHelper;
use App\Http\Controllers\Controller;
use App\Services\Notifications\EmailNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Admin-side support ticket management.
 *
 * All /admin/* routes are guarded globally by AdminAuthMiddleware, which sets the
 * 'admin_user' request attribute. Admins see every ticket; they can filter, assign
 * to themselves, change status, reply, and close (with a mandatory resolution note).
 */
class AdminSupportTicketController extends Controller
{
    /** GET /admin/support-tickets — stats + filtered, paginated list. */
    public function index(Request $request): JsonResponse
    {
        try {
            // ── Stats (over the whole table, unaffected by filters) ──
            $byStatus = DB::table('support_tickets')
                ->select('status', DB::raw('COUNT(*) as c'))
                ->groupBy('status')
                ->pluck('c', 'status');

            $stats = [
                'total'      => (int) array_sum($byStatus->all()),
                'submitted'  => (int) ($byStatus['submitted'] ?? 0),
                'in_process' => (int) ($byStatus['in_process'] ?? 0),
                'closed'     => (int) ($byStatus['closed'] ?? 0),
                'unassigned' => (int) DB::table('support_tickets')->whereNull('assigned_to_admin_id')->count(),
            ];

            // ── Filtered list ──
            $perPage = max(1, min((int) $request->query('per_page', 15), 50));

            $q = DB::table('support_tickets as st')
                ->leftJoin('users as u', 'u.id', '=', 'st.user_id')
                ->leftJoin('admin_users as a', 'a.id', '=', 'st.assigned_to_admin_id')
                ->select(
                    'st.id', 'st.ticket_no', 'st.user_type', 'st.ticket_category', 'st.status',
                    'st.assigned_to_admin_id', 'st.created_at', 'st.updated_at',
                    'u.name as user_name', 'u.email as user_email',
                    'a.name as assigned_admin_name'
                );

            if ($status = $request->query('status')) {
                if (in_array($status, SupportTicketHelper::STATUSES, true)) {
                    $q->where('st.status', $status);
                }
            }
            if ($category = $request->query('category')) {
                $q->where('st.ticket_category', $category);
            }
            if ($userType = $request->query('user_type')) {
                if (in_array($userType, ['student', 'firm'], true)) {
                    $q->where('st.user_type', $userType);
                }
            }
            $assigned = $request->query('assigned');
            if ($assigned === 'assigned') {
                $q->whereNotNull('st.assigned_to_admin_id');
            } elseif ($assigned === 'unassigned') {
                $q->whereNull('st.assigned_to_admin_id');
            }
            if ($search = trim((string) $request->query('search', ''))) {
                $q->where(function ($w) use ($search) {
                    $w->where('st.ticket_no', 'like', "%{$search}%")
                      ->orWhere('u.name', 'like', "%{$search}%")
                      ->orWhere('u.email', 'like', "%{$search}%");
                    if (ctype_digit($search)) {
                        $w->orWhere('st.id', (int) $search);
                    }
                });
            }

            $paginator = $q->orderByDesc('st.created_at')->orderByDesc('st.id')->paginate($perPage);

            $rows = collect($paginator->items())->map(function ($t) {
                return [
                    'id'                  => (int) $t->id,
                    'ticket_no'           => $t->ticket_no,
                    'user_type'           => $t->user_type,
                    'user_name'           => $t->user_name,
                    'user_email'          => $t->user_email,
                    'ticket_category'     => $t->ticket_category,
                    'status'              => $t->status,
                    'status_label'        => SupportTicketHelper::statusLabel($t->status),
                    'assigned_to_admin_id'=> $t->assigned_to_admin_id !== null ? (int) $t->assigned_to_admin_id : null,
                    'assigned_admin_name' => $t->assigned_admin_name,
                    'created_at'          => $t->created_at,
                    'updated_at'          => $t->updated_at,
                ];
            })->all();

            return response()->json([
                'status'  => true,
                'message' => 'Tickets fetched.',
                'data'    => [
                    'stats'    => $stats,
                    'tickets'  => $rows,
                ],
                'pagination' => [
                    'total'        => $paginator->total(),
                    'per_page'     => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'last_page'    => $paginator->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('AdminSupportTicket@index: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /** GET /admin/support-tickets/{id} — full detail incl. user info + thread. */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $ticket = DB::table('support_tickets')->where('id', (int) $id)->first();
            if (!$ticket) {
                return response()->json(['status' => false, 'message' => 'Ticket not found'], 404);
            }

            $user = DB::table('users')->where('id', $ticket->user_id)
                ->select('id', 'name', 'email', 'mobile', 'role', 'profile_image')->first();

            $assignedAdmin = null;
            if ($ticket->assigned_to_admin_id) {
                $assignedAdmin = DB::table('admin_users')->where('id', $ticket->assigned_to_admin_id)
                    ->select('id', 'name')->first();
            }

            return response()->json([
                'status'  => true,
                'message' => 'Ticket fetched.',
                'data'    => [
                    'ticket'         => [
                        'id'              => (int) $ticket->id,
                        'ticket_no'       => $ticket->ticket_no,
                        'user_type'       => $ticket->user_type,
                        'ticket_category' => $ticket->ticket_category,
                        'issue_brief'     => $ticket->issue_brief,
                        'status'          => $ticket->status,
                        'status_label'    => SupportTicketHelper::statusLabel($ticket->status),
                        'attachments'     => SupportTicketHelper::decodeAttachments($ticket->attachments),
                        'assigned_to_admin_id' => $ticket->assigned_to_admin_id !== null ? (int) $ticket->assigned_to_admin_id : null,
                        'resolution_note' => $ticket->resolution_note,
                        'closed_at'       => $ticket->closed_at,
                        'created_at'      => $ticket->created_at,
                        'updated_at'      => $ticket->updated_at,
                    ],
                    'user'           => $user,
                    'assigned_admin' => $assignedAdmin,
                    'messages'       => $this->serializeMessages((int) $id),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('AdminSupportTicket@show: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /** POST /admin/support-tickets/{id}/assign — assign to the acting admin. */
    public function assign(Request $request, $id): JsonResponse
    {
        try {
            $admin  = $request->attributes->get('admin_user');
            $ticket = DB::table('support_tickets')->where('id', (int) $id)->first();
            if (!$ticket) {
                return response()->json(['status' => false, 'message' => 'Ticket not found'], 404);
            }

            $now = now();
            $update = ['assigned_to_admin_id' => $admin->id, 'updated_at' => $now];

            // Assigning a brand-new ticket moves it into processing.
            $movedToInProcess = false;
            if ($ticket->status === 'submitted') {
                $update['status'] = 'in_process';
                $movedToInProcess = true;
            }

            DB::table('support_tickets')->where('id', $ticket->id)->update($update);

            $this->systemMessage($ticket->id, 'Ticket assigned to ' . ($admin->name ?? 'an agent') . '.');

            if ($movedToInProcess) {
                $this->systemMessage($ticket->id, 'Status changed to In Process.');
                $this->notifyUser($ticket, 'Support ticket in process',
                    "Your ticket {$ticket->ticket_no} is now being looked into.");
            }

            return response()->json(['status' => true, 'message' => 'Ticket assigned to you.']);
        } catch (\Exception $e) {
            Log::error('AdminSupportTicket@assign: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * POST /admin/support-tickets/{id}/status — change status.
     * Closing REQUIRES a resolution_note (saved + closed_at stamped + email sent).
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        try {
            $admin  = $request->attributes->get('admin_user');
            $ticket = DB::table('support_tickets')->where('id', (int) $id)->first();
            if (!$ticket) {
                return response()->json(['status' => false, 'message' => 'Ticket not found'], 404);
            }

            $validator = Validator::make($request->all(), [
                'status'          => ['required', 'in:submitted,in_process,closed'],
                // Mandatory ONLY when closing — enforced via required_if.
                'resolution_note' => ['required_if:status,closed', 'nullable', 'string', 'max:2000'],
            ], [
                'resolution_note.required_if' => 'A resolution note is required to close a ticket.',
            ]);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
            }

            $newStatus = $request->input('status');
            if ($newStatus === $ticket->status && $newStatus !== 'closed') {
                return response()->json(['status' => false, 'message' => 'Ticket is already in this status.'], 422);
            }

            $now = now();
            $update = ['status' => $newStatus, 'updated_at' => $now];

            if ($newStatus === 'closed') {
                $update['resolution_note'] = trim((string) $request->input('resolution_note'));
                $update['closed_at'] = $now;
            }

            DB::table('support_tickets')->where('id', $ticket->id)->update($update);

            $this->systemMessage($ticket->id, 'Status changed to ' . SupportTicketHelper::statusLabel($newStatus) . '.');

            // ── User notifications per status transition ──
            if ($newStatus === 'in_process') {
                $this->notifyUser($ticket, 'Support ticket in process',
                    "Your ticket {$ticket->ticket_no} is now being looked into.");
            } elseif ($newStatus === 'closed') {
                $this->notifyUser($ticket, 'Support ticket closed',
                    "Your ticket {$ticket->ticket_no} has been resolved and closed.");

                // Email only on close (Ticket ID, Category, Resolution Note).
                $owner = DB::table('users')->where('id', $ticket->user_id)->select('name', 'email')->first();
                if ($owner && $owner->email) {
                    try {
                        app(EmailNotificationService::class)->sendSupportTicketClosed(
                            $owner->email,
                            $owner->name ?? 'there',
                            $ticket->ticket_no,
                            $ticket->ticket_category,
                            $update['resolution_note']
                        );
                    } catch (\Throwable $mailEx) {
                        Log::error('AdminSupportTicket@updateStatus mail: ' . $mailEx->getMessage());
                    }
                }
            }

            return response()->json(['status' => true, 'message' => 'Ticket status updated.']);
        } catch (\Exception $e) {
            Log::error('AdminSupportTicket@updateStatus: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /** POST /admin/support-tickets/{id}/messages — admin reply in the thread. */
    public function addMessage(Request $request, $id): JsonResponse
    {
        try {
            $admin  = $request->attributes->get('admin_user');
            $ticket = DB::table('support_tickets')->where('id', (int) $id)->first();
            if (!$ticket) {
                return response()->json(['status' => false, 'message' => 'Ticket not found'], 404);
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
                    'adm_' . time() . '_' . Str::random(6) . '.' . $file->getClientOriginalExtension(),
                    'public'
                );
            }

            $now = now();
            $msgId = DB::table('support_ticket_messages')->insertGetId([
                'ticket_id'       => $ticket->id,
                'sender_type'     => 'admin',
                'sender_id'       => $admin->id,
                'message'         => trim((string) $request->input('message')),
                'attachment_path' => $attachmentPath,
                'created_at'      => $now,
            ]);

            DB::table('support_tickets')->where('id', $ticket->id)->update(['updated_at' => $now]);

            // Notify the ticket owner that an admin replied.
            $this->notifyUser($ticket, 'New reply on your support ticket',
                "Support replied to your ticket {$ticket->ticket_no}.");

            $msg = DB::table('support_ticket_messages')->where('id', $msgId)->first();

            return response()->json([
                'status'  => true,
                'message' => 'Reply sent.',
                'data'    => $this->serializeMessage($msg),
            ]);
        } catch (\Exception $e) {
            Log::error('AdminSupportTicket@addMessage: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    private function systemMessage(int $ticketId, string $text): void
    {
        DB::table('support_ticket_messages')->insert([
            'ticket_id'   => $ticketId,
            'sender_type' => 'system',
            'sender_id'   => null,
            'message'     => $text,
            'created_at'  => now(),
        ]);
    }

    private function notifyUser($ticket, string $title, string $message): void
    {
        NotificationHelper::create((int) $ticket->user_id, $title, $message);
    }

    private function serializeMessages(int $ticketId): array
    {
        return DB::table('support_ticket_messages')
            ->where('ticket_id', $ticketId)
            ->orderBy('created_at')->orderBy('id')
            ->get()
            ->map(fn ($m) => $this->serializeMessage($m))->all();
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
