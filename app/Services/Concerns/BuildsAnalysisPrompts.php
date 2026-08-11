<?php

declare(strict_types=1);

namespace App\Services\Concerns;

use App\Models\Repository;

trait BuildsAnalysisPrompts
{
    protected int $maxReadmeChars;
    protected int $maxDescriptionChars;
    protected int $maxPromptChars;

    protected function buildRepositoryPrompt(Repository $repository): string
    {
        $topics = implode(', ', $repository->topics ?? []);
        $readme = mb_substr($repository->readme ?? 'Not available', 0, $this->maxReadmeChars);
        $description = mb_substr($repository->description ?? 'No description', 0, $this->maxDescriptionChars);
        $license = $repository->license ?? 'Not specified';
        $lastPushed = $repository->pushed_at?->toDateString() ?? 'Unknown';
        $createdAt = $repository->github_created_at?->toDateString() ?? 'Unknown';

        $prompt = <<<PROMPT
You are a senior software engineer, technical recruiter, and portfolio analyst reviewing a GitHub repository.

Analyze this repository and return ONLY valid JSON. No markdown code blocks, no explanations — just raw JSON.
Keep each array field to 2-3 short bullet points (max 15 words each). Keep the entire JSON under 4000 characters.

Repository Details:
- Name: {$repository->name}
- Full Name: {$repository->full_name}
- Description: {$description}
- Primary Language: {$repository->language}
- Stars: {$repository->stars}
- Forks: {$repository->forks}
- Open Issues: {$repository->open_issues}
- Watchers: {$repository->watchers}
- Size (KB): {$repository->size}
- Topics: {$topics}
- License: {$license}
- Is Fork: {$this->formatBool($repository->is_fork)}
- Is Archived: {$this->formatBool($repository->is_archived)}
- Last Pushed: {$lastPushed}
- Created: {$createdAt}

README (truncated):
{$readme}

Return this exact JSON structure. All fields are required:

{
  "score": <integer 0-100>,
  "difficulty": "<beginner|intermediate|advanced|expert>",
  "portfolio_level": "<junior|mid|senior|staff|principal>",
  "recruiter_rating": <integer 1-10>,
  "estimated_experience": "<e.g., 0-1 years, 1-3 years, 3-5 years, 5+ years>",
  "hiring_probability": <integer 0-100>,
  "market_readiness": "<not-ready|emerging|ready|production-grade>",
  "strengths": ["<string>"],
  "weaknesses": ["<string>"],
  "recommendations": ["<string>"],
  "architecture_review": ["<string>"],
  "security_review": ["<string>"],
  "performance_review": ["<string>"],
  "code_style_review": ["<string>"],
  "missing_features": ["<string>"],
  "resume_suggestions": ["<string>"],
  "cv_suggestions": ["<string>"],
  "linkedin_suggestions": ["<string>"],
  "interview_questions": ["<string>"],
  "best_companies": ["<string>"],
  "improvement_roadmap": ["<string>"]
}

CRITICAL: Return ONLY the JSON object. No text before or after.
PROMPT;

        if (strlen($prompt) > $this->maxPromptChars) {
            $prompt = mb_substr($prompt, 0, $this->maxPromptChars) . "\n\n[Prompt truncated for size limits.]";
        }

        return $prompt;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Repository>  $repositories
     */
    protected function buildPortfolioPrompt(\Illuminate\Support\Collection $repositories): string
    {
        $repoList = $repositories->map(function (Repository $r) {
            return "- {$r->name} ({$r->language}, ⭐{$r->stars}): " . ($r->description ?? 'No description');
        })->implode("\n");

        $totalRepos = $repositories->count();
        $totalStars = $repositories->sum('stars');
        $totalForks = $repositories->sum('forks');
        $languages = $repositories->pluck('language')->filter()->unique()->implode(', ');

        return <<<PROMPT
You are a senior software engineer and technical recruiter reviewing a developer's complete GitHub portfolio.

Analyze the portfolio and return ONLY valid JSON. No markdown, no code blocks, no explanations.

Portfolio Summary:
- Total Repositories: {$totalRepos}
- Total Stars: {$totalStars}
- Total Forks: {$totalForks}
- Languages Used: {$languages}

Repositories:
{$repoList}

Return this exact JSON structure (all fields required):

{
  "score": <integer 0-100>,
  "portfolio_level": "<junior|mid|senior|staff|principal>",
  "estimated_experience": "<e.g., 0-1 years, 1-3 years, 3-5 years, 5+ years>",
  "hiring_probability": <integer 0-100>,
  "market_readiness": "<not-ready|emerging|ready|production-grade>",
  "recruiter_rating": <integer 1-10>,
  "strengths": ["<string>", ...],
  "weaknesses": ["<string>", ...],
  "recommendations": ["<string>", ...],
  "missing_features": ["<string>", ...],
  "resume_suggestions": ["<string>", ...],
  "cv_suggestions": ["<string>", ...],
  "linkedin_suggestions": ["<string>", ...],
  "best_companies": ["<string>", ...],
  "improvement_roadmap": ["<string>", ...]
}

CRITICAL: Return ONLY the JSON object.
PROMPT;
    }

    protected function formatBool(?bool $value): string
    {
        return $value ? 'Yes' : 'No';
    }
}
