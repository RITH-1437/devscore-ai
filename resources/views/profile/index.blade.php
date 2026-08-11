<x-layouts.app>

<div class="max-w-7xl mx-auto space-y-8"
     x-data="{
        syncState: 'idle', // idle | loading | success | error
        syncMessage: '',
        async syncProfile() {
            if (this.syncState === 'loading') return;
            this.syncState = 'loading';
            this.syncMessage = '';
            try {
                const res = await fetch('{{ route('profile.sync') }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').content,
                    },
                    credentials: 'same-origin',
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok && data.success) {
                    this.syncState = 'success';
                    this.syncMessage = data.message || 'Profile synchronized';
                    setTimeout(() => window.location.reload(), 900);
                } else {
                    this.syncState = 'error';
                    this.syncMessage = data.message || 'Sync failed';
                }
            } catch (e) {
                this.syncState = 'error';
                this.syncMessage = 'Network error — please try again.';
            }
        }
     }">

    {{-- ── Profile Header ─────────────────────────────────────────────────── --}}
    <div class="p-4 sm:p-6 lg:p-8 rounded-2xl bg-[var(--bg-card)] border border-[var(--border-color)] shadow-[var(--shadow-card)]">
        <div class="flex flex-col sm:flex-row sm:items-start gap-6">

            <img src="{{ $account->avatar_url }}"
                 alt="{{ $account->name ?: $account->username }}"
                 class="w-24 h-24 rounded-2xl border border-[var(--border-color)] shrink-0 mx-auto sm:mx-0">

            <div class="flex-1 min-w-0 text-center sm:text-left">
                <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3 min-w-0">
                    <h1 class="text-2xl font-black tracking-tight text-[var(--text-primary)] break-anywhere">
                        {{ $account->name ?: $user->name }}
                    </h1>
                    <a href="https://github.com/{{ $account->username }}" target="_blank" rel="noopener noreferrer"
                       class="text-sm font-medium text-[var(--primary)] hover:underline break-anywhere">
                        {{ $account->username }}
                    </a>
                </div>

                @if($account->bio)
                <p class="mt-2 text-sm text-[var(--text-secondary)] max-w-2xl break-anywhere">{{ $account->bio }}</p>
                @endif

                <div class="mt-4 flex flex-wrap items-center justify-center sm:justify-start gap-x-5 gap-y-2 text-sm text-[var(--text-muted)]">
                    @if($account->location)
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        {{ $account->location }}
                    </span>
                    @endif

                    @if($account->company)
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        {{ $account->company }}
                    </span>
                    @endif

                    @if($account->portfolio_url)
                    <a href="{{ $account->portfolio_url }}" target="_blank" rel="noopener noreferrer"
                       class="flex min-w-0 max-w-full items-center gap-1.5 text-[var(--primary)] hover:underline">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                        <span class="break-anywhere">{{ $account->portfolio_url }}</span>
                    </a>
                    @endif

                    @if($account->github_created_at)
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        Joined {{ $account->github_created_at->format('F Y') }}
                    </span>
                    @endif
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex flex-col gap-2.5 w-full sm:w-auto shrink-0">
                <a href="https://github.com/{{ $account->username }}" target="_blank" rel="noopener noreferrer"
                   class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border border-[var(--border-color)] bg-[var(--bg-muted)] hover:bg-[var(--bg-hover)] text-[var(--text-primary)] text-sm font-medium transition">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 0C5.37 0 0 5.373 0 12c0 5.303 3.438 9.8 8.205 11.387.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 21.795 24 17.298 24 12c0-6.627-5.373-12-12-12"/>
                    </svg>
                    View GitHub Profile
                </a>

                <button type="button"
                        @click="syncProfile()"
                        :disabled="syncState === 'loading'"
                        :aria-busy="(syncState === 'loading').toString()"
                        aria-describedby="profile-sync-status"
                        class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-70"
                        :style="syncState === 'error'
                            ? 'background: var(--danger-soft); border: 1px solid var(--danger-soft-border); color: var(--danger);'
                            : (syncState === 'success'
                                ? 'background: var(--success-soft); border: 1px solid var(--success-soft-border); color: var(--success);'
                                : 'background: var(--primary); color: #fff; border: 1px solid transparent;')">
                    <svg x-show="syncState === 'loading'" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <svg x-show="syncState === 'success'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <svg x-show="syncState === 'error'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <svg x-show="syncState === 'idle'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span x-text="syncState === 'loading' ? 'Syncing...' : (syncState === 'success' ? (syncMessage || 'Profile synchronized') : (syncState === 'error' ? 'Sync failed - Retry' : 'Sync GitHub Profile'))"></span>
                </button>
                <p id="profile-sync-status" x-show="syncMessage" x-transition class="text-xs text-center" :style="syncState === 'error' ? 'color: var(--danger);' : 'color: var(--success);'" x-text="syncMessage" role="status" aria-live="polite"></p>

                <form action="{{ route('settings.sync') }}" method="POST" x-data="{ submitting: false }" @submit="submitting = true">
                    @csrf
                    <button type="submit"
                            :disabled="submitting"
                            :aria-busy="submitting.toString()"
                            :class="submitting ? 'opacity-70 cursor-not-allowed' : ''"
                            class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border border-[var(--border-color)] bg-[var(--bg-muted)] hover:bg-[var(--bg-hover)] text-[var(--text-primary)] text-sm font-medium transition">
                        <svg class="w-4 h-4" :class="submitting ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                        </svg>
                        <span x-text="submitting ? 'Syncing repositories...' : 'Sync Repositories'"></span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ── GitHub Statistics ──────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-stat-card title="Followers" :value="number_format($stats['followers'])" color="violet"
            icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-3.13a4 4 0 10-4-4 4 4 0 004 4zm6 0a4 4 0 10-4-4"/>' />

        <x-stat-card title="Following" :value="number_format($stats['following'])" color="blue"
            icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>' />

        <x-stat-card title="Public Repositories" :value="number_format($stats['public_repos'])" color="emerald"
            :sub="$stats['synced_repos'] . ' synced locally'"
            icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>' />

        <x-stat-card title="Public Gists" :value="number_format($stats['public_gists'])" color="amber"
            icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>' />

        <x-stat-card title="Total Stars" :value="number_format($stats['total_stars'])" color="amber"
            sub="Across synced repositories"
            icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>' />

        <x-stat-card title="Total Forks" :value="number_format($stats['total_forks'])" color="blue"
            sub="Across synced repositories"
            icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/>' />

        <x-stat-card title="Primary Language" :value="$stats['primary_language'] ?? 'N/A'" color="violet"
            icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>' />

        <x-stat-card title="GitHub Member Since" :value="$account->github_created_at?->format('M Y') ?? 'N/A'" color="emerald"
            icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>' />
    </div>

    {{-- ── Developer Overview ─────────────────────────────────────────────── --}}
    <div>
        <h2 class="font-bold text-lg text-[var(--text-primary)] mb-4">Developer Overview</h2>

        <div class="grid lg:grid-cols-3 gap-6">

            {{-- Portfolio score --}}
            <div class="lg:col-span-1 p-6 rounded-2xl bg-[var(--bg-card)] border border-[var(--border-color)] shadow-[var(--shadow-card)] flex flex-col items-center justify-center gap-4">
                @if($portfolio->isAnalyzed())
                <x-score-ring :score="$portfolio->score" :size="140" label="Portfolio Score" />
                @else
                <div class="flex flex-col items-center gap-3 py-6">
                    <div class="w-32 h-32 rounded-full bg-[var(--bg-muted)] border border-[var(--border-color)] flex items-center justify-center">
                        <span class="text-[var(--text-muted)] text-sm font-medium text-center leading-tight">
                            Not<br>analyzed
                        </span>
                    </div>
                    <span class="text-[var(--text-muted)] text-xs font-medium">Portfolio Score</span>
                    <a href="{{ route('repositories.index') }}"
                       class="mt-1 px-4 py-2 rounded-xl text-white text-xs font-semibold transition-all duration-200"
                       style="background: var(--primary);">
                        Run AI Analysis
                    </a>
                </div>
                @endif
                <p class="text-xs text-[var(--text-muted)] text-center">
                    {{ $stats['analyzed_repos'] }} of {{ $stats['synced_repos'] }} repositories analyzed
                </p>
            </div>

            {{-- Most-used languages --}}
            <div class="lg:col-span-1 p-6 rounded-2xl bg-[var(--bg-card)] border border-[var(--border-color)] shadow-[var(--shadow-card)]">
                <h3 class="font-bold text-sm text-[var(--text-primary)] mb-4">Most-Used Languages</h3>
                @if($topLanguages->isNotEmpty())
                <div class="space-y-3">
                    @php $maxLang = $topLanguages->max(); @endphp
                    @foreach($topLanguages->take(6) as $lang => $count)
                    @php
                        $colors = ['bg-violet-500','bg-blue-500','bg-cyan-500','bg-emerald-500','bg-amber-500','bg-rose-500'];
                        $color  = $colors[$loop->index % count($colors)];
                        $pct    = $maxLang > 0 ? round(($count / $maxLang) * 100) : 0;
                    @endphp
                    <div>
                        <div class="flex justify-between text-xs mb-1.5">
                            <span class="font-medium text-[var(--text-primary)] truncate pr-3">{{ $lang ?: 'Unknown' }}</span>
                            <span class="text-[var(--text-muted)]">{{ $count }}</span>
                        </div>
                        <div class="h-1.5 bg-[var(--bg-muted)] rounded-full overflow-hidden">
                            <div class="{{ $color }} h-full rounded-full transition-all duration-700" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="flex items-center justify-center h-32 text-[var(--text-muted)] text-sm">
                    No language data yet.
                </div>
                @endif
            </div>

            {{-- Repository activity --}}
            <div class="lg:col-span-1 p-6 rounded-2xl bg-[var(--bg-card)] border border-[var(--border-color)] shadow-[var(--shadow-card)]">
                <h3 class="font-bold text-sm text-[var(--text-primary)] mb-4">Repository Activity</h3>
                @if($activityTimeline->isNotEmpty())
                @php $maxTimeline = $activityTimeline->max(); @endphp
                <div class="flex items-end gap-2 h-32 overflow-x-auto pb-1">
                    @foreach($activityTimeline as $year => $count)
                    @php $heightPct = $maxTimeline > 0 ? round(($count / $maxTimeline) * 100) : 0; @endphp
                    <div class="flex flex-col items-center gap-2 flex-1 min-w-10">
                        <span class="text-xs font-bold text-[var(--text-primary)]">{{ $count }}</span>
                        <div class="w-full rounded-t-lg border transition-all duration-300"
                             style="height: {{ max($heightPct, 8) }}%; background: var(--primary-soft); border-color: var(--primary-soft-border);">
                        </div>
                        <span class="text-[10px] text-[var(--text-muted)] truncate w-full text-center">{{ $year }}</span>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="flex items-center justify-center h-32 text-[var(--text-muted)] text-sm">
                    No repository activity yet.
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Top Repositories ───────────────────────────────────────────────── --}}
    <div>
        <div class="flex items-center justify-between gap-3 mb-4">
            <h2 class="font-bold text-lg text-[var(--text-primary)]">Top Repositories</h2>
            <a href="{{ route('repositories.index') }}" class="text-sm font-medium text-[var(--primary)] hover:underline">
                View all
            </a>
        </div>

        @if($topRepositories->isNotEmpty())
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($topRepositories as $repo)
            <div class="flex flex-col rounded-2xl border border-[var(--border-color)] bg-[var(--bg-card)] p-5 shadow-[var(--shadow-card)]">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <a href="{{ route('repositories.show', $repo) }}" class="font-bold text-sm text-[var(--text-primary)] hover:text-[var(--primary)] transition truncate block">
                            {{ $repo->name }}
                        </a>
                        @if($repo->language)
                        <p class="text-xs text-[var(--text-muted)] mt-0.5">{{ $repo->language }}</p>
                        @endif
                    </div>
                    @if($repo->isAnalyzed())
                    <span class="shrink-0 text-xs font-bold px-2 py-1 rounded-lg {{ $repo->score_color }}" style="background: var(--bg-muted);">
                        {{ $repo->score }}/100
                    </span>
                    @endif
                </div>

                <p class="mt-3 text-sm text-[var(--text-secondary)] line-clamp-2 min-h-10">
                    {{ $repo->description ?: 'No description provided.' }}
                </p>

                <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-[var(--text-muted)]">
                    <span class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        {{ number_format($repo->stars) }}
                    </span>
                    <span class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/>
                        </svg>
                        {{ number_format($repo->forks) }}
                    </span>
                    @if($repo->pushed_at)
                    <span class="break-anywhere">Updated {{ $repo->pushed_at->diffForHumans() }}</span>
                    @endif
                </div>

                @if($repo->html_url)
                <a href="{{ $repo->html_url }}" target="_blank" rel="noopener noreferrer"
                   class="mt-4 flex items-center justify-center gap-2 px-3 py-2 rounded-xl border border-[var(--border-color)] bg-[var(--bg-muted)] hover:bg-[var(--bg-hover)] text-[var(--text-primary)] text-xs font-medium transition">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 0C5.37 0 0 5.373 0 12c0 5.303 3.438 9.8 8.205 11.387.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 21.795 24 17.298 24 12c0-6.627-5.373-12-12-12"/>
                    </svg>
                    View on GitHub
                </a>
                @endif
            </div>
            @endforeach
        </div>
        @else
        <div class="rounded-2xl bg-[var(--bg-card)] border border-[var(--border-color)] p-12 text-center">
            <p class="text-[var(--text-muted)] text-sm">No repositories synced yet. Use "Sync Repositories" above to import your GitHub projects.</p>
        </div>
        @endif
    </div>

    {{-- ── GitHub Information ─────────────────────────────────────────────── --}}
    <div class="p-6 rounded-2xl bg-[var(--bg-card)] border border-[var(--border-color)] shadow-[var(--shadow-card)]">
        <h2 class="font-bold text-base text-[var(--text-primary)] mb-5">GitHub Information</h2>
        <div class="grid sm:grid-cols-2 gap-y-3 gap-x-8">
            <div class="flex flex-col gap-1 py-2.5 border-b border-[var(--border-color)] sm:flex-row sm:items-center sm:justify-between">
                <span class="text-sm text-[var(--text-muted)]">Location</span>
                <span class="text-sm font-medium text-[var(--text-primary)] break-anywhere sm:text-right">{{ $account->location ?: 'Not provided' }}</span>
            </div>
            <div class="flex flex-col gap-1 py-2.5 border-b border-[var(--border-color)] sm:flex-row sm:items-center sm:justify-between">
                <span class="text-sm text-[var(--text-muted)]">Company</span>
                <span class="text-sm font-medium text-[var(--text-primary)] break-anywhere sm:text-right">{{ $account->company ?: 'Not provided' }}</span>
            </div>
            <div class="flex flex-col gap-1 py-2.5 border-b border-[var(--border-color)] sm:flex-row sm:items-center sm:justify-between">
                <span class="text-sm text-[var(--text-muted)]">Website</span>
                @if($account->portfolio_url)
                <a href="{{ $account->portfolio_url }}" target="_blank" rel="noopener noreferrer"
                   class="text-sm font-medium text-[var(--primary)] hover:underline break-anywhere sm:max-w-[16rem] sm:text-right">
                    {{ $account->portfolio_url }}
                </a>
                @else
                <span class="text-sm font-medium text-[var(--text-primary)]">Not provided</span>
                @endif
            </div>
            <div class="flex flex-col gap-1 py-2.5 border-b border-[var(--border-color)] sm:flex-row sm:items-center sm:justify-between">
                <span class="text-sm text-[var(--text-muted)]">Joined GitHub</span>
                <span class="text-sm font-medium text-[var(--text-primary)] sm:text-right">{{ $account->github_created_at?->format('F j, Y') ?? 'Unknown' }}</span>
            </div>
            <div class="flex flex-col gap-1 py-2.5 border-b border-[var(--border-color)] sm:col-span-2 sm:flex-row sm:items-center sm:justify-between">
                <span class="text-sm text-[var(--text-muted)]">Last synchronized</span>
                <span class="text-sm font-medium text-[var(--text-primary)] sm:text-right">{{ $account->updated_at->diffForHumans() }}</span>
            </div>
        </div>
    </div>

</div>

</x-layouts.app>
