<p class="text-[var(--text-secondary)] mt-1 text-sm">
    {{ $repositories->total() }} repositories
    @if($search) matching "{{ $search }}" @endif
    @if($lang) · Language: {{ $lang }} @endif
</p>

{{-- Active filters --}}
@php
    $labels = [
        'visibility' => $visibility === 'private' ? 'Private' : ($visibility ? 'Public' : null),
        'origin'     => $origin === 'fork' ? 'Forks' : ($origin ? 'Source repos' : null),
        'state'      => $state === 'archived' ? 'Archived' : ($state ? 'Active' : null),
        'analysis'   => match($analysis) {
            'analyzed' => 'Analyzed',
            'failed'   => 'Failed',
            'pending'  => 'Not analyzed',
            default    => null,
        },
        'highlight'  => $highlight === 'featured' ? 'Featured' : ($highlight ? 'Pinned' : null),
    ];
    $labels = array_filter($labels);
@endphp

@if(count($labels))
<div class="flex flex-wrap gap-2 mt-3">
    @foreach($labels as $key => $label)
    <a href="{{ route('repositories.index', array_merge(request()->query(), [$key => null])) }}"
       class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-medium transition-all duration-200"
       style="background: var(--primary-soft); border-color: var(--primary-soft-border); color: var(--primary);">
        {{ $label }}
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </a>
    @endforeach
</div>
@endif

{{-- Repository Grid --}}
@if($repositories->isNotEmpty())
<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4 mt-6">
    @foreach($repositories as $repo)
    <x-repo-card :repository="$repo" />
    @endforeach
</div>

{{-- Pagination --}}
@if($repositories->hasPages())
<div class="flex justify-center pt-6">
    {{ $repositories->links('vendor.pagination.simple-tailwind') }}
</div>
@endif

@else
{{-- Empty state --}}
<div class="rounded-2xl bg-[var(--bg-card)] border border-[var(--border-color)] shadow-[var(--shadow-card)] p-16 text-center mt-6">
    <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-5"
         style="background: var(--primary-soft); border: 1px solid var(--primary-soft-border);">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--primary);">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
    </div>
    <h3 class="font-bold text-lg text-[var(--text-primary)] mb-2">No Repositories Found</h3>
    <p class="text-[var(--text-secondary)] text-sm">
        @if($search || $lang)
            No repositories match your filters. <a href="{{ route('repositories.index') }}" class="text-[var(--primary)] hover:underline">Clear filters</a>
        @else
            Sync your GitHub account to see your repositories here.
        @endif
    </p>
</div>
@endif
