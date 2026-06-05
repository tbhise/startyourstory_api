<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    protected $table = 'email_logs';

    protected $fillable = [
        'recipient_email',
        'recipient_type',
        'email_purpose',
        'template_name',
        'sender_identity',
        'subject',
        'status',
        'error_message',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
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
}
