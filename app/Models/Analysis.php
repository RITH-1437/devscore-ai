<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Analysis extends Model
{
    protected $fillable = [
        'user_id',
        'repository_id',
        'model_used',
        'prompt_tokens',
        'completion_tokens',
        'score',
        'difficulty',
        'portfolio_level',
        'recruiter_rating',
        'estimated_experience',
        'hiring_probability',
        'market_readiness',
        'strengths',
        'weaknesses',
        'recommendations',
        'architecture_review',
        'security_review',
        'performance_review',
        'code_style_review',
        'missing_features',
        'resume_suggestions',
        'cv_suggestions',
        'linkedin_suggestions',
        'interview_questions',
        'best_companies',
        'improvement_roadmap',
        'raw_response',
        'status',
        'error_message',
    ];

    protected $casts = [
        'score'                 => 'integer',
        'prompt_tokens'         => 'integer',
        'completion_tokens'     => 'integer',
        'strengths'             => 'array',
        'weaknesses'            => 'array',
        'recommendations'       => 'array',
        'architecture_review'   => 'array',
        'security_review'       => 'array',
        'performance_review'    => 'array',
        'code_style_review'     => 'array',
        'missing_features'      => 'array',
        'resume_suggestions'    => 'array',
        'cv_suggestions'        => 'array',
        'linkedin_suggestions'  => 'array',
        'interview_questions'   => 'array',
        'best_companies'        => 'array',
        'improvement_roadmap'   => 'array',
    ];

    // ─── Relationships ──────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function repository(): BelongsTo
    {
        return $this->belongsTo(Repository::class);
    }

    // ─── Helpers ────────────────────────────────────────────────────────────────

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }
}
