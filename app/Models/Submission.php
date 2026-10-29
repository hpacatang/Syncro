<?php

namespace App\Models;

use App\Submission\Enums\SubmissionLifecycleStatus;
use App\Models\Scopes\OrgOwnedSubmissionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Submission extends Model
{
    use HasFactory;

    public const TYPE_SOCIAL_MEDIA = 'social_media';
    public const TYPE_WEBSITE_ARTICLE = 'website_article';
    public const SOCIAL_MEDIA_CONTENT_MAX = 2200;
    public const WEBSITE_ARTICLE_CONTENT_MIN = 100;
    public const WEBSITE_ARTICLE_CONTENT_MAX = 12000;

    protected $fillable = [
        'user_id', 'review_department_id', 'submission_type', 'article_title', 'enhanced_title',
        'original_caption', 'enhanced_caption', 'links', 'media_paths', 'status',
        'enhanced_by', 'enhanced_at', 'workflow_status', 'org_review_notes', 'pair_feedback'
    ];

    protected $casts = [
        'links' => 'array',
        'media_paths' => 'array',
        'enhanced_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new OrgOwnedSubmissionScope);

        static::creating(function (self $submission): void {
            $now = now();
            $submission->created_at ??= $now;
            $submission->updated_at ??= $now;
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewDepartment()
    {
        return $this->belongsTo(Department::class, 'review_department_id');
    }

    public function reviewOwnerLabel(): string
    {
        return $this->review_department_id === null
            ? 'PAIR'
            : ($this->reviewDepartment?->displayName() ?? 'PAIR');
    }

    public function isWebsiteArticle(): bool
    {
        return $this->submission_type === self::TYPE_WEBSITE_ARTICLE;
    }

    public function contentTypeLabel(): string
    {
        return $this->isWebsiteArticle() ? 'USJR website article' : 'Social media post';
    }

    public function enhancer()
    {
        return $this->belongsTo(User::class, 'enhanced_by');
    }

    public function feedback()
    {
        return $this->hasMany(Feedback::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class);
    }

    public function lifecycle(): SubmissionLifecycleStatus
    {
        return SubmissionLifecycleStatus::fromLegacy($this->workflow_status);
    }
}