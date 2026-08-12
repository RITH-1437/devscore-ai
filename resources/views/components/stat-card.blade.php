@props([
    'title',
    'value',
    'icon'  => null,
    'trend' => null,
    'color' => 'orange', // retained for callers; visual style is neutral
    'sub'   => null,
])

<div {{ $attributes->merge(['class' => 'stat-card panel relative min-w-0 p-4 sm:p-5']) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0 flex-1">
            <p class="section-kicker mb-2">{{ $title }}</p>
            <p class="stat-value text-2xl sm:text-[1.75rem] break-anywhere">{{ $value }}</p>
            @if($sub)
            <p class="mt-1 text-xs text-[var(--text-muted)] break-anywhere">{{ $sub }}</p>
            @endif

            @if($trend !== null)
            <div class="mt-2 flex items-center gap-1">
                @if($trend >= 0)
                <svg class="h-3.5 w-3.5 text-[var(--success)]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                </svg>
                <span class="text-xs text-[var(--success)]">+{{ $trend }}</span>
                @else
                <svg class="h-3.5 w-3.5 text-[var(--danger)]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
                <span class="text-xs text-[var(--danger)]">{{ $trend }}</span>
                @endif
            </div>
            @endif
        </div>

        @if($icon)
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-[var(--border-color)] bg-[var(--bg-muted)] text-[var(--text-secondary)]">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                {!! $icon !!}
            </svg>
        </div>
        @endif
    </div>
</div>
