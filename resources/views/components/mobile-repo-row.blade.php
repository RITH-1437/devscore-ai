@props(['repository'])

@php
$repo = $repository;
@endphp

<a href="{{ route('repositories.show', $repo) }}"
   class="flex flex-col gap-3 border-b border-[var(--border-color)] px-4 py-4 transition hover:bg-[var(--bg-muted)] last:border-b-0 sm:px-5">
    <div class="flex items-start justify-between gap-3 min-w-0">
        <div class="min-w-0 flex-1">
            <p class="break-anywhere text-sm font-semibold repo-name text-[var(--text-primary)]">{{ $repo->name }}</p>
            @if($repo->description)
            <p class="mt-0.5 line-clamp-2 text-xs text-[var(--text-muted)]">{{ $repo->description }}</p>
            @endif
        </div>
        @if($repo->isAnalyzed())
        <span class="shrink-0 text-xs font-bold {{ $repo->score_color }}">{{ $repo->score }}/100</span>
        @elseif($repo->isAnalyzing())
        <span class="shrink-0 text-xs text-blue-600 dark:text-blue-400">Analyzing…</span>
        @else
        <span class="shrink-0 text-xs text-[var(--text-muted)]">Not analyzed</span>
        @endif
    </div>

    <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-[var(--text-secondary)]">
        @if($repo->language)
        @php $langColor = \App\Helpers\LanguageColorHelper::getLanguageColor($repo->language); @endphp
        <span class="inline-flex items-center gap-1.5">
            <span class="h-2 w-2 rounded-full shrink-0" style="background: {{ $langColor }};"></span>
            {{ $repo->language }}
        </span>
        @endif
        <span class="inline-flex items-center gap-1">
            <svg class="h-3.5 w-3.5 text-amber-400" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
            </svg>
            {{ $repo->stars }}
        </span>
        @if($repo->pushed_at)
        <span class="text-[var(--text-muted)]">Updated {{ $repo->pushed_at->diffForHumans() }}</span>
        @endif
    </div>
</a>
