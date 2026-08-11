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
    <div class="flex justify-between items-center text-sm gap-3">
        <span class="font-semibold text-[var(--text-primary)] flex items-center gap-2 min-w-0">
            <span class="w-3 h-3 rounded-full shrink-0" style="background: {{ $langColor }};" aria-hidden="true"></span>
            <span class="truncate">{{ $language ?: 'Unknown' }}</span>
        </span>
        <span class="text-[var(--text-muted)] shrink-0">{{ $count }} repos · {{ $pct }}%</span>
    </div>
    <div class="h-2.5 bg-[var(--bg-muted)] rounded-full overflow-hidden" role="presentation">
        <div class="h-full rounded-full transition-all duration-700"
             style="width: {{ $barPct }}%; background: {{ $langColor }};"
             aria-hidden="true"></div>
    </div>
</div>
