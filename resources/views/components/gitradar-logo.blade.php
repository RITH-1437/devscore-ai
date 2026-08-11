@props([
    'variant' => 'full', // icon | compact | full
    'class' => '',
])

@php
$iconClass = match ($variant) {
    'icon' => 'h-9 w-9',
    'compact' => 'h-8 w-auto max-w-[140px]',
    default => 'h-9 w-auto max-w-[160px]',
};
@endphp

<a href="{{ route('dashboard') }}" {{ $attributes->merge(['class' => 'inline-flex items-center gap-3 shrink-0 ' . $class]) }} aria-label="GitRadar home">
    <span class="{{ $iconClass }} inline-flex shrink-0 text-[var(--text-primary)] [&>svg]:h-full [&>svg]:w-auto" aria-hidden="true">
        {!! str_replace('<svg', '<svg role="img" aria-label="GitRadar"', file_get_contents(public_path('images/logo.svg'))) !!}
    </span>

    @if($variant === 'full')
    <p class="text-[var(--text-muted)] text-xs hidden sm:block">AI Portfolio Analyzer</p>
    @endif
</a>
