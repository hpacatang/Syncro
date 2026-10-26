<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Media extends Model
{
    use HasFactory;

    protected $fillable = [
        'uploaded_by',
        'submission_id',
        'purpose',
        'storage_disk',
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}
