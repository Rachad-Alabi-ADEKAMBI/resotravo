<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminMailLog extends Model
{
    protected $fillable = [
        'sent_by',
        'subject',
        'body',
        'recipient_mode',
        'roles',
        'user_ids',
        'manual_recipients',
        'recipients_count',
        'sent_count',
        'failed',
        'attachments',
    ];

    protected function casts(): array
    {
        return [
            'roles' => 'array',
            'user_ids' => 'array',
            'manual_recipients' => 'array',
            'failed' => 'array',
            'attachments' => 'array',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
