<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class DisputeAttachment extends Model
{
    protected $fillable = [
        'dispute_id', 'uploader_id', 'uploader_role',
        'filename', 'path', 'mime_type', 'size',
    ];

    public function dispute(): BelongsTo
    {
        return $this->belongsTo(Dispute::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }

    public function getUrlAttribute(): string
    {
        return Storage::url($this->path);
    }

    public function getIsImageAttribute(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function getSizeFormattedAttribute(): string
    {
        if ($this->size < 1024)       return $this->size . ' o';
        if ($this->size < 1048576)    return round($this->size / 1024, 1) . ' Ko';
        return round($this->size / 1048576, 1) . ' Mo';
    }
}