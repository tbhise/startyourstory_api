<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    protected $table = 'email_logs';

    protected $fillable = [
        'campaign_id',
        'recipient_email',
        'recipient_type',
        'email_purpose',
        'template_name',
        'sender_identity',
        'subject',
        'status',
        'error_message',
        'sent_at',
        'click_count',
        'clicked_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at'    => 'datetime',
            'clicked_at' => 'datetime',
        ];
    }

    public function markSent(): void
    {
        $this->update(['status' => 'sent', 'sent_at' => now()]);
    }

    public function markFailed(string $error): void
    {
        $this->update(['status' => 'failed', 'error_message' => $error]);
    }

    /**
     * Record a CTA click: always bump click_count; stamp clicked_at on the
     * first click only. Direct save() (not increment()) so both columns persist
     * in one write.
     */
    public function registerClick(): void
    {
        $this->click_count = (int) $this->click_count + 1;
        if (is_null($this->clicked_at)) {
            $this->clicked_at = now();
        }
        $this->save();
    }
}
