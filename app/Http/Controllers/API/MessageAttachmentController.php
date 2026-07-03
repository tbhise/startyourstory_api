<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Authenticated streaming of chat attachments.
 *
 * GET /messaging/attachments/{id}            → inline (images render, PDFs open)
 * GET /messaging/attachments/{id}?download=1 → forced download
 *
 * Files live on the private `local` disk (storage/app/private) — no public
 * URL exists. Every request re-verifies that the logged-in user is a
 * participant of the attachment's conversation (same ownership semantics as
 * MessagingController::userOwnsConversation).
 */
class MessageAttachmentController extends Controller
{
    private const DISK = 'local';

    public function show(Request $request, int $id)
    {
        try {
            $user = $request->attributes->get('auth_user');
            if (!$user) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }

            $att = DB::table('message_attachments')->where('id', $id)->first();
            if (!$att) {
                return response()->json(['status' => false, 'message' => 'Attachment not found'], 404);
            }

            $conv = DB::table('conversations')->where('id', $att->conversation_id)->first();
            if (!$conv || !$this->userOwnsConversation($user, $conv)) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
            }

            // Defense-in-depth: path is server-controlled, but never serve
            // anything suspicious anyway.
            $rel = (string) $att->file_path;
            if (str_contains($rel, '..') || str_contains($rel, "\0")) {
                return response()->json(['status' => false, 'message' => 'Invalid file'], 422);
            }

            if (!Storage::disk(self::DISK)->exists($rel)) {
                return response()->json(['status' => false, 'message' => 'File missing'], 404);
            }

            $absolute    = Storage::disk(self::DISK)->path($rel);
            $disposition = $request->boolean('download') ? 'attachment' : 'inline';
            // RFC 6266-safe filename (strip quotes/control chars).
            $safeName    = preg_replace('/[^\w\s.\-()\[\]]+/u', '_', (string) $att->original_name) ?: 'attachment';

            return response()->file($absolute, [
                'Content-Type'            => $att->mime_type,
                'Content-Disposition'     => $disposition . '; filename="' . $safeName . '"',
                'X-Content-Type-Options'  => 'nosniff',
                'Cache-Control'           => 'private, max-age=86400',
            ]);
        } catch (\Throwable $e) {
            Log::error('MessageAttachmentController@show: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /** Mirrors MessagingController::userOwnsConversation (kept private there). */
    private function userOwnsConversation(object $user, object $conv): bool
    {
        if ($user->role === 'student') {
            return (int) $conv->candidate_id === (int) $user->id;
        }
        if ($user->role === 'firm') {
            $firm = DB::table('firm_profiles')->where('user_id', $user->id)->first();
            return $firm && (int) $conv->firm_id === (int) $firm->id;
        }
        return false;
    }
}
