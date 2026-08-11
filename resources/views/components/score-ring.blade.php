@props([
    'score'  => 0,
    'size'   => 120,
    'stroke' => 10,
    'label'  => null,
    'showRangeLabel' => true,
])

@php
$score = max(0, min(100, (int) ($score ?? 0)));
$radius       = ($size - $stroke) / 2;
$circumference = 2 * M_PI * $radius;
$offset       = $circumference - ($score / 100) * $circumference;

$rangeLabel = match (true) {
    $score >= 90 => 'Excellent',
    $score >= 75 => 'Strong',
    $score >= 60 => 'Developing',
    $score >= 40 => 'Needs Improvement',
    default      => 'Weak',
};

$strokeColor = match (true) {
    $score >= 90 => '#059669',
    $score >= 75 => '#0891b2',
    $score >= 60 => '#2563eb',
    $score >= 40 => '#d97706',
    default      => '#dc2626',
};

$textColor = match (true) {
    $score >= 90 => 'text-emerald-600 dark:text-emerald-400',
    $score >= 75 => 'text-cyan-600 dark:text-cyan-400',
    $score >= 60 => 'text-blue-600 dark:text-blue-400',
    $score >= 40 => 'text-amber-600 dark:text-amber-400',
    default      => 'text-red-600 dark:text-red-400',
};

$rangeColor = match (true) {
    $score >= 90 => 'text-emerald-600 dark:text-emerald-400',
    $score >= 75 => 'text-cyan-600 dark:text-cyan-400',
    $score >= 60 => 'text-blue-600 dark:text-blue-400',
    $score >= 40 => 'text-amber-600 dark:text-amber-400',
    default      => 'text-red-600 dark:text-red-400',
};

@endphp

<div class="flex flex-col items-center gap-2">
    <div class="relative" style="width:{{ $size }}px;height:{{ $size }}px;" role="img" aria-label="Score {{ $score }} out of 100, {{ $rangeLabel }}">
        <svg width="{{ $size }}" height="{{ $size }}" class="-rotate-90" aria-hidden="true">
            <circle cx="{{ $size/2 }}" cy="{{ $size/2 }}" r="{{ $radius }}"
                    fill="none" stroke="var(--border-color)" stroke-width="{{ $stroke }}"/>
            <circle cx="{{ $size/2 }}" cy="{{ $size/2 }}" r="{{ $radius }}"
                    fill="none"
                    stroke="{{ $strokeColor }}"
                    stroke-width="{{ $stroke }}"
                    stroke-linecap="round"
                    stroke-dasharray="{{ $circumference }}"
                    stroke-dashoffset="{{ $offset }}"
                    style="transition: stroke-dashoffset 1s ease;"/>
        </svg>
        <div class="absolute inset-0 flex flex-col items-center justify-center">
            <span class="font-black leading-none {{ $textColor }}" style="font-size: {{ max(18, $size * 0.22) }}px;">{{ $score }}</span>
            <span class="text-[var(--text-muted)] text-[10px] mt-0.5">/ 100</span>
        </div>
    </div>

    @if($showRangeLabel)
    <span class="text-xs font-semibold {{ $rangeColor }}">{{ $rangeLabel }}</span>
    @endif

    @if($label)
    <span class="text-[var(--text-secondary)] text-xs font-medium text-center">{{ $label }}</span>
    @endif
</div>
