<?php

namespace App\Services\Messaging;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Chat message attachments (ADDITIVE layer on live messaging).
 *
 * Rules (V1): max 5 files per message, any mix of images + at most 1 PDF.
 * Images: jpg/jpeg/png/webp, ≤5 MB each — stored in their ORIGINAL format,
 * untouched (users send document-like images: marksheets, offer letters —
 * format conversion would hurt readability/download UX). PDFs: ≤10 MB.
 *
 * Storage: the private `local` disk (storage/app/private), path
 * message-attachments/{conversation}/{40-char random}.{ext}. Paths never
 * leave the server — clients only ever see attachment ids, served through
 * the authenticated GET /messaging/attachments/{id} endpoint.
 *
 * Security without re-encoding: content-sniffed MIME (finfo), getimagesize()
 * sanity check for images, %PDF- magic bytes for PDFs, random filenames with
 * server-derived extensions, nosniff on serve.
 */
class MessageAttachmentService
{
    public const MAX_FILES       = 5;
    public const MAX_PDFS        = 5;
    public const MAX_IMAGE_BYTES = 5 * 1024 * 1024;   // 5 MB
    public const MAX_PDF_BYTES   = 10 * 1024 * 1024;  // 10 MB

    private const IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/webp'];
    private const DISK        = 'local'; // private: storage/app/private

    /**
     * Cross-file validation beyond Laravel's per-file rules.
     * Returns a user-facing error string, or null when the set is valid.
     *
     * @param UploadedFile[] $files
     */
    public static function validateSet(array $files): ?string
    {
        if (count($files) > self::MAX_FILES) {
            return 'You can attach at most ' . self::MAX_FILES . ' files per message.';
        }

        $pdfCount = 0;
        foreach ($files as $file) {
            if (!$file instanceof UploadedFile || !$file->isValid()) {
                return 'One of the attachments failed to upload. Please try again.';
            }

            $mime = (string) $file->getMimeType(); // content-sniffed (finfo)

            if ($mime === 'application/pdf') {
                $pdfCount++;
                if ($file->getSize() > self::MAX_PDF_BYTES) {
                    return 'PDF attachments must be 10 MB or smaller.';
                }
                // Magic-byte check. Per the PDF spec the %PDF- signature may be
                // preceded by up to 1024 bytes of junk (some exporters/scanners
                // do this), so search the first 1 KB rather than byte 0 only.
                $head = (string) file_get_contents($file->getRealPath(), false, null, 0, 1024);
                if (!str_contains($head, '%PDF-')) {
                    Log::debug('Attachment PDF rejected: no %PDF- signature in first 1KB', [
                        'name'      => $file->getClientOriginalName(),
                        'head_hex'  => bin2hex(substr($head, 0, 16)),
                    ]);
                    return 'The PDF file appears to be invalid.';
                }
            } elseif (in_array($mime, self::IMAGE_MIMES, true)) {
                if ($file->getSize() > self::MAX_IMAGE_BYTES) {
                    return 'Images must be 5 MB or smaller.';
                }
                // Decodable-image sanity check (rejects polyglots/corrupt files).
                if (@getimagesize($file->getRealPath()) === false) {
                    return 'One of the images appears to be invalid.';
                }
            } else {
                return 'Only JPG, PNG, WebP images and PDF files are allowed.';
            }
        }

        if ($pdfCount > self::MAX_PDFS) {
            return 'You can attach at most ' . self::MAX_PDFS . ' PDF' . (self::MAX_PDFS > 1 ? 's' : '') . ' per message.';
        }

        return null;
    }

    /**
     * Persist the files (original format, untouched) + insert their rows.
     * Call inside the sendMessage DB transaction; on ANY failure it removes
     * whatever files it already wrote, then rethrows so the caller rolls back.
     *
     * @param  UploadedFile[] $files
     * @return array[] client-safe payloads: id, type, original_name, mime_type,
     *                 size_bytes, width, height (never file_path)
     */
    public static function store(array $files, int $conversationId, int $messageId): array
    {
        $storedPaths = [];
        $payloads    = [];

        try {
            foreach ($files as $file) {
                $mime    = (string) $file->getMimeType();
                $isPdf   = $mime === 'application/pdf';
                // Server-derived extension (from sniffed content), never the client name.
                $ext     = $isPdf ? 'pdf' : ($file->guessExtension() ?: 'bin');
                $name    = Str::random(40) . '.' . $ext;
                $dir     = 'message-attachments/' . $conversationId;

                $path = $file->storeAs($dir, $name, self::DISK);
                if ($path === false) {
                    throw new \RuntimeException('Attachment write failed');
                }
                $storedPaths[] = $path;

                $width = $height = null;
                if (!$isPdf) {
                    $dims = @getimagesize(Storage::disk(self::DISK)->path($path));
                    if (is_array($dims)) {
                        $width  = min((int) $dims[0], 65535);
                        $height = min((int) $dims[1], 65535);
                    }
                }

                $id = DB::table('message_attachments')->insertGetId([
                    'message_id'      => $messageId,
                    'conversation_id' => $conversationId,
                    'type'            => $isPdf ? 'pdf' : 'image',
                    'file_path'       => $path,
                    'original_name'   => mb_substr($file->getClientOriginalName() ?: ('attachment.' . $ext), 0, 255),
                    'mime_type'       => $mime,
                    'size_bytes'      => (int) $file->getSize(),
                    'width'           => $width,
                    'height'          => $height,
                    'created_at'      => now(),
                ]);

                $payloads[] = [
                    'id'            => $id,
                    'type'          => $isPdf ? 'pdf' : 'image',
                    'original_name' => mb_substr($file->getClientOriginalName() ?: ('attachment.' . $ext), 0, 255),
                    'mime_type'     => $mime,
                    'size_bytes'    => (int) $file->getSize(),
                    'width'         => $width,
                    'height'        => $height,
                ];
            }

            return $payloads;
        } catch (\Throwable $e) {
            // File writes are not transactional — clean up before the DB rollback.
            foreach ($storedPaths as $p) {
                try {
                    Storage::disk(self::DISK)->delete($p);
                } catch (\Throwable $cleanupErr) {
                    Log::warning('Attachment cleanup failed: ' . $cleanupErr->getMessage());
                }
            }
            throw $e;
        }
    }

    /**
     * Client-safe attachment payloads for a set of message ids, keyed by
     * message_id. Used by getMessages to decorate the raw rows.
     *
     * @param  int[] $messageIds
     * @return array<int, array[]>
     */
    public static function forMessages(array $messageIds): array
    {
        if (empty($messageIds)) return [];

        $grouped = [];
        $rows = DB::table('message_attachments')
            ->whereIn('message_id', $messageIds)
            ->orderBy('id')
            ->get();

        foreach ($rows as $row) {
            $grouped[(int) $row->message_id][] = [
                'id'            => (int) $row->id,
                'type'          => $row->type,
                'original_name' => $row->original_name,
                'mime_type'     => $row->mime_type,
                'size_bytes'    => (int) $row->size_bytes,
                'width'         => $row->width !== null ? (int) $row->width : null,
                'height'        => $row->height !== null ? (int) $row->height : null,
            ];
        }

        return $grouped;
    }

    /**
     * Human summaries for an attachment set. Returns [bellPreview, pushBody]
     * used when the message has no text (e.g. "📷 3 Photos", "📷 Sent 3 images").
     *
     * @param array[] $payloads output of store()
     */
    public static function summaries(array $payloads): array
    {
        $images = count(array_filter($payloads, fn ($a) => $a['type'] === 'image'));
        $pdfs   = count($payloads) - $images;

        if ($pdfs > 0 && $images > 0) {
            $n = $images + $pdfs;
            return ["📎 {$n} attachments", "📎 Sent {$n} attachments"];
        }
        if ($pdfs > 0) {
            return [
                $pdfs === 1 ? '📄 ' . ($payloads[0]['original_name'] ?? 'Document') : "📄 {$pdfs} Documents",
                $pdfs === 1 ? '📄 Sent a document' : "📄 Sent {$pdfs} documents",
            ];
        }
        return [
            $images === 1 ? '📷 Photo' : "📷 {$images} Photos",
            $images === 1 ? '📷 Sent an image' : "📷 Sent {$images} images",
        ];
    }
}
