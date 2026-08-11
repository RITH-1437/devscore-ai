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
    <img src="{{ asset('images/logo.png') }}"
         alt="GitRadar"
         class="{{ $iconClass }} object-contain"
         width="160"
         height="36"
         loading="eager">

    @if($variant === 'full')
    <div class="min-w-0 hidden sm:block">
        <p class="font-bold text-base leading-tight text-[var(--text-primary)]">GitRadar</p>
        <p class="text-[var(--text-muted)] text-xs">AI Portfolio Analyzer</p>
    </div>
    @elseif($variant === 'compact')
    <span class="font-bold text-base tracking-tight text-[var(--text-primary)] hidden sm:inline">GitRadar</span>
    @endif
</a>
