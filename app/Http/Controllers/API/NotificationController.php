<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{

    public function markAsRead(Request $request)
    {

        try {

            $authUser =
                $request->attributes->get('auth_user');
            $type = $request->type;
            $mode = $request->mode;
            $id = $request->id;


            if ($type === 'notification') {
                $query =
                    DB::table('notifications')
                    ->where('user_id', $authUser->id);
                if ($mode === 'single') {
                    $query->where('id', $id);
                }
                $query->update([
                    'is_read' => true
                ]);
            }



            if ($type === 'recruiter_action') {
                // Scope to the rows the student actually sees in the feed — the
                // same filter the list + unread-count queries use. Without it,
                // "mark all read" also flipped firm-visible tracking rows.
                $query =
                    DB::table('recruiter_actions')
                    ->where('student_id', $authUser->id)
                    ->where('visible_to', 'student');
                if ($mode === 'single') {
                    $query->where('id', $id);
                }
                $query->update([
                    'is_read' => true
                ]);
            }

            return response()->json([
                'status' => true,
                'message' => 'Read status updated'
            ]);
        } catch (\Exception $e) {
            Log::error(
                'Update Read Status Error',
                [
                    'message' =>
                    $e->getMessage(),
                ]
            );

            return response()->json([
                'status' => false,
                'message' => 'Unexpected server error'
            ]);
        }
    }
}
