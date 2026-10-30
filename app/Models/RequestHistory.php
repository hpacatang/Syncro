<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestHistory extends Model
{
    use HasFactory;

    public $timestamps = false; // We use created_at immutable audit timestamp

    protected $table = 'request_histories';

    protected $fillable = [
        'submission_id',
        'actor_id',
        'actor_role',
        'action',
        'from_status',
        'to_status',
        'reason',
        'notes',
        'metadata',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'metadata'   => 'array',
        'created_at' => 'datetime',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * Immutable recording helper for strict audit trails.
     */
    public static function record(
        Submission $submission,
        User $actor,
        string $action,
        ?string $fromStatus,
        string $toStatus,
        ?string $reason = null,
        ?string $notes = null,
        array $metadata = []
    ): self {
        return self::create([
            'submission_id' => $submission->id,
            'actor_id'      => $actor->id,
            'actor_role'    => $actor->role,
            'action'        => $action,
            'from_status'   => $fromStatus,
            'to_status'     => $toStatus,
            'reason'        => $reason,
            'notes'         => $notes,
            'metadata'      => $metadata,
            'ip_address'    => request()->ip(),
            'user_agent'    => request()->userAgent(),
            'created_at'    => now(),
        ]);
    }
}
