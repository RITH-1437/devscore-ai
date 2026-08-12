@props([
    'title',
    'items' => [],
    'tone' => 'neutral', // success | danger | info | neutral
    'icon',
    'empty' => 'No items available yet.',
    'limit' => 6,
])

@php
$toneStyles = [
    'success' => [
        'accent' => 'var(--success)',
        'heading' => 'text-[var(--success)]',
    ],
    'danger' => [
        'accent' => 'var(--danger)',
        'heading' => 'text-[var(--danger)]',
    ],
    'info' => [
        'accent' => 'var(--info)',
        'heading' => 'text-[var(--info)]',
    ],
    'neutral' => [
        'accent' => 'var(--primary)',
        'heading' => 'text-[var(--text-primary)]',
    ],
];
$style = $toneStyles[$tone] ?? $toneStyles['neutral'];
$visibleItems = array_slice($items, 0, $limit);
$hiddenItems = array_slice($items, $limit);
$hasMore = count($hiddenItems) > 0;
@endphp

<div {{ $attributes->merge(['class' => 'panel p-5']) }}
     style="border-top: 2px solid {{ $style['accent'] }};"
     x-data="{ expanded: false }">
    <div class="mb-4">
        <p class="section-kicker">{{ $title }}</p>
        <p class="mt-1 text-xs text-[var(--text-muted)]">{{ count($items) }} finding{{ count($items) === 1 ? '' : 's' }}</p>
    </div>

    @if(count($items) > 0)
    <ul class="space-y-0 divide-y divide-[var(--border-color)]">
        @foreach($visibleItems as $item)
        <li class="flex items-start gap-3 py-3 first:pt-0 last:pb-0">
            <span class="mt-2 h-1 w-1 shrink-0 rounded-full" style="background: {{ $style['accent'] }};" aria-hidden="true"></span>
            <span class="break-anywhere text-sm leading-6 text-[var(--text-secondary)]">{{ $item }}</span>
        </li>
        @endforeach

        @if($hasMore)
        <template x-if="expanded">
            <div class="divide-y divide-[var(--border-color)]">
                @foreach($hiddenItems as $item)
                <div class="flex items-start gap-3 py-3">
                    <span class="mt-2 h-1 w-1 shrink-0 rounded-full" style="background: {{ $style['accent'] }};" aria-hidden="true"></span>
                    <span class="break-anywhere text-sm leading-6 text-[var(--text-secondary)]">{{ $item }}</span>
                </div>
                @endforeach
            </div>
        </template>
        @endif
    </ul>

    @if($hasMore)
    <button type="button"
            @click="expanded = !expanded"
            class="mt-3 text-xs font-semibold text-[var(--primary)] hover:text-[var(--primary-hover)] transition"
            :aria-expanded="expanded.toString()">
        <span x-text="expanded ? 'Show less' : 'Show {{ count($hiddenItems) }} more'"></span>
    </button>
    @endif
    @else
    <p class="text-sm text-[var(--text-muted)]">{{ $empty }}</p>
    @endif
</div>
