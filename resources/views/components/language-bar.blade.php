@props([
    'language',
    'count',
    'total',
    'max' => null,
])

@php
$langColor = \App\Helpers\LanguageColorHelper::getLanguageColor($language);
$pct = $total > 0 ? round(($count / $total) * 100) : 0;
$barPct = ($max ?? $total) > 0 ? round(($count / ($max ?? $total)) * 100) : 0;
@endphp

<div {{ $attributes->merge(['class' => 'space-y-2']) }}>
    <div class="flex flex-col gap-1 text-sm sm:flex-row sm:items-center sm:justify-between sm:gap-3">
        <span class="flex min-w-0 items-center gap-2 font-semibold text-[var(--text-primary)]">
            <span class="h-3 w-3 shrink-0 rounded-full" style="background: {{ $langColor }};" aria-hidden="true"></span>
            <span class="break-anywhere">{{ $language ?: 'Unknown' }}</span>
        </span>
        <span class="shrink-0 text-[var(--text-muted)]">{{ $count }} repos · {{ $pct }}%</span>
    </div>
    <div class="h-2.5 bg-[var(--bg-muted)] rounded-full overflow-hidden" role="presentation">
        <div class="h-full rounded-full transition-all duration-700"
             style="width: {{ $barPct }}%; background: {{ $langColor }};"
             aria-hidden="true"></div>
    </div>
</div>
