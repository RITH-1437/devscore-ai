@props(['repository'])

@php
$repo = $repository;
$statusClass = $repo->isAnalyzed()
    ? 'border-emerald-500/25 bg-emerald-500/10 text-emerald-600 dark:text-emerald-300'
    : ($repo->isAnalyzing()
        ? 'border-blue-500/25 bg-blue-500/10 text-blue-600 dark:text-blue-300'
        : ($repo->hasFailed()
            ? 'border-red-500/25 bg-red-500/10 text-red-600 dark:text-red-300'
            : 'border-[var(--border-color)] bg-[var(--bg-muted)] text-[var(--text-muted)]'));
$statusLabel = $repo->isAnalyzed()
    ? $repo->score . '/100'
    : ($repo->isAnalyzing()
        ? 'Analyzing'
        : ($repo->hasFailed() ? 'Failed' : 'Not analyzed'));
@endphp

<a href="{{ route('repositories.show', $repo) }}"
   class="group relative flex min-h-56 min-w-0 flex-col overflow-hidden rounded-2xl border border-[var(--border-color)] bg-[var(--bg-card)] p-5 shadow-[var(--shadow-card)] transition-all duration-300 hover:-translate-y-0.5 hover:border-[var(--primary)]/40"
   aria-label="Open {{ $repo->name }} repository details">
    <div class="absolute inset-x-0 top-0 h-px opacity-0 transition-opacity duration-300 group-hover:opacity-100" style="background: var(--primary);"></div>

    {{-- Header --}}
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <h3 class="truncate text-base font-black tracking-tight text-[var(--text-primary)] transition-colors" style="--tw-text-opacity:1;">
                {{ $repo->name }}
            </h3>
            @if($repo->full_name)
            <p class="mt-0.5 truncate text-xs text-[var(--text-muted)]">{{ $repo->full_name }}</p>
            @endif
        </div>

        <span class="shrink-0 rounded-lg border px-2.5 py-1 text-xs font-bold {{ $statusClass }}" aria-label="Analysis status: {{ $statusLabel }}">
            {{ $statusLabel }}
        </span>
    </div>

    <div class="mt-3 flex flex-wrap gap-2">
        @if($repo->language)
        @php
        $langColor = \App\Helpers\LanguageColorHelper::getLanguageColor($repo->language);
        @endphp
        <span class="inline-flex items-center gap-1.5 rounded-lg border border-[var(--border-color)] bg-[var(--bg-muted)] px-2.5 py-1 text-xs font-medium text-[var(--text-secondary)]">
            <span class="h-2 w-2 rounded-full" style="background: {{ $langColor }};"></span>
            {{ $repo->language }}
        </span>
        @endif

        @if($repo->is_pinned)
        <span class="inline-flex items-center rounded-lg border border-amber-500/25 bg-amber-500/10 px-2.5 py-1 text-xs font-medium text-amber-700 dark:text-amber-200">Pinned</span>
        @endif

        @if($repo->is_private)
        <span class="inline-flex items-center rounded-lg border border-rose-500/25 bg-rose-500/10 px-2.5 py-1 text-xs font-medium text-rose-700 dark:text-rose-200">Private</span>
        @endif

        @if($repo->is_fork)
        <span class="inline-flex items-center rounded-lg border border-blue-500/25 bg-blue-500/10 px-2.5 py-1 text-xs font-medium text-blue-700 dark:text-blue-200">Fork</span>
        @endif

        @if($repo->is_archived)
        <span class="inline-flex items-center rounded-lg border border-[var(--border-strong)] bg-[var(--bg-muted)] px-2.5 py-1 text-xs font-medium text-[var(--text-secondary)]">Archived</span>
        @endif
    </div>

    {{-- Description --}}
    <p class="mt-4 line-clamp-3 min-h-14 text-sm leading-6 text-[var(--text-secondary)]">
        {{ $repo->description ?: 'No description provided.' }}
    </p>

    {{-- Footer --}}
    <div class="mt-auto flex flex-col items-start justify-between gap-4 border-t border-[var(--border-color)] pt-4 sm:flex-row sm:items-end">
        <div class="flex flex-wrap items-center gap-3 text-xs text-[var(--text-muted)]">
            <span class="inline-flex items-center gap-1.5 rounded-lg bg-[var(--bg-muted)] px-2 py-1">
                <svg class="w-3.5 h-3.5 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
                {{ number_format($repo->stars) }}
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-lg bg-[var(--bg-muted)] px-2 py-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/>
                </svg>
                {{ number_format($repo->forks) }}
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-lg bg-[var(--bg-muted)] px-2 py-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 4v-4z"/>
                </svg>
                {{ number_format($repo->open_issues) }}
            </span>
        </div>

        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-[var(--border-color)] bg-[var(--bg-muted)] text-[var(--text-muted)] transition group-hover:border-[var(--primary)]/40 group-hover:text-[var(--primary)]">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17L17 7M17 7H8M17 7v9"/>
            </svg>
        </span>
    </div>
</a>
