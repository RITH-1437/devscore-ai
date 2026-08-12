@props(['repository'])

@php
$repo = $repository;
$statusClass = $repo->isAnalyzed()
    ? 'border-[var(--success-soft-border)] bg-[var(--success-soft)] text-[var(--success)]'
    : ($repo->isAnalyzing()
        ? 'border-[var(--info-soft-border)] bg-[var(--info-soft)] text-[var(--info)]'
        : ($repo->hasFailed()
            ? 'border-[var(--danger-soft-border)] bg-[var(--danger-soft)] text-[var(--danger)]'
            : 'border-[var(--border-color)] bg-[var(--bg-muted)] text-[var(--text-muted)]'));
$statusLabel = $repo->isAnalyzed()
    ? $repo->score . '/100'
    : ($repo->isAnalyzing()
        ? 'Analyzing'
        : ($repo->hasFailed() ? 'Failed' : 'Not analyzed'));
@endphp

<a href="{{ route('repositories.show', $repo) }}"
   class="panel group relative flex min-h-44 min-w-0 flex-col p-4 transition hover:border-[var(--border-strong)] sm:min-h-52 sm:p-5"
   aria-label="Open {{ $repo->name }} repository details">

    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <h3 class="repo-name break-anywhere text-[var(--text-primary)] transition-colors group-hover:text-[var(--primary)]">
                {{ $repo->name }}
            </h3>
            @if($repo->full_name)
            <p class="mt-0.5 break-anywhere font-mono text-[11px] text-[var(--text-muted)]">{{ $repo->full_name }}</p>
            @endif
        </div>

        <span class="shrink-0 rounded-md border px-2 py-0.5 text-[11px] font-semibold tabular-nums {{ $statusClass }}" aria-label="Analysis status: {{ $statusLabel }}">
            {{ $statusLabel }}
        </span>
    </div>

    <div class="mt-3 flex flex-wrap gap-1.5">
        @if($repo->language)
        @php
        $langColor = \App\Helpers\LanguageColorHelper::getLanguageColor($repo->language);
        @endphp
        <span class="inline-flex items-center gap-1.5 rounded-md border border-[var(--border-color)] bg-[var(--bg-muted)] px-2 py-0.5 text-[11px] font-medium text-[var(--text-secondary)]">
            <span class="h-1.5 w-1.5 rounded-full" style="background: {{ $langColor }};"></span>
            {{ $repo->language }}
        </span>
        @endif

        @if($repo->is_pinned)
        <span class="inline-flex items-center rounded-md border border-[var(--warning-soft-border)] bg-[var(--warning-soft)] px-2 py-0.5 text-[11px] font-medium text-[var(--warning)]">Pinned</span>
        @endif

        @if($repo->is_private)
        <span class="inline-flex items-center rounded-md border border-[var(--border-color)] bg-[var(--bg-muted)] px-2 py-0.5 text-[11px] font-medium text-[var(--text-secondary)]">Private</span>
        @endif

        @if($repo->is_fork)
        <span class="inline-flex items-center rounded-md border border-[var(--border-color)] bg-[var(--bg-muted)] px-2 py-0.5 text-[11px] font-medium text-[var(--text-secondary)]">Fork</span>
        @endif

        @if($repo->is_archived)
        <span class="inline-flex items-center rounded-md border border-[var(--border-color)] bg-[var(--bg-muted)] px-2 py-0.5 text-[11px] font-medium text-[var(--text-muted)]">Archived</span>
        @endif
    </div>

    <p class="mt-3 line-clamp-2 min-h-10 text-sm leading-relaxed text-[var(--text-secondary)]">
        {{ $repo->description ?: 'No description provided.' }}
    </p>

    <div class="mt-auto flex items-end justify-between gap-3 border-t border-[var(--border-color)] pt-3">
        <div class="flex flex-wrap items-center gap-2 text-[11px] text-[var(--text-muted)]">
            <span class="inline-flex items-center gap-1 tabular-nums">
                <svg class="h-3 w-3 text-[var(--warning)]" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
                {{ number_format($repo->stars) }}
            </span>
            <span class="inline-flex items-center gap-1 tabular-nums">
                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/>
                </svg>
                {{ number_format($repo->forks) }}
            </span>
        </div>

        <span class="text-xs font-medium text-[var(--text-muted)] transition group-hover:text-[var(--primary)]">View →</span>
    </div>
</a>
