<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Helpers\MessagingHelper;
use App\Helpers\NotificationHelper;
use App\Helpers\SubscriptionHelper;
use Illuminate\Http\Request;
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

    private function formatConversation(object $conv, int $authUserId, string $role): array
    {
        $lastMsg = DB::table('messages')
            ->where('conversation_id', $conv->id)
            ->orderByDesc('created_at')
            ->first();

        // Unread count for this viewer
        $otherSenderType = $role === 'student' ? 'firm' : 'candidate';
        $unread = DB::table('messages')
            ->where('conversation_id', $conv->id)
            ->where('sender_type', $otherSenderType)
            ->where('is_read', false)
            ->count();

        // Peer info
        if ($role === 'student') {
            $firm    = DB::table('firm_profiles')->where('id', $conv->firm_id)->first();
            $firmUser = $firm ? DB::table('users')->where('id', $firm->user_id)->first() : null;
            $peer = [
                'id'       => $conv->firm_id,
                'name'     => $firm->firm_name ?? 'Unknown Firm',
                'avatar'   => $firm->logo ?? null,
                'verified' => $firm ? ($firm->verification_status === 'approved') : false,
                'type'     => 'firm',
            ];
        } else {
            $candidate = DB::table('users')->where('id', $conv->candidate_id)->first();
            $sp        = $candidate ? DB::table('student_profiles')->where('user_id', $candidate->id)->first() : null;
            $peer = [
                'id'       => $conv->candidate_id,
                'name'     => $candidate->name ?? 'Unknown Candidate',
                'avatar'   => $sp->profile_image ?? null,
                'verified' => false,
                'type'     => 'candidate',
            ];
        }

        $request = DB::table('message_requests')->where('conversation_id', $conv->id)->first();

        return [
            'id'              => $conv->id,
            'status'          => $conv->status,
            'initiated_by'    => $conv->initiated_by,
            'last_message_at' => $conv->last_message_at,
            'created_at'      => $conv->created_at,
            'peer'            => $peer,
            'last_message'    => $lastMsg ? [
                'message'     => $lastMsg->message,
                'sender_type' => $lastMsg->sender_type,
                'created_at'  => $lastMsg->created_at,
            ] : null,
            'unread_count'   => (int) $unread,
            'request_status' => $request ? $request->status : null,
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
                $otherType = $role === 'student' ? 'firm' : 'candidate';
                $query->whereExists(function ($q) use ($otherType) {
                    $q->select(DB::raw(1))
                        ->from('messages as m')
                        ->whereColumn('m.conversation_id', 'c.id')
                        ->where('m.sender_type', $otherType)
                        ->where('m.is_read', false);
                });
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

            $list = $convs->map(fn($c) => $this->formatConversation($c, $user->id, $role))->values();

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

            // For free firms: check if they can unlock this request
            if ($user->role === 'firm') {
                $firm = $this->firmProfileForUser($user->id);
                if ($conv->status === 'pending' && $conv->initiated_by === 'candidate') {
                    $request_ = DB::table('message_requests')->where('conversation_id', $conv->id)->first();
                    if ($request_ && $request_->status === 'pending') {
                        if (!MessagingHelper::canFirmUnlockRequest($firm->id)) {
                            return response()->json([
                                'status'  => false,
                                'message' => 'Upgrade to view more messages.',
                                'code'    => 'request_limit_reached',
                            ], 403);
                        }
                    }
                }
            }

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

            // Mark messages from peer as read
            $readerType = $user->role === 'student' ? 'firm' : 'candidate';
            DB::table('messages')
                ->where('conversation_id', $conversationId)
                ->where('sender_type', $readerType)
                ->where('is_read', false)
                ->update(['is_read' => true, 'read_at' => now(), 'updated_at' => now()]);

            $convFormatted = $this->formatConversation($conv, $user->id, $user->role);

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
                $candidate   = DB::table('users')->where('id', $candidateId)->where('role', 'student')->first();
                if (!$candidate) {
                    DB::rollBack();
                    return response()->json(['status' => false, 'message' => 'Candidate not found'], 404);
                }

                // Check limit
                $check = MessagingHelper::canFirmStartConversation($firm->id);
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

                DB::table('messages')->insert([
                    'conversation_id' => $convId,
                    'sender_id'       => $user->id,
                    'sender_type'     => 'firm',
                    'message'         => $message,
                    'is_read'         => false,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);

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

                if (!MessagingHelper::acceptsDirectMessages($firmId)) {
                    DB::rollBack();
                    return response()->json(['status' => false, 'message' => 'This firm is not accepting direct messages'], 403);
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

                DB::table('messages')->insert([
                    'conversation_id' => $convId,
                    'sender_id'       => $user->id,
                    'sender_type'     => 'candidate',
                    'message'         => $message,
                    'is_read'         => false,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);

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

            if ($conv->status === 'blocked' || $conv->status === 'ignored') {
                return response()->json(['status' => false, 'message' => 'Cannot send message to this conversation'], 403);
            }

            $message = trim($request->input('message', ''));
            if (empty($message) || mb_strlen($message) > 2000) {
                return response()->json(['status' => false, 'message' => 'Message must be between 1 and 2000 characters'], 422);
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

                    // If firm is replying to a candidate-initiated request, count the unlock
                    if ($user->role === 'firm') {
                        $firm = $this->firmProfileForUser($user->id);
                        if ($firm) {
                            MessagingHelper::incrementRequestsUnlocked($firm->id);
                        }
                    }
                }
            }

            DB::table('messages')->insert([
                'conversation_id' => $conv->id,
                'sender_id'       => $user->id,
                'sender_type'     => $senderType,
                'message'         => $message,
                'is_read'         => false,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            DB::table('conversations')
                ->where('id', $conv->id)
                ->update(['last_message_at' => now(), 'updated_at' => now()]);

            // Notify the other party
            $this->notifyPeer($user, $conv, $message, $senderType);

            DB::commit();

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
            $settings = MessagingHelper::getOrCreateSettings($firm->id);
            $limits   = MessagingHelper::getOrCreateLimits($firm->id);
            $isPremium = SubscriptionHelper::isPremiumFirm($firm->id);

            return response()->json([
                'status'  => true,
                'message' => 'Settings fetched',
                'data'    => [
                    'accept_direct_messages'          => (bool) $settings->accept_direct_messages,
                    'is_premium'                      => $isPremium,
                    'lifetime_conversations_started'  => (int) $limits->lifetime_conversations_started,
                    'lifetime_requests_unlocked'      => (int) $limits->lifetime_requests_unlocked,
                    'monthly_conversations_started'   => (int) $limits->monthly_conversations_started,
                    'free_conversation_limit'         => MessagingHelper::FREE_LIFETIME_CONVERSATIONS,
                    'free_request_unlock_limit'       => MessagingHelper::FREE_LIFETIME_REQUESTS_UNLOCKED,
                    'premium_monthly_limit'           => MessagingHelper::PREMIUM_MONTHLY_CONVERSATIONS,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('MessagingController@getSettings: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    public function updateSettings(Request $request)
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

            $validator = Validator::make($request->all(), [
                'accept_direct_messages' => 'required|boolean',
            ]);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
            }

            MessagingHelper::getOrCreateSettings($firm->id);
            DB::table('messaging_settings')
                ->where('firm_id', $firm->id)
                ->update([
                    'accept_direct_messages' => (bool) $request->input('accept_direct_messages'),
                    'updated_at'             => now(),
                ]);

            return response()->json(['status' => true, 'message' => 'Settings updated']);
        } catch (\Exception $e) {
            Log::error('MessagingController@updateSettings: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

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

            $accepts = MessagingHelper::acceptsDirectMessages($firmId);
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
                    'accept_direct_messages' => $accepts,
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
            $canStart = MessagingHelper::canFirmStartConversation($firm->id);

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
            if ($senderType === 'firm') {
                $firm      = $this->firmProfileForUser($sender->id);
                $firmName  = $firm ? $firm->firm_name : 'A firm';
                NotificationHelper::create(
                    $conv->candidate_id,
                    'New Message',
                    $firmName . ' sent you a message.'
                );
                $candidate = DB::table('users')->where('id', $conv->candidate_id)->first();
                if ($candidate) {
                    Mail::to($candidate->email)->queue(
                        new \App\Mail\NewMessageReplyMail($candidate, $firmName, $message)
                    );
                }
            } else {
                $firmProfile = DB::table('firm_profiles')->where('id', $conv->firm_id)->first();
                $firmUser    = $firmProfile ? DB::table('users')->where('id', $firmProfile->user_id)->first() : null;
                if ($firmUser) {
                    NotificationHelper::create(
                        $firmUser->id,
                        'New Message',
                        $sender->name . ' replied to your message.'
                    );
                    Mail::to($firmUser->email)->queue(
                        new \App\Mail\NewMessageReplyMail($firmUser, $sender->name, $message)
                    );
                }
            }
        } catch (\Exception $e) {
            Log::warning('Messaging peer notification failed: ' . $e->getMessage());
        }
    }
}
