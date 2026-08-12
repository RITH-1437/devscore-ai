@props(['repository'])

@php
$repo = $repository;
$analysis = is_array($repo->ai_analysis) ? $repo->ai_analysis : [];
$langColor = $repo->language
    ? \App\Helpers\LanguageColorHelper::getLanguageColor($repo->language)
    : 'var(--lang-default)';
@endphp

<a href="{{ route('repositories.show', $repo) }}"
   class="flex flex-col gap-3 border-b border-[var(--border-color)] px-4 py-4 transition hover:bg-[var(--bg-muted)] last:border-b-0 sm:px-5">
    <div class="flex items-start justify-between gap-3 min-w-0">
        <div class="min-w-0 flex-1">
            <p class="break-anywhere text-sm font-semibold text-[var(--text-primary)]">{{ $repo->name }}</p>
            @if($repo->language)
            <p class="mt-1 inline-flex items-center gap-1.5 text-xs text-[var(--text-muted)]">
                <span class="h-2 w-2 rounded-full shrink-0" style="background: {{ $langColor }};"></span>
                {{ $repo->language }}
            </p>
            @endif
        </div>
        <span class="shrink-0 text-sm font-bold {{ $repo->score_color }}">{{ $repo->score }}/100</span>
    </div>

    <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-xs">
        <div>
            <dt class="text-[var(--text-muted)]">Difficulty</dt>
            <dd class="font-medium text-[var(--text-primary)]">{{ ucfirst($analysis['difficulty'] ?? '—') }}</dd>
        </div>
        <div>
            <dt class="text-[var(--text-muted)]">Level</dt>
            <dd class="font-medium text-[var(--text-primary)]">{{ ucfirst($analysis['portfolio_level'] ?? '—') }}</dd>
        </div>
        <div>
            <dt class="text-[var(--text-muted)]">Recruiter</dt>
            <dd class="font-medium text-[var(--text-primary)]">{{ ($analysis['recruiter_rating'] ?? null) !== null ? ($analysis['recruiter_rating'] . '/10') : '—' }}</dd>
        </div>
        <div>
            <dt class="text-[var(--text-muted)]">Hiring</dt>
            <dd class="font-medium text-[var(--text-primary)]">{{ ($analysis['hiring_probability'] ?? null) !== null ? ($analysis['hiring_probability'] . '%') : '—' }}</dd>
        </div>
    </dl>
</a>
