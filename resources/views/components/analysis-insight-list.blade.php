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
        'panel' => 'border-[var(--success-soft-border)] bg-[var(--success-soft)]',
        'heading' => 'text-[var(--success)]',
        'itemBorder' => 'border-[var(--border-color)]',
        'bullet' => 'text-[var(--success)]',
    ],
    'danger' => [
        'panel' => 'border-[var(--danger-soft-border)] bg-[var(--danger-soft)]',
        'heading' => 'text-[var(--danger)]',
        'itemBorder' => 'border-[var(--border-color)]',
        'bullet' => 'text-[var(--danger)]',
    ],
    'info' => [
        'panel' => 'border-[var(--info-soft-border)] bg-[var(--info-soft)]',
        'heading' => 'text-[var(--info)]',
        'itemBorder' => 'border-[var(--border-color)]',
        'bullet' => 'text-[var(--info)]',
    ],
    'neutral' => [
        'panel' => 'border-[var(--border-color)] bg-[var(--bg-card)]',
        'heading' => 'text-[var(--text-primary)]',
        'itemBorder' => 'border-[var(--border-color)]',
        'bullet' => 'text-[var(--primary)]',
    ],
];
$style = $toneStyles[$tone] ?? $toneStyles['neutral'];
$visibleItems = array_slice($items, 0, $limit);
$hiddenItems = array_slice($items, $limit);
$hasMore = count($hiddenItems) > 0;
@endphp

<div {{ $attributes->merge(['class' => 'rounded-2xl border p-5 shadow-[var(--shadow-card)] transition-colors hover:border-[var(--border-strong)] ' . $style['panel']]) }}
     x-data="{ expanded: false }">
    <div class="mb-4 flex items-center gap-2">
        <div class="flex h-9 w-9 items-center justify-center rounded-xl border border-[var(--border-color)] bg-[var(--bg-muted)]">
            <svg class="h-4 w-4 {{ $style['heading'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                {!! $icon !!}
            </svg>
        </div>
        <div>
            <h3 class="text-sm font-bold {{ $style['heading'] }}">{{ $title }}</h3>
            <p class="text-xs text-[var(--text-muted)]">{{ count($items) }} item{{ count($items) === 1 ? '' : 's' }}</p>
        </div>
    </div>

    @if(count($items) > 0)
    <ul class="space-y-0">
        @foreach($visibleItems as $item)
        <li class="flex items-start gap-2 border-b py-2.5 last:border-0 {{ $style['itemBorder'] }}">
            <span class="mt-0.5 shrink-0 text-sm {{ $style['bullet'] }}" aria-hidden="true">•</span>
            <span class="text-sm leading-6 text-[var(--text-secondary)]">{{ $item }}</span>
        </li>
        @endforeach

        @if($hasMore)
        <template x-if="expanded">
            <div class="space-y-0">
                @foreach($hiddenItems as $item)
                <div class="flex items-start gap-2 border-b py-2.5 last:border-0 {{ $style['itemBorder'] }}">
                    <span class="mt-0.5 shrink-0 text-sm {{ $style['bullet'] }}" aria-hidden="true">•</span>
                    <span class="text-sm leading-6 text-[var(--text-secondary)]">{{ $item }}</span>
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
    <p class="text-sm italic text-[var(--text-muted)]">{{ $empty }}</p>
    @endif
</div>
