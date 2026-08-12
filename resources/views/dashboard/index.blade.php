<x-layouts.app title="Dashboard">

<div class="mx-auto min-w-0 max-w-7xl space-y-6 sm:space-y-8">

    {{-- Page header --}}
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div class="min-w-0">
            <h1 class="page-heading text-[var(--text-primary)]">Dashboard</h1>
            <p class="page-subheading break-anywhere">
                Welcome back, <span class="font-semibold text-[var(--text-primary)]">{{ auth()->user()->name }}</span>
                @if($account?->username)
                · <a href="https://github.com/{{ $account->username }}" target="_blank" rel="noopener noreferrer"
                     class="text-[var(--primary)] hover:text-[var(--primary-hover)] transition">
                    {{ $account->username }}
                </a>
                @endif
            </p>
        </div>

        <form action="{{ route('settings.sync') }}" method="POST" x-data="{ syncing: false }" @submit="syncing = true" class="w-full sm:w-auto">
            @csrf
            <button type="submit"
                    :disabled="syncing"
                    :aria-busy="syncing.toString()"
                    :class="syncing ? 'opacity-70 cursor-not-allowed' : ''"
                    class="touch-target flex w-full items-center justify-center gap-2 rounded-xl border border-[var(--border-color)] bg-[var(--bg-card)] px-4 py-2.5 text-sm font-medium text-[var(--text-primary)] transition-all duration-200 sm:w-auto">
                <svg class="w-4 h-4" :class="syncing ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--primary);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span x-text="syncing ? 'Syncing...' : 'Sync Repositories'">Sync Repositories</span>
            </button>
        </form>
    </div>

    {{-- Stats row --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

        <x-stat-card
            title="Repositories"
            :value="$totalRepos"
            color="orange"
            :sub="$analyzedCount . ' analyzed'"
            icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>'
        />

        <x-stat-card
            title="Total Stars"
            :value="number_format($totalStars)"
            color="amber"
            icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>'
        />

        <x-stat-card
            title="Top Language"
            :value="$topLanguages->keys()->first() ?? 'N/A'"
            color="violet"
            :sub="($topLanguages->first() ? $topLanguages->first() . ' repos' : '')"
            icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>'
        />

        <x-stat-card
            title="Portfolio Score"
            :value="$portfolio->isAnalyzed() ? $portfolio->score . '/100' : 'Not analyzed'"
            color="emerald"
            :sub="$portfolio->isAnalyzed() ? $portfolio->analyzedRepositories . ' repos analyzed' : 'Run AI analysis to score'"
            icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>'
        />

    </div>

    <div class="grid lg:grid-cols-3 gap-6">

        {{-- Portfolio score ring + breakdown --}}
        <div class="lg:col-span-1 flex flex-col items-center justify-center gap-5 panel p-5 sm:gap-6 sm:p-6">
            @if($portfolio->isAnalyzed())
            <x-score-ring :score="$portfolio->score" :size="120" label="Portfolio Score" />
            @else
            <div class="flex flex-col items-center gap-3 py-6">
                <div class="w-32 h-32 rounded-full bg-[var(--bg-muted)] border border-[var(--border-color)] flex items-center justify-center">
                    <span class="text-[var(--text-muted)] text-sm font-medium text-center leading-tight">
                        Not<br>analyzed
                    </span>
                </div>
                <span class="text-[var(--text-muted)] text-xs font-medium">Portfolio Score</span>
                <a href="{{ route('repositories.index') }}" class="btn-primary mt-1 px-4 py-2 text-xs">
                    Run AI Analysis
                </a>
            </div>
            @endif

            <div class="w-full space-y-3">
                @php
                $breakdown = [
                    ['label' => 'Repositories Analyzed', 'value' => $portfolio->analyzedRepositories, 'max' => max($portfolio->totalRepositories, 1), 'color' => 'var(--primary)'],
                    ['label' => 'Strengths',            'value' => count($portfolio->strengths),      'max' => 10, 'color' => 'var(--success)'],
                    ['label' => 'Areas to Improve',     'value' => count($portfolio->weaknesses),     'max' => 10, 'color' => 'var(--warning)'],
                    ['label' => 'Recommendations',      'value' => count($portfolio->recommendations),'max' => 10, 'color' => 'var(--secondary)'],
                ];
                @endphp
                @foreach($breakdown as $b)
                <div>
                    <div class="flex justify-between text-xs text-[var(--text-muted)] mb-1.5">
                        <span class="font-medium">{{ $b['label'] }}</span>
                        <span>{{ $b['value'] }}/{{ $b['max'] }}</span>
                    </div>
                    <div class="h-2 bg-[var(--bg-muted)] rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-700"
                             style="width: {{ $b['max'] > 0 ? round(min(($b['value'] / $b['max']), 1) * 100) : 0 }}%; background: {{ $b['color'] }};">
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Language distribution --}}
        <div class="lg:col-span-2 panel p-6">
            <div class="panel-header !mb-5 !pb-4">
                <div>
                    <h2 class="section-title">Language distribution</h2>
                    <p class="section-desc">Primary languages across synced repositories</p>
                </div>
            </div>
            @if($topLanguages->isNotEmpty())
            <div class="space-y-4">
                @php
                $maxLang = $topLanguages->max();
                $totalRepos = $topLanguages->sum();
                @endphp
                @foreach($topLanguages->take(7) as $lang => $count)
                @php
                $langColor = \App\Helpers\LanguageColorHelper::getLanguageColor($lang);
                $pct = $maxLang > 0 ? round(($count / $maxLang) * 100) : 0;
                $totalPct = $totalRepos > 0 ? round(($count / $totalRepos) * 100) : 0;
                @endphp
                <div class="min-w-0">
                    <div class="mb-2 flex flex-col gap-1 text-sm sm:flex-row sm:items-center sm:justify-between">
                        <span class="flex min-w-0 items-center gap-2 font-semibold text-[var(--text-primary)]">
                            <span class="h-3 w-3 shrink-0 rounded-full" style="background: {{ $langColor }};"></span>
                            <span class="break-anywhere">{{ $lang ?: 'Unknown' }}</span>
                        </span>
                        <span class="shrink-0 text-[var(--text-muted)]">{{ $count }} repos · {{ $totalPct }}%</span>
                    </div>
                    <div class="h-2.5 bg-[var(--bg-muted)] rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-700"
                             style="width: {{ $pct }}%; background: {{ $langColor }};">
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="flex items-center justify-center h-32 text-[var(--text-muted)] text-sm">
                No repositories synced yet.
            </div>
            @endif
        </div>

    </div>

    {{-- Pinned Repositories --}}
    @if($pinnedRepos->isNotEmpty())
    <div>
        <h2 class="section-title mb-4 flex items-center gap-2">
            <span class="inline-flex h-5 w-5 items-center justify-center rounded text-[var(--warning)]" aria-hidden="true">
                <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>
            </span>
            Pinned on GitHub
        </h2>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($pinnedRepos as $repo)
            <x-repo-card :repository="$repo" />
            @endforeach
        </div>
    </div>
    @endif

    {{-- Recent Repositories --}}
    <div>
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h2 class="section-title">Recent repositories</h2>
                <p class="section-desc">Latest activity from your GitHub sync</p>
            </div>
            <a href="{{ route('repositories.index') }}" class="text-sm font-medium text-[var(--primary)] hover:text-[var(--primary-hover)] transition">
                View all
            </a>
        </div>

        @if($repositories->isNotEmpty())
        {{-- Mobile / tablet card list --}}
        <div class="panel overflow-hidden lg:hidden">
            @foreach($repositories->take(10) as $repo)
            <x-mobile-repo-row :repository="$repo" />
            @endforeach
        </div>

        {{-- Desktop table --}}
        <div class="panel hidden overflow-hidden lg:block">
            <div class="table-scroll">
            <table class="w-full min-w-[34rem]">
                <thead>
                    <tr class="border-b border-[var(--border-color)]">
                        <th class="table-head">Repository</th>
                        <th class="table-head hidden md:table-cell">Language</th>
                        <th class="table-head hidden sm:table-cell">Stars</th>
                        <th class="table-head hidden lg:table-cell">AI score</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--border-color)]">
                    @foreach($repositories->take(10) as $repo)
                    <tr class="hover:bg-[var(--bg-muted)] transition-colors duration-150">
                        <td class="px-5 py-4">
                            <a href="{{ route('repositories.show', $repo) }}"
                               class="repo-name text-sm hover:text-[var(--primary)] transition flex items-center gap-2">
                                {{ $repo->name }}
                                @if($repo->is_pinned)
                                <svg class="w-3.5 h-3.5 text-amber-400" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                                </svg>
                                @endif
                            </a>
                            @if($repo->description)
                            <p class="text-xs text-[var(--text-muted)] mt-0.5 truncate max-w-xs">{{ $repo->description }}</p>
                            @endif
                        </td>
                        <td class="px-5 py-4 hidden md:table-cell">
                            @if($repo->language)
                            @php $langColor = \App\Helpers\LanguageColorHelper::getLanguageColor($repo->language); @endphp
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-lg bg-[var(--bg-muted)] text-[var(--text-secondary)]">
                                <span class="w-2 h-2 rounded-full shrink-0" style="background: {{ $langColor }};" aria-hidden="true"></span>
                                {{ $repo->language }}
                            </span>
                            @else
                            <span class="text-[var(--text-muted)] text-xs">-</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 hidden sm:table-cell">
                            <span class="text-sm text-[var(--text-secondary)] flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                                {{ $repo->stars }}
                            </span>
                        </td>
                        <td class="px-5 py-4 hidden lg:table-cell">
                            @if($repo->isAnalyzed())
                            <span class="text-sm font-bold {{ $repo->score_color }}">
                                {{ $repo->score }}/100
                            </span>
                            @elseif($repo->isAnalyzing())
                            <span class="text-xs text-blue-600 dark:text-blue-400 flex items-center gap-1">
                                <svg class="w-3 h-3 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                Analyzing…
                            </span>
                            @else
                            <a href="{{ route('repositories.show', $repo) }}"
                               class="text-xs text-[var(--primary)] hover:text-[var(--primary-hover)] transition">Analyze</a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </div>
        @else
        <div class="panel empty-state sm:p-12">
            <div class="empty-state-icon mb-4">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                </svg>
            </div>
            <h3 class="section-title mb-1">No repositories synced</h3>
            <p class="text-sm text-[var(--text-muted)] mb-5 max-w-sm">Connect GitHub and pull your public repos to start scoring your portfolio.</p>
            <form action="{{ route('settings.sync') }}" method="POST">
                @csrf
                <button type="submit" class="btn-primary">Sync from GitHub</button>
            </form>
        </div>
        @endif
    </div>

</div>

</x-layouts.app>
