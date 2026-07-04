<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Helpers\MessagingHelper;
use App\Helpers\NotificationHelper;
use App\Helpers\SubscriptionHelper;
use App\Events\MessageSent;
use App\Events\MessageRead;
use App\Events\ConversationUpdated;
use App\Jobs\SendUserPushJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class MessagingController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function authUser(Request $request): object
    {
        return $request->attributes->get('auth_user');
    }

    private function firmProfileForUser(int $userId): ?object
    {
        return DB::table('firm_profiles')->where('user_id', $userId)->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Realtime broadcasting (Reverb) — best-effort; never breaks the request.
    |--------------------------------------------------------------------------
    */

    /** @return array{0:?int,1:?int} [candidateUserId, firmUserId] */
    private function conversationParticipantUserIds(object $conv): array
    {
        $firm = DB::table('firm_profiles')->where('id', $conv->firm_id)->first();
        return [(int) $conv->candidate_id, $firm ? (int) $firm->user_id : null];
    }

    private function emitConversationUpdated(object $conv, ?int $candUserId, ?int $firmUserId, bool $toCandidate, bool $toFirm): void
    {
        if ($toCandidate && $candUserId) {
            event(new ConversationUpdated(
                $candUserId, (int) $conv->id, $conv->last_message_preview, $conv->last_message_at,
                $conv->last_message_sender_type, (int) $conv->candidate_unread_count,
                MessagingHelper::getUnreadCount($candUserId, 'student')
            ));
        }
        if ($toFirm && $firmUserId) {
            event(new ConversationUpdated(
                $firmUserId, (int) $conv->id, $conv->last_message_preview, $conv->last_message_at,
                $conv->last_message_sender_type, (int) $conv->firm_unread_count,
                MessagingHelper::getUnreadCount($firmUserId, 'firm')
            ));
        }
    }

    /** Broadcast a new message to the thread + a list/badge update to both participants. */
    private function broadcastMessage(int $conversationId, int $messageId, int $senderId, string $senderType, string $message): void
    {
        try {
            event(new MessageSent($conversationId, $messageId, $senderId, $senderType, $message, now()->toISOString()));
            $conv = DB::table('conversations')->where('id', $conversationId)->first();
            if (!$conv) return;
            [$candUserId, $firmUserId] = $this->conversationParticipantUserIds($conv);
            $this->emitConversationUpdated($conv, $candUserId, $firmUserId, true, true);
        } catch (\Throwable $e) {
            Log::warning('Broadcast (message) failed: ' . $e->getMessage());
        }
    }

    /** Broadcast a read receipt to the thread + a badge update to the reader. */
    private function broadcastRead(int $conversationId, string $readerRole): void
    {
        try {
            $readerType = $readerRole === 'student' ? 'candidate' : 'firm';
            event(new MessageRead($conversationId, $readerType, now()->toISOString()));
            $conv = DB::table('conversations')->where('id', $conversationId)->first();
            if (!$conv) return;
            [$candUserId, $firmUserId] = $this->conversationParticipantUserIds($conv);
            $this->emitConversationUpdated(
                $conv, $candUserId, $firmUserId,
                $readerRole === 'student', $readerRole === 'firm'
            );
        } catch (\Throwable $e) {
            Log::warning('Broadcast (read) failed: ' . $e->getMessage());
        }
    }

    /**
     * Resolve the peer (firm for a student viewer, candidate for a firm viewer).
     * Used for the single-conversation path; the list path injects this in bulk.
     */
    private function resolvePeer(object $conv, string $role): array
    {
        if ($role === 'student') {
            $firm = DB::table('firm_profiles')->where('id', $conv->firm_id)->first();
            return [
                'id'       => $conv->firm_id,
                'name'     => $firm->firm_name ?? 'Unknown Firm',
                'avatar'   => ($firm && $firm->logo_path) ? asset('/storage/' . $firm->logo_path) : null,
                'verified' => $firm ? ($firm->verification_status === 'approved') : false,
                'type'     => 'firm',
            ];
        }
        $candidate = DB::table('users')->where('id', $conv->candidate_id)->first();
        return [
            'id'       => $conv->candidate_id,
            'name'     => $candidate->name ?? 'Unknown Candidate',
            'avatar'   => ($candidate && $candidate->profile_image) ? asset('/storage/' . $candidate->profile_image) : null,
            'verified' => false,
            'type'     => 'candidate',
        ];
    }

    /**
     * Build the conversation payload from DENORMALIZED columns only — no per-row
     * messages scan. $peer / $requestStatus may be injected (bulk list path);
     * otherwise they are resolved here (single-conversation path).
     */
    private function formatConversation(object $conv, string $role, ?array $peer = null, ?string $requestStatus = null): array
    {
        $peer ??= $this->resolvePeer($conv, $role);
        if ($requestStatus === null) {
            $requestStatus = DB::table('message_requests')->where('conversation_id', $conv->id)->value('status');
        }

        $unread = $role === 'student'
            ? (int) ($conv->candidate_unread_count ?? 0)
            : (int) ($conv->firm_unread_count ?? 0);

        return [
            'id'              => $conv->id,
            'status'          => $conv->status,
            'initiated_by'    => $conv->initiated_by,
            'last_message_at' => $conv->last_message_at,
            'created_at'      => $conv->created_at,
            'peer'            => $peer,
            'last_message'    => $conv->last_message_id ? [
                'message'     => $conv->last_message_preview,
                'sender_type' => $conv->last_message_sender_type,
                'created_at'  => $conv->last_message_at,
            ] : null,
            'unread_count'   => $unread,
            'request_status' => $requestStatus ?: null,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | GET /messaging/conversations
    | Query: tab=all|unread|requests, search, page
    |--------------------------------------------------------------------------
    */

    public function getConversations(Request $request)
    {
        try {
            $user  = $this->authUser($request);
            $role  = $user->role;
            $tab   = $request->get('tab', 'all');
            $search = trim($request->get('search', ''));
            $page  = max(1, (int) $request->get('page', 1));
            $perPage = 20;

            if ($role === 'student') {
                $query = DB::table('conversations as c')
                    ->where('c.candidate_id', $user->id);
            } else {
                $firm = $this->firmProfileForUser($user->id);
                if (!$firm) {
                    return response()->json(['status' => false, 'message' => 'Firm profile not found'], 404);
                }
                $query = DB::table('conversations as c')
                    ->where('c.firm_id', $firm->id);
            }

            // Tab filters
            if ($tab === 'unread') {
                $unreadCol = $role === 'student' ? 'c.candidate_unread_count' : 'c.firm_unread_count';
                $query->where($unreadCol, '>', 0);
            } elseif ($tab === 'requests') {
                $query->where('c.status', 'pending')
                    ->whereExists(function ($q) {
                        $q->select(DB::raw(1))
                            ->from('message_requests as mr')
                            ->whereColumn('mr.conversation_id', 'c.id')
                            ->where('mr.status', 'pending');
                    });
            } else {
                $query->whereIn('c.status', ['active', 'pending']);
            }

            // Search
            if ($search !== '') {
                if ($role === 'student') {
                    $query->join('firm_profiles as fp', 'fp.id', '=', 'c.firm_id')
                        ->where('fp.firm_name', 'like', "%{$search}%");
                } else {
                    $query->join('users as u', 'u.id', '=', 'c.candidate_id')
                        ->where('u.name', 'like', "%{$search}%");
                }
            }

            $total = (clone $query)->count();
            $convs = $query
                ->select('c.*')
                ->orderByDesc('c.last_message_at')
                ->orderByDesc('c.created_at')
                ->offset(($page - 1) * $perPage)
                ->limit($perPage)
                ->get();

            // ── Bulk-resolve peers + request statuses to avoid N+1 across the page ──
            $convIds = $convs->pluck('id')->all();
            $requestMap = $convIds
                ? DB::table('message_requests')->whereIn('conversation_id', $convIds)
                    ->pluck('status', 'conversation_id')
                : collect();

            $peerMap = [];
            if ($role === 'student') {
                $firmIds = $convs->pluck('firm_id')->unique()->all();
                $firms = $firmIds
                    ? DB::table('firm_profiles')->whereIn('id', $firmIds)
                        ->get(['id', 'firm_name', 'logo_path', 'verification_status'])->keyBy('id')
                    : collect();
                foreach ($convs as $c) {
                    $f = $firms->get($c->firm_id);
                    $peerMap[$c->id] = [
                        'id'       => $c->firm_id,
                        'name'     => $f->firm_name ?? 'Unknown Firm',
                        'avatar'   => ($f && $f->logo_path) ? asset('/storage/' . $f->logo_path) : null,
                        'verified' => $f ? ($f->verification_status === 'approved') : false,
                        'type'     => 'firm',
                    ];
                }
            } else {
                $candIds = $convs->pluck('candidate_id')->unique()->all();
                $users = $candIds
                    ? DB::table('users')->whereIn('id', $candIds)->get(['id', 'name', 'profile_image'])->keyBy('id')
                    : collect();
                foreach ($convs as $c) {
                    $u = $users->get($c->candidate_id);
                    $peerMap[$c->id] = [
                        'id'       => $c->candidate_id,
                        'name'     => $u->name ?? 'Unknown Candidate',
                        'avatar'   => ($u && $u->profile_image) ? asset('/storage/' . $u->profile_image) : null,
                        'verified' => false,
                        'type'     => 'candidate',
                    ];
                }
            }

            $list = $convs->map(fn($c) => $this->formatConversation(
                $c,
                $role,
                $peerMap[$c->id] ?? null,
                $requestMap->get($c->id),
            ))->values();

            return response()->json([
                'status'   => true,
                'message'  => 'Conversations fetched',
                'data'     => [
                    'conversations' => $list,
                    'total'         => $total,
                    'page'          => $page,
                    'has_more'      => ($page * $perPage) < $total,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('MessagingController@getConversations: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /messaging/conversations/{id}/messages
    |--------------------------------------------------------------------------
    */

    public function getMessages(Request $request, int $conversationId)
    {
        try {
            $user = $this->authUser($request);
            $conv = DB::table('conversations')->where('id', $conversationId)->first();

            if (!$conv) {
                return response()->json(['status' => false, 'message' => 'Conversation not found'], 404);
            }

            if (!$this->userOwnsConversation($user, $conv)) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
            }

            // NOTE (2026-07-03 business rules): the free-firm "request unlock"
            // gate was removed — existing conversations (including pending
            // student-initiated requests) are always viewable by participants.

            $page    = max(1, (int) $request->get('page', 1));
            $perPage = 30;

            $total = DB::table('messages')->where('conversation_id', $conversationId)->count();
            $msgs  = DB::table('messages')
                ->where('conversation_id', $conversationId)
                ->orderByDesc('created_at')
                ->offset(($page - 1) * $perPage)
                ->limit($perPage)
                ->get()
                ->reverse()
                ->values();

            // Decorate with attachments (ids only — never file paths). Old
            // messages simply get an empty array.
            $attachmentsByMsg = \App\Services\Messaging\MessageAttachmentService::forMessages(
                $msgs->pluck('id')->all()
            );
            $msgs = $msgs->map(function ($m) use ($attachmentsByMsg) {
                $m->attachments = $attachmentsByMsg[(int) $m->id] ?? [];
                return $m;
            });

            // Mark messages from peer as read + zero this viewer's unread counter.
            $readerType = $user->role === 'student' ? 'firm' : 'candidate';
            DB::table('messages')
                ->where('conversation_id', $conversationId)
                ->where('sender_type', $readerType)
                ->where('is_read', false)
                ->update(['is_read' => true, 'read_at' => now(), 'updated_at' => now()]);
            MessagingHelper::applyConversationRead($conversationId, $user->role);
            $this->broadcastRead($conversationId, $user->role);

            // Re-read so the formatted payload reflects the zeroed counter.
            $conv = DB::table('conversations')->where('id', $conversationId)->first();
            $convFormatted = $this->formatConversation($conv, $user->role);

            return response()->json([
                'status'  => true,
                'message' => 'Messages fetched',
                'data'    => [
                    'conversation' => $convFormatted,
                    'messages'     => $msgs,
                    'total'        => $total,
                    'page'         => $page,
                    'has_more'     => ($page * $perPage) < $total,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('MessagingController@getMessages: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /messaging/conversations
    | Start a new conversation
    |--------------------------------------------------------------------------
    */

    public function startConversation(Request $request)
    {
        try {
            $user = $this->authUser($request);

            $validator = Validator::make($request->all(), [
                'message'       => 'required|string|min:1|max:2000',
                'candidate_id'  => 'required_if_role_firm|integer',
                'firm_id'       => 'required_if_role_candidate|integer',
            ]);

            $message = trim($request->input('message', ''));
            if (empty($message)) {
                return response()->json(['status' => false, 'message' => 'Message is required'], 422);
            }

            DB::beginTransaction();

            if ($user->role === 'firm') {
                $firm = $this->firmProfileForUser($user->id);
                if (!$firm) {
                    DB::rollBack();
                    return response()->json(['status' => false, 'message' => 'Firm profile not found'], 404);
                }

                $candidateId = (int) $request->input('candidate_id');
                $candidate   = DB::table('users')
                    ->where('id', $candidateId)
                    ->where('role', 'student')
                    ->where('is_deleted', false)
                    ->first();
                if (!$candidate) {
                    DB::rollBack();
                    return response()->json(['status' => false, 'message' => 'Candidate not found'], 404);
                }

                // Check limit
                $check = MessagingHelper::canStartNewConversation($firm->id);
                if (!$check['allowed']) {
                    DB::rollBack();
                    return response()->json([
                        'status'  => false,
                        'message' => $check['message'],
                        'code'    => $check['reason'],
                    ], 403);
                }

                // Existing conversation?
                $existing = DB::table('conversations')
                    ->where('candidate_id', $candidateId)
                    ->where('firm_id', $firm->id)
                    ->first();

                if ($existing) {
                    DB::rollBack();
                    return response()->json([
                        'status'  => false,
                        'message' => 'Conversation already exists',
                        'data'    => ['conversation_id' => $existing->id],
                    ], 409);
                }

                $convId = DB::table('conversations')->insertGetId([
                    'candidate_id'    => $candidateId,
                    'firm_id'         => $firm->id,
                    'initiated_by'    => 'firm',
                    'status'          => 'pending',
                    'last_message_at' => now(),
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);

                DB::table('message_requests')->insert([
                    'conversation_id' => $convId,
                    'recipient_id'    => $candidateId,
                    'recipient_type'  => 'candidate',
                    'status'          => 'pending',
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);

                $msgId = DB::table('messages')->insertGetId([
                    'conversation_id' => $convId,
                    'sender_id'       => $user->id,
                    'sender_type'     => 'firm',
                    'message'         => $message,
                    'is_read'         => false,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
                MessagingHelper::applyMessageSent($convId, 'firm', $msgId, $message);

                MessagingHelper::incrementConversationsStarted($firm->id);

                // Notify candidate
                NotificationHelper::create(
                    $candidateId,
                    'New Message Request',
                    $firm->firm_name . ' sent you a message request.'
                );

                // Email notification
                try {
                    $candidateUser = DB::table('users')->where('id', $candidateId)->first();
                    if ($candidateUser) {
                        Mail::to($candidateUser->email)->queue(
                            new \App\Mail\NewMessageRequestMail($candidateUser, $firm, $message)
                        );
                    }
                } catch (\Exception $e) {
                    Log::warning('Messaging email failed: ' . $e->getMessage());
                }

            } else {
                // Candidate initiating
                $firmId    = (int) $request->input('firm_id');
                $firmProfile = DB::table('firm_profiles')->where('id', $firmId)->first();
                if (!$firmProfile) {
                    DB::rollBack();
                    return response()->json(['status' => false, 'message' => 'Firm not found'], 404);
                }

                // New-conversation gate: firm policy (premium / free-under-limit)
                // AND the firm accepts direct messages. Failures collapse to a
                // single "Not Accepting Direct Messages" message (no premium leak).
                $check = MessagingHelper::canStudentMessageFirm($firmId);
                if (!$check['allowed']) {
                    DB::rollBack();
                    return response()->json([
                        'status'  => false,
                        'message' => $check['message'],
                        'code'    => $check['reason'],
                    ], 403);
                }

                $existing = DB::table('conversations')
                    ->where('candidate_id', $user->id)
                    ->where('firm_id', $firmId)
                    ->first();

                if ($existing) {
                    DB::rollBack();
                    return response()->json([
                        'status'  => false,
                        'message' => 'Conversation already exists',
                        'data'    => ['conversation_id' => $existing->id],
                    ], 409);
                }

                $firmUser = DB::table('users')->where('id', $firmProfile->user_id)->first();
                // Firm account gone/deactivated → same generic message (no detail leak).
                if (!$firmUser || $firmUser->is_deleted) {
                    DB::rollBack();
                    return response()->json([
                        'status'  => false,
                        'message' => 'Firm Not Accepting Direct Messages',
                        'code'    => 'not_accepting',
                    ], 403);
                }

                $convId = DB::table('conversations')->insertGetId([
                    'candidate_id'    => $user->id,
                    'firm_id'         => $firmId,
                    'initiated_by'    => 'candidate',
                    'status'          => 'pending',
                    'last_message_at' => now(),
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);

                DB::table('message_requests')->insert([
                    'conversation_id' => $convId,
                    'recipient_id'    => $firmProfile->user_id,
                    'recipient_type'  => 'firm',
                    'status'          => 'pending',
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);

                $msgId = DB::table('messages')->insertGetId([
                    'conversation_id' => $convId,
                    'sender_id'       => $user->id,
                    'sender_type'     => 'candidate',
                    'message'         => $message,
                    'is_read'         => false,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
                MessagingHelper::applyMessageSent($convId, 'candidate', $msgId, $message);

                // Final business rule (2026-07-03): the free-conversation limit
                // applies to ALL new conversations involving the firm, in BOTH
                // directions — student-initiated ones consume the quota too.
                // Incremented for premium firms as well (same as the firm-
                // initiated branch); the gate simply doesn't enforce it for them.
                MessagingHelper::incrementConversationsStarted($firmId);

                if ($firmUser) {
                    NotificationHelper::create(
                        $firmUser->id,
                        'New Message Request',
                        $user->name . ' sent you a message request.'
                    );
                    try {
                        Mail::to($firmUser->email)->queue(
                            new \App\Mail\NewMessageRequestMail($firmUser, null, $message, $user)
                        );
                    } catch (\Exception $e) {
                        Log::warning('Messaging email failed: ' . $e->getMessage());
                    }
                }
            }

            DB::commit();

            // Realtime: notify the recipient's list/badge + open thread.
            $this->broadcastMessage(
                $convId,
                $msgId,
                $user->id,
                $user->role === 'student' ? 'candidate' : 'firm',
                $message
            );

            return response()->json([
                'status'  => true,
                'message' => 'Conversation started',
                'data'    => ['conversation_id' => $convId],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('MessagingController@startConversation: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /messaging/conversations/{id}/messages
    | Send message in existing conversation
    |--------------------------------------------------------------------------
    */

    public function sendMessage(Request $request, int $conversationId)
    {
        try {
            $user = $this->authUser($request);
            $conv = DB::table('conversations')->where('id', $conversationId)->first();

            if (!$conv) {
                return response()->json(['status' => false, 'message' => 'Conversation not found'], 404);
            }

            if (!$this->userOwnsConversation($user, $conv)) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
            }

            // Existing-conversation gate (2026-07-03 business rules): premium/
            // limit policy NEVER blocks an existing conversation — only a closed
            // conversation or an inactive/deleted participant does.
            $gate = MessagingHelper::canSendMessageInConversation($conv);
            if (!$gate['allowed']) {
                return response()->json(['status' => false, 'message' => $gate['message'], 'code' => $gate['reason']], 403);
            }

            $message = trim($request->input('message', ''));

            // Attachments (ADDITIVE, optional). A plain-JSON text message takes
            // the exact same path as before — nothing below changes for it.
            $files = $request->file('attachments', []);
            if ($files instanceof \Illuminate\Http\UploadedFile) {
                $files = [$files];
            }
            $files = is_array($files) ? array_values(array_filter($files)) : [];
            $hasAttachments = count($files) > 0;

            if (!$hasAttachments && empty($message)) {
                return response()->json(['status' => false, 'message' => 'Message must be between 1 and 2000 characters'], 422);
            }
            if (mb_strlen($message) > 2000) {
                return response()->json(['status' => false, 'message' => 'Message must be between 1 and 2000 characters'], 422);
            }
            if ($hasAttachments) {
                $attachmentError = \App\Services\Messaging\MessageAttachmentService::validateSet($files);
                if ($attachmentError !== null) {
                    return response()->json(['status' => false, 'message' => $attachmentError], 422);
                }
            }

            $senderType = $user->role === 'student' ? 'candidate' : 'firm';

            DB::beginTransaction();

            // If pending request and the recipient is now replying → accept it
            $req = DB::table('message_requests')->where('conversation_id', $conv->id)->first();
            if ($req && $req->status === 'pending' && $conv->status === 'pending') {
                $isRecipientReplying = (int) $req->recipient_id === (int) $user->id;

                if ($isRecipientReplying) {
                    DB::table('message_requests')
                        ->where('conversation_id', $conv->id)
                        ->update(['status' => 'accepted', 'updated_at' => now()]);

                    DB::table('conversations')
                        ->where('id', $conv->id)
                        ->update(['status' => 'active', 'updated_at' => now()]);

                    // (Request-unlock counting removed 2026-07-03 — replying to a
                    // request is never limited; it accepts the conversation only.)
                }
            }

            $msgId = DB::table('messages')->insertGetId([
                'conversation_id' => $conv->id,
                'sender_id'       => $user->id,
                'sender_type'     => $senderType,
                'message'         => $message, // '' for attachment-only messages
                'is_read'         => false,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            // Store attachments (original format, private disk) + build the
            // text stand-ins used by preview/push when the message has no text.
            $bellPreview = $message;
            $pushBody    = $message;
            if ($hasAttachments) {
                $attachmentPayloads = \App\Services\Messaging\MessageAttachmentService::store($files, (int) $conv->id, $msgId);
                if ($message === '') {
                    [$bellPreview, $pushBody] = \App\Services\Messaging\MessageAttachmentService::summaries($attachmentPayloads);
                }
            }

            // Refresh last-message snapshot + bump recipient's unread counter.
            MessagingHelper::applyMessageSent($conv->id, $senderType, $msgId, $bellPreview);

            // Notify the other party
            $this->notifyPeer($user, $conv, $pushBody, $senderType);

            DB::commit();

            // Realtime (after commit so subscribers see committed state).
            // V1 decision: the websocket payload stays lightweight — attachment
            // messages broadcast the generic "📎 Attachment" marker and the
            // recipient's client refetches the thread for full details.
            $this->broadcastMessage(
                $conv->id,
                $msgId,
                $user->id,
                $senderType,
                $hasAttachments ? '📎 Attachment' : $message
            );

            return response()->json([
                'status'  => true,
                'message' => 'Message sent',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('MessagingController@sendMessage: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /messaging/conversations/{id}/ignore
    |--------------------------------------------------------------------------
    */

    public function ignoreRequest(Request $request, int $conversationId)
    {
        try {
            $user = $this->authUser($request);
            $conv = DB::table('conversations')->where('id', $conversationId)->first();

            if (!$conv || !$this->userOwnsConversation($user, $conv)) {
                return response()->json(['status' => false, 'message' => 'Not found'], 404);
            }

            DB::table('conversations')
                ->where('id', $conversationId)
                ->update(['status' => 'ignored', 'updated_at' => now()]);

            DB::table('message_requests')
                ->where('conversation_id', $conversationId)
                ->update(['status' => 'ignored', 'updated_at' => now()]);

            return response()->json(['status' => true, 'message' => 'Request ignored']);
        } catch (\Exception $e) {
            Log::error('MessagingController@ignoreRequest: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /messaging/conversations/{id}/mark-read
    |--------------------------------------------------------------------------
    */

    public function markRead(Request $request, int $conversationId)
    {
        try {
            $user = $this->authUser($request);
            $conv = DB::table('conversations')->where('id', $conversationId)->first();

            if (!$conv || !$this->userOwnsConversation($user, $conv)) {
                return response()->json(['status' => false, 'message' => 'Not found'], 404);
            }

            $peerSenderType = $user->role === 'student' ? 'firm' : 'candidate';

            DB::table('messages')
                ->where('conversation_id', $conversationId)
                ->where('sender_type', $peerSenderType)
                ->where('is_read', false)
                ->update(['is_read' => true, 'read_at' => now(), 'updated_at' => now()]);
            MessagingHelper::applyConversationRead($conversationId, $user->role);
            $this->broadcastRead($conversationId, $user->role);

            return response()->json(['status' => true, 'message' => 'Marked as read']);
        } catch (\Exception $e) {
            Log::error('MessagingController@markRead: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /messaging/unread-count
    |--------------------------------------------------------------------------
    */

    public function getUnreadCount(Request $request)
    {
        try {
            $user  = $this->authUser($request);
            $count = MessagingHelper::getUnreadCount($user->id, $user->role);
            return response()->json([
                'status' => true,
                'message' => 'Unread count',
                'data'   => ['count' => $count],
            ]);
        } catch (\Exception $e) {
            Log::error('MessagingController@getUnreadCount: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /messaging/settings  (firm only)
    | PUT /messaging/settings
    |--------------------------------------------------------------------------
    */

    public function getSettings(Request $request)
    {
        try {
            $user = $this->authUser($request);
            if ($user->role !== 'firm') {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
            }
            $firm = $this->firmProfileForUser($user->id);
            if (!$firm) {
                return response()->json(['status' => false, 'message' => 'Firm profile not found'], 404);
            }
            $limits   = MessagingHelper::getOrCreateLimits($firm->id);
            $isPremium = SubscriptionHelper::isPremiumFirm($firm->id);

            // accept_direct_messages toggle + request-unlock system retired
            // (2026-07-03 business rules) — fields removed from the payload.
            return response()->json([
                'status'  => true,
                'message' => 'Settings fetched',
                'data'    => [
                    'is_premium'                      => $isPremium,
                    'lifetime_conversations_started'  => (int) $limits->lifetime_conversations_started,
                    'monthly_conversations_started'   => (int) $limits->monthly_conversations_started,
                    'free_conversation_limit'         => MessagingHelper::freeFirmConversationLimit(),
                    'allow_free_firm_messaging'       => MessagingHelper::allowFreeFirmMessaging(),
                    'premium_monthly_limit'           => 0,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('MessagingController@getSettings: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // updateSettings (accept_direct_messages toggle) removed 2026-07-03 —
    // the per-firm toggle is retired from the product; the messaging_settings
    // table remains in the DB but is no longer read or written by the app.

    /*
    |--------------------------------------------------------------------------
    | GET /messaging/firm/{firmId}/status
    | Returns whether firm accepts messages (for candidate-side buttons)
    |--------------------------------------------------------------------------
    */

    public function getFirmMessagingStatus(Request $request, int $firmId)
    {
        try {
            $firm = DB::table('firm_profiles')->where('id', $firmId)->first();
            if (!$firm) {
                return response()->json(['status' => false, 'message' => 'Firm not found'], 404);
            }

            // Effective gate for a student starting a NEW conversation: the firm
            // policy alone (premium / free-under-limit). The accept_direct_messages
            // toggle was retired 2026-07-03 and is no longer consulted.
            $canStart = MessagingHelper::canStudentMessageFirm($firmId);
            $isPremium = SubscriptionHelper::isPremiumFirm($firmId);
            $user    = $this->authUser($request);

            $existingConvId = null;
            if ($user->role === 'student') {
                $existing = DB::table('conversations')
                    ->where('candidate_id', $user->id)
                    ->where('firm_id', $firmId)
                    ->first();
                $existingConvId = $existing ? $existing->id : null;
            }

            return response()->json([
                'status'  => true,
                'message' => 'Firm messaging status',
                'data'    => [
                    // Kept for payload compatibility; mirrors the effective gate
                    // now that the per-firm toggle is retired.
                    'accept_direct_messages'   => $canStart['allowed'],
                    'can_start_conversation'   => $canStart['allowed'],
                    'is_premium'               => $isPremium,
                    'existing_conversation_id' => $existingConvId,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('MessagingController@getFirmMessagingStatus: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /messaging/candidate/{candidateId}/status
    | Returns existing conversation id (for firm-side buttons)
    |--------------------------------------------------------------------------
    */

    public function getCandidateMessagingStatus(Request $request, int $candidateId)
    {
        try {
            $user = $this->authUser($request);
            if ($user->role !== 'firm') {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
            }

            $firm = $this->firmProfileForUser($user->id);
            if (!$firm) {
                return response()->json(['status' => false, 'message' => 'Firm profile not found'], 404);
            }

            $existing = DB::table('conversations')
                ->where('candidate_id', $candidateId)
                ->where('firm_id', $firm->id)
                ->first();

            $limits   = MessagingHelper::getOrCreateLimits($firm->id);
            $canStart = MessagingHelper::canStartNewConversation($firm->id);

            return response()->json([
                'status'  => true,
                'message' => 'Candidate messaging status',
                'data'    => [
                    'existing_conversation_id' => $existing ? $existing->id : null,
                    'can_start_conversation'   => $canStart['allowed'],
                    'limit_reason'             => $canStart['reason'],
                    'limit_message'            => $canStart['message'],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('MessagingController@getCandidateMessagingStatus: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Private Helpers
    |--------------------------------------------------------------------------
    */

    private function userOwnsConversation(object $user, object $conv): bool
    {
        if ($user->role === 'student') {
            return (int) $conv->candidate_id === (int) $user->id;
        }
        $firm = $this->firmProfileForUser($user->id);
        return $firm && (int) $conv->firm_id === (int) $firm->id;
    }

    private function notifyPeer(object $sender, object $conv, string $message, string $senderType): void
    {
        try {
            // NOTE: no per-message bell notification (NotificationHelper::create).
            // The Messages unread badge already tracks per-message counts in realtime
            // (ConversationUpdated), so adding a bell entry per message floods the
            // notification bell. The one-time "new conversation request" bell + email
            // still fire in startConversation; replies notify via push only.
            if ($senderType === 'firm') {
                $firm      = $this->firmProfileForUser($sender->id);
                $firmName  = $firm ? $firm->firm_name : 'A firm';
                $candidate = DB::table('users')->where('id', $conv->candidate_id)->first();
                if ($candidate) {
                    // No email for replies (final 2026-07-04 strategy): email fires
                    // only on new-conversation requests (NewMessageRequestMail in
                    // startConversation). Push below is the only reply channel.
                    $this->pushToPeer((int) $candidate->id, $firmName, $message, (int) $conv->id);
                }
            } else {
                $firmProfile = DB::table('firm_profiles')->where('id', $conv->firm_id)->first();
                $firmUser    = $firmProfile ? DB::table('users')->where('id', $firmProfile->user_id)->first() : null;
                if ($firmUser) {
                    // No email for replies (final 2026-07-04 strategy): email fires
                    // only on new-conversation requests (NewMessageRequestMail in
                    // startConversation). Push below is the only reply channel.
                    $this->pushToPeer((int) $firmUser->id, $sender->name, $message, (int) $conv->id);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Messaging peer notification failed: ' . $e->getMessage());
        }
    }

    /** Suppress the push while the recipient was active within this window (s). */
    public const PUSH_ACTIVE_SUPPRESS_SECONDS = 180;
    /** Minimum gap between pushes for one conversation+recipient (s). */
    public const PUSH_COOLDOWN_SECONDS = 120;
    /** How long unsent messages keep accumulating toward one aggregate push (s). */
    public const PUSH_AGGREGATE_TTL = 900;
    /** Fallback flush: re-check a suppressed push this long after suppression (s). */
    public const PUSH_FLUSH_DELAY_SECONDS = 120;

    /**
     * Per-message push to the conversation peer, spam-reduced (2026-07-04):
     *  - AGGREGATION: every message increments a pending counter; messages
     *    suppressed by the gates below are not lost — the next allowed push
     *    says "{n} new messages from {name}" instead of pushing n times.
     *  - Active suppression FIRST (180s, raised from 60s): recipient is in the
     *    app where Reverb + the in-app toast already deliver; checking before
     *    the cooldown means a skip never burns the cooldown window.
     *  - 2-minute cooldown per conversation+recipient (atomic Cache::add).
     *  - Collapse tag `conv_{id}`: whatever does get through REPLACES the
     *    previous browser notification for this conversation, never stacks.
     * Never throws — called inside notifyPeer's try/catch.
     */
    private function pushToPeer(int $recipientId, string $senderName, string $message, int $conversationId): void
    {
        // Count this message toward the aggregate regardless of the gates below.
        $countKey = "msg_push_agg_{$conversationId}_{$recipientId}";
        if (!Cache::add($countKey, 1, self::PUSH_AGGREGATE_TTL)) {
            Cache::increment($countKey);
        }

        $recentlyActive = DB::table('user_sessions')
            ->where('user_id', $recipientId)
            ->where('last_activity_at', '>=', now()->subSeconds(self::PUSH_ACTIVE_SUPPRESS_SECONDS))
            ->exists();
        if ($recentlyActive) {
            Log::debug("pushToPeer skipped: recipient {$recipientId} active (conv {$conversationId}) — message added to aggregate");
            // Fallback flush (2026-07-05): if the recipient closes the app right
            // after this suppression and no further message ever arrives, the
            // aggregate would never flush — the message would stay silently
            // unnotified. Record WHEN the last suppression happened and schedule
            // ONE delayed re-check per conversation+recipient burst (atomic add).
            // The job pushes only if the recipient had no activity after this
            // moment and the conversation is still unread (DB truth).
            Cache::put(
                "msg_push_flush_ts_{$conversationId}_{$recipientId}",
                now()->getTimestamp(),
                self::PUSH_AGGREGATE_TTL
            );
            if (Cache::add("msg_push_flush_{$conversationId}_{$recipientId}", 1, self::PUSH_FLUSH_DELAY_SECONDS + 60)) {
                \App\Jobs\FlushSuppressedMessagePushJob::dispatch($conversationId, $recipientId)
                    ->delay(now()->addSeconds(self::PUSH_FLUSH_DELAY_SECONDS));
            }
            return;
        }

        // Cooldown gate (atomic): false = a push already went out this window;
        // the counter keeps accumulating for the next allowed push.
        if (!Cache::add("msg_push_cd_{$conversationId}_{$recipientId}", 1, self::PUSH_COOLDOWN_SECONDS)) {
            Log::debug("pushToPeer skipped: cooldown active (conv {$conversationId}, recipient {$recipientId}) — message added to aggregate");
            return;
        }

        // Pending count = messages since the last push (including this one).
        $pending = max(1, (int) Cache::get($countKey, 1));
        Cache::forget($countKey);

        $title = $pending > 1
            ? "{$pending} new messages from {$senderName}"
            : "New message from {$senderName}";
        $preview = mb_strlen($message) > 80 ? mb_substr($message, 0, 77) . '…' : $message;

        Log::debug("pushToPeer: dispatching push (conv {$conversationId}, recipient {$recipientId}, pending {$pending})");
        SendUserPushJob::dispatch(
            $recipientId,
            $title,
            $preview,
            '/messages',
            [],
            'conv_' . $conversationId
        );
    }
}
