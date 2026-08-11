<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Repository extends Model
{
    protected $fillable = [
        'repo_id',
        'github_account_id',
        'owner',
        'name',
        'full_name',
        'description',
        'language',
        'stars',
        'forks',
        'open_issues',
        'watchers',
        'size',
        'html_url',
        'clone_url',
        'default_branch',
        'topics',
        'license',
        'is_private',
        'is_fork',
        'is_archived',
        'pushed_at',
        'github_created_at',
        'readme',

        // AI analysis fields
        'ai_analysis',
        'ai_analyzed_at',
        'analysis_status',
        'analysis_started_at',

        // User preferences
        'is_featured',
        'is_pinned',
    ];

    protected $casts = [
        'ai_analysis'      => 'array',
        'ai_analyzed_at'   => 'datetime',
        'analysis_started_at' => 'datetime',
        'pushed_at'        => 'datetime',
        'github_created_at'=> 'datetime',
        'topics'           => 'array',
        'is_private'       => 'boolean',
        'is_fork'          => 'boolean',
        'is_archived'      => 'boolean',
        'is_featured'      => 'boolean',
        'is_pinned'        => 'boolean',
        'stars'            => 'integer',
        'forks'            => 'integer',
        'open_issues'      => 'integer',
        'watchers'         => 'integer',
        'size'             => 'integer',
    ];

    // ─── Relationships ──────────────────────────────────────────────────────────

    public function githubAccount(): BelongsTo
    {
        return $this->belongsTo(GithubAccount::class);
    }

    public function analyses(): HasMany
    {
        return $this->hasMany(Analysis::class);
    }

    // ─── Scopes ─────────────────────────────────────────────────────────────────

    /**
     * Scope repositories to the given user (via their GitHub account).
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->whereHas('githubAccount', function (Builder $q) use ($user) {
            $q->where('user_id', $user->id);
        });
    }

    public function scopeAnalyzed(Builder $query): Builder
    {
        return $query->whereNotNull('ai_analyzed_at');
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_private', false);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopePinned(Builder $query): Builder
    {
        return $query->where('is_pinned', true);
    }

    // ─── Helpers ────────────────────────────────────────────────────────────────

    public function isAnalyzed(): bool
    {
        return $this->ai_analyzed_at !== null;
    }

    public function isAnalyzing(): bool
    {
        return $this->analysis_status === 'processing';
    }

    public function hasFailed(): bool
    {
        return $this->analysis_status === 'failed';
    }

    /**
     * A repository marked as processing for a long time is considered stale
     * (e.g. the queue worker died mid-run) and may be re-analyzed.
     */
    public function isStaleProcessing(): bool
    {
        if ($this->analysis_status !== 'processing') {
            return false;
        }

        // Legacy rows stuck in "processing" without a start time are stale.
        if ($this->analysis_started_at === null) {
            return true;
        }

        return $this->analysis_started_at->lessThanOrEqualTo(now()->subMinutes(5));
    }

    /**
     * The repository's AI score, or null when it has not been analyzed.
     * Never falls back to a misleading 0 for an unanalyzed repository.
     */
    public function getScoreAttribute(): ?int
    {
        if (! $this->isAnalyzed() || ! is_array($this->ai_analysis)) {
            return null;
        }

        $score = $this->ai_analysis['score'] ?? null;

        return is_numeric($score) ? (int) round((float) $score) : null;
    }

    public function getScoreColorAttribute(): string
    {
        return match (true) {
            $this->score >= 90 => 'text-emerald-600 dark:text-emerald-400',
            $this->score >= 75 => 'text-cyan-600 dark:text-cyan-400',
            $this->score >= 60 => 'text-blue-600 dark:text-blue-400',
            $this->score >= 40 => 'text-amber-600 dark:text-amber-400',
            default            => 'text-red-600 dark:text-red-400',
        };
    }

    public function getScoreGradientAttribute(): string
    {
        return match (true) {
            $this->score >= 90 => 'bg-emerald-500',
            $this->score >= 75 => 'bg-cyan-500',
            $this->score >= 60 => 'bg-blue-500',
            $this->score >= 40 => 'bg-amber-500',
            default            => 'bg-red-500',
        };
    }
}
