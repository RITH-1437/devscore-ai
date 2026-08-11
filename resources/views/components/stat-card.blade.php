@props([
    'title',
    'value',
    'icon'  => null,
    'trend' => null,
    'color' => 'violet',
    'sub'   => null,
])

@php
$colorMap = [
    'violet' => ['bg' => 'bg-violet-500/10', 'border' => 'border-violet-500/20', 'text' => 'text-violet-600 dark:text-violet-400', 'glow' => 'shadow-violet-500/10'],
    'blue'   => ['bg' => 'bg-blue-500/10',   'border' => 'border-blue-500/20',   'text' => 'text-blue-600 dark:text-blue-400',   'glow' => 'shadow-blue-500/10'],
    'emerald'=> ['bg' => 'bg-emerald-500/10', 'border' => 'border-emerald-500/20','text' => 'text-emerald-600 dark:text-emerald-400','glow' => 'shadow-emerald-500/10'],
    'amber'  => ['bg' => 'bg-amber-500/10',   'border' => 'border-amber-500/20',  'text' => 'text-amber-600 dark:text-amber-400',  'glow' => 'shadow-amber-500/10'],
    'red'    => ['bg' => 'bg-red-500/10',     'border' => 'border-red-500/20',    'text' => 'text-red-600 dark:text-red-400',    'glow' => 'shadow-red-500/10'],
];
$c = $colorMap[$color] ?? $colorMap['violet'];
@endphp

<div class="relative min-w-0 p-5 sm:p-6 rounded-2xl bg-[var(--bg-card)] border border-[var(--border-color)] hover:border-[var(--border-strong)] transition-all duration-300 shadow-[var(--shadow-card)] overflow-hidden group">
    {{-- Gradient accent --}}
    <div class="absolute top-0 right-0 w-24 h-24 {{ $c['bg'] }} rounded-bl-full opacity-60 group-hover:opacity-90 transition-opacity duration-300"></div>

    <div class="relative">
        @if($icon)
        <div class="w-10 h-10 rounded-xl {{ $c['bg'] }} border {{ $c['border'] }} flex items-center justify-center mb-4">
            <svg class="w-5 h-5 {{ $c['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                {!! $icon !!}
            </svg>
        </div>
        @endif

        <p class="text-[var(--text-secondary)] text-sm font-medium mb-1 break-anywhere">{{ $title }}</p>
        <h3 class="text-2xl sm:text-3xl font-black tracking-tight text-[var(--text-primary)] break-anywhere">{{ $value }}</h3>

        @if($sub)
        <p class="text-[var(--text-muted)] text-xs mt-1.5 break-anywhere">{{ $sub }}</p>
        @endif

        @if($trend !== null)
        <div class="flex items-center gap-1 mt-2">
            @if($trend >= 0)
            <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
            </svg>
            <span class="text-xs text-emerald-600 dark:text-emerald-400">+{{ $trend }}</span>
            @else
            <svg class="w-3.5 h-3.5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
            <span class="text-xs text-red-600 dark:text-red-400">{{ $trend }}</span>
            @endif
        </div>
        @endif
    </div>
</div>
