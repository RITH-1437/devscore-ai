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
    $score >= 40 => 'Needs work',
    default      => 'Early stage',
};

$strokeColor = match (true) {
    $score >= 75 => 'var(--success)',
    $score >= 60 => 'var(--primary)',
    $score >= 40 => 'var(--warning)',
    default      => 'var(--danger)',
};

$rangeClass = match (true) {
    $score >= 75 => 'text-[var(--success)]',
    $score >= 60 => 'text-[var(--primary)]',
    $score >= 40 => 'text-[var(--warning)]',
    default      => 'text-[var(--danger)]',
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
            <span class="stat-value leading-none {{ $rangeClass }}" style="font-size: {{ max(18, $size * 0.22) }}px;">{{ $score }}</span>
            <span class="text-[var(--text-muted)] text-[10px] mt-0.5 font-medium">/ 100</span>
        </div>
    </div>

    @if($showRangeLabel)
    <span class="text-xs font-semibold {{ $rangeClass }}">{{ $rangeLabel }}</span>
    @endif

    @if($label)
    <span class="text-[var(--text-secondary)] text-xs font-medium text-center">{{ $label }}</span>
    @endif
</div>
