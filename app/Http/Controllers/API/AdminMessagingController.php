<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminMessagingController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | GET /admin/messaging/conversations
    | Query: search, status, page
    |--------------------------------------------------------------------------
    */

    public function getConversations(Request $request)
    {
        try {
            $page    = max(1, (int) $request->get('page', 1));
            $perPage = 25;
            $search  = trim($request->get('search', ''));
            $status  = $request->get('status', '');

            $query = DB::table('conversations as c')
                ->join('users as cu', 'cu.id', '=', 'c.candidate_id')
                ->join('firm_profiles as fp', 'fp.id', '=', 'c.firm_id')
                ->select(
                    'c.*',
                    'cu.name as candidate_name',
                    'cu.email as candidate_email',
                    'fp.firm_name'
                );

            if ($status) {
                $query->where('c.status', $status);
            }

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('cu.name', 'like', "%{$search}%")
                        ->orWhere('fp.firm_name', 'like', "%{$search}%")
                        ->orWhere('cu.email', 'like', "%{$search}%");
                });
            }

            $total = (clone $query)->count();
            $rows  = $query
                ->orderByDesc('c.last_message_at')
                ->offset(($page - 1) * $perPage)
                ->limit($perPage)
                ->get();

            $list = $rows->map(function ($row) {
                $msgCount = DB::table('messages')->where('conversation_id', $row->id)->count();
                $req      = DB::table('message_requests')->where('conversation_id', $row->id)->first();
                return [
                    'id'              => $row->id,
                    'candidate_name'  => $row->candidate_name,
                    'candidate_email' => $row->candidate_email,
                    'firm_name'       => $row->firm_name,
                    'initiated_by'    => $row->initiated_by,
                    'status'          => $row->status,
                    'message_count'   => $msgCount,
                    'request_status'  => $req ? $req->status : null,
                    'last_message_at' => $row->last_message_at,
                    'created_at'      => $row->created_at,
                ];
            });

            return response()->json([
                'status'  => true,
                'message' => 'Conversations fetched',
                'data'    => [
                    'conversations' => $list,
                    'total'         => $total,
                    'page'          => $page,
                    'has_more'      => ($page * $perPage) < $total,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('AdminMessagingController@getConversations: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /admin/messaging/conversations/{id}/messages
    |--------------------------------------------------------------------------
    */

    public function getConversationMessages(Request $request, int $conversationId)
    {
        try {
            $conv = DB::table('conversations')->where('id', $conversationId)->first();
            if (!$conv) {
                return response()->json(['status' => false, 'message' => 'Not found'], 404);
            }

            $msgs = DB::table('messages')
                ->where('conversation_id', $conversationId)
                ->orderBy('created_at')
                ->get();

            $candidate = DB::table('users')->where('id', $conv->candidate_id)->first();
            $firm      = DB::table('firm_profiles')->where('id', $conv->firm_id)->first();

            return response()->json([
                'status'  => true,
                'message' => 'Messages fetched',
                'data'    => [
                    'conversation' => [
                        'id'           => $conv->id,
                        'status'       => $conv->status,
                        'initiated_by' => $conv->initiated_by,
                        'candidate'    => $candidate ? ['id' => $candidate->id, 'name' => $candidate->name, 'email' => $candidate->email] : null,
                        'firm'         => $firm ? ['id' => $firm->id, 'name' => $firm->firm_name] : null,
                        'created_at'   => $conv->created_at,
                    ],
                    'messages' => $msgs,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('AdminMessagingController@getConversationMessages: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /admin/messaging/conversations/{id}/block
    |--------------------------------------------------------------------------
    */

    public function blockConversation(Request $request, int $conversationId)
    {
        try {
            $conv = DB::table('conversations')->where('id', $conversationId)->first();
            if (!$conv) {
                return response()->json(['status' => false, 'message' => 'Not found'], 404);
            }
            DB::table('conversations')
                ->where('id', $conversationId)
                ->update(['status' => 'blocked', 'updated_at' => now()]);

            return response()->json(['status' => true, 'message' => 'Conversation blocked']);
        } catch (\Exception $e) {
            Log::error('AdminMessagingController@blockConversation: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /admin/messaging/conversations/{id}/unblock
    |--------------------------------------------------------------------------
    */

    public function unblockConversation(Request $request, int $conversationId)
    {
        try {
            $conv = DB::table('conversations')->where('id', $conversationId)->first();
            if (!$conv) {
                return response()->json(['status' => false, 'message' => 'Not found'], 404);
            }
            DB::table('conversations')
                ->where('id', $conversationId)
                ->update(['status' => 'active', 'updated_at' => now()]);

            return response()->json(['status' => true, 'message' => 'Conversation unblocked']);
        } catch (\Exception $e) {
            Log::error('AdminMessagingController@unblockConversation: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /admin/messaging/stats
    |--------------------------------------------------------------------------
    */

    public function getStats(Request $request)
    {
        try {
            $total        = DB::table('conversations')->count();
            $active       = DB::table('conversations')->where('status', 'active')->count();
            $pending      = DB::table('conversations')->where('status', 'pending')->count();
            $blocked      = DB::table('conversations')->where('status', 'blocked')->count();
            $totalMsgs    = DB::table('messages')->count();
            $todayMsgs    = DB::table('messages')->whereDate('created_at', today())->count();
            $firmsWithMsg = DB::table('messaging_settings')->where('accept_direct_messages', true)->count();

            return response()->json([
                'status'  => true,
                'message' => 'Stats fetched',
                'data'    => [
                    'total_conversations'      => $total,
                    'active_conversations'     => $active,
                    'pending_conversations'    => $pending,
                    'blocked_conversations'    => $blocked,
                    'total_messages'           => $totalMsgs,
                    'messages_today'           => $todayMsgs,
                    'firms_accepting_messages' => $firmsWithMsg,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('AdminMessagingController@getStats: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /admin/messaging/limits
    | View per-firm messaging limits
    |--------------------------------------------------------------------------
    */

    public function getLimits(Request $request)
    {
        try {
            $page    = max(1, (int) $request->get('page', 1));
            $perPage = 25;
            $search  = trim($request->get('search', ''));

            $query = DB::table('messaging_limits as ml')
                ->join('firm_profiles as fp', 'fp.id', '=', 'ml.firm_id')
                ->select('ml.*', 'fp.firm_name', 'fp.is_premium');

            if ($search !== '') {
                $query->where('fp.firm_name', 'like', "%{$search}%");
            }

            $total = (clone $query)->count();
            $rows  = $query
                ->orderByDesc('ml.lifetime_conversations_started')
                ->offset(($page - 1) * $perPage)
                ->limit($perPage)
                ->get();

            return response()->json([
                'status'  => true,
                'message' => 'Limits fetched',
                'data'    => [
                    'limits'   => $rows,
                    'total'    => $total,
                    'page'     => $page,
                    'has_more' => ($page * $perPage) < $total,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('AdminMessagingController@getLimits: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /admin/messaging/limits/{firmId}/reset-monthly
    |--------------------------------------------------------------------------
    */

    public function resetMonthlyLimit(Request $request, int $firmId)
    {
        try {
            $exists = DB::table('messaging_limits')->where('firm_id', $firmId)->exists();
            if (!$exists) {
                return response()->json(['status' => false, 'message' => 'Not found'], 404);
            }
            DB::table('messaging_limits')->where('firm_id', $firmId)->update([
                'monthly_conversations_started' => 0,
                'updated_at'                    => now(),
            ]);
            return response()->json(['status' => true, 'message' => 'Monthly limit reset']);
        } catch (\Exception $e) {
            Log::error('AdminMessagingController@resetMonthlyLimit: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }
}
