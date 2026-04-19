<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasFactory;

    protected $fillable = ['submission_id', 'user_id', 'message'];

    /**
     * Get the submission that owns this feedback
     */
    public function submission()
    {
        return $this->belongsTo(Submission::class);
    }

    /**
     * Get the user who created this feedback
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
