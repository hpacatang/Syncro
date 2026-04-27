<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'original_caption', 'enhanced_caption', 'links', 'media_paths', 'status',
        'enhanced_by', 'enhanced_at', 'workflow_status', 'org_review_notes', 'pair_feedback'
    ];

    // This ensures links and images are properly formatted as JSON arrays
    protected $casts = [
        'links' => 'array',
        'media_paths' => 'array',
        'enhanced_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function enhancer()
    {
        return $this->belongsTo(User::class, 'enhanced_by');
    }

    /**
     * Get all feedback for this submission
     */
    public function feedback()
    {
        return $this->hasMany(Feedback::class);
    }
}