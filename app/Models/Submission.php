<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'original_caption', 'enhanced_caption', 'links', 'media_paths', 'status'
    ];

    // This ensures links and images are properly formatted as JSON arrays
    protected $casts = [
        'links' => 'array',
        'media_paths' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all feedback for this submission
     */
    public function feedback()
    {
        return $this->hasMany(Feedback::class);
    }
}