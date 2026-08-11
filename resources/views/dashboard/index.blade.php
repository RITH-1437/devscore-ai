<x-layouts.app>

<div class="max-w-7xl mx-auto space-y-8">

    {{-- Page header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-black tracking-tight text-[var(--text-primary)]">Dashboard</h1>
            <p class="text-[var(--text-secondary)] mt-1 text-sm">
                Welcome back, <span class="font-semibold text-[var(--text-primary)]">{{ auth()->user()->name }}</span>
                @if($account?->username)
                · <a href="https://github.com/{{ $account->username }}" target="_blank" rel="noopener noreferrer"
                     class="text-[var(--primary)] hover:text-[var(--primary-hover)] transition">
                    {{ $account->username }}
                </a>
                @endif
            </p>
        </div>

        <form action="{{ route('settings.sync') }}" method="POST" x-data="{ syncing: false }" @submit="syncing = true">
            @csrf
            <button type="submit"
                    :disabled="syncing"
                    :aria-busy="syncing.toString()"
                    :class="syncing ? 'opacity-70 cursor-not-allowed' : ''"
                    class="flex items-center gap-2 px-4 py-2.5 rounded-xl border border-[var(--border-color)] bg-[var(--bg-card)] hover:bg-[var(--bg-hover)] text-sm font-medium text-[var(--text-primary)] transition-all duration-200 shrink-0">
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
            color="violet"
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
            color="blue"
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
        <div class="lg:col-span-1 p-6 rounded-2xl bg-[var(--bg-card)] border border-[var(--border-color)] shadow-[var(--shadow-card)] flex flex-col items-center justify-center gap-6">
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
        <div class="lg:col-span-2 p-6 rounded-2xl bg-[var(--bg-card)] border border-[var(--border-color)] shadow-[var(--shadow-card)]">
            <h2 class="font-bold text-lg text-[var(--text-primary)] mb-5">Language Distribution</h2>
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
                <div>
                    <div class="flex justify-between items-center text-sm mb-2">
                        <span class="font-semibold text-[var(--text-primary)] flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full" style="background: {{ $langColor }};"></span>
                            {{ $lang ?: 'Unknown' }}
                        </span>
                        <span class="text-[var(--text-muted)]">{{ $count }} repos · {{ $totalPct }}%</span>
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
        <h2 class="font-bold text-base mb-4 flex items-center gap-2">
            <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 24 24">
                <path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
            </svg>
            Pinned Repositories
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
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-bold text-lg text-[var(--text-primary)]">Recent Repositories</h2>
            <a href="{{ route('repositories.index') }}" class="text-sm font-medium text-[var(--primary)] hover:text-[var(--primary-hover)] transition">
                View all →
            </a>
        </div>

        @if($repositories->isNotEmpty())
        <div class="overflow-hidden rounded-2xl bg-[var(--bg-card)] border border-[var(--border-color)] shadow-[var(--shadow-card)]">
            <div class="overflow-x-auto">
            <table class="w-full min-w-[34rem]">
                <thead>
                    <tr class="border-b border-[var(--border-color)]">
                        <th class="text-left px-5 py-4 text-xs font-semibold text-[var(--text-muted)] uppercase tracking-wider">Repository</th>
                        <th class="text-left px-5 py-4 text-xs font-semibold text-[var(--text-muted)] uppercase tracking-wider hidden md:table-cell">Language</th>
                        <th class="text-left px-5 py-4 text-xs font-semibold text-[var(--text-muted)] uppercase tracking-wider hidden sm:table-cell">Stars</th>
                        <th class="text-left px-5 py-4 text-xs font-semibold text-[var(--text-muted)] uppercase tracking-wider hidden lg:table-cell">AI Score</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--border-color)]">
                    @foreach($repositories->take(10) as $repo)
                    <tr class="hover:bg-[var(--bg-muted)] transition-colors duration-150">
                        <td class="px-5 py-4">
                            <a href="{{ route('repositories.show', $repo) }}"
                               class="font-medium hover:text-violet-400 transition text-sm flex items-center gap-2">
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
        <div class="rounded-2xl bg-[var(--bg-card)] border border-[var(--border-color)] p-8 text-center sm:p-16">
            <div class="w-14 h-14 rounded-2xl bg-violet-500/10 border border-violet-500/20 flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                </svg>
            </div>
            <h3 class="font-bold text-lg mb-2">No Repositories Yet</h3>
            <p class="text-[var(--text-muted)] text-sm mb-5">Click "Sync Repositories" to import your GitHub projects.</p>
            <form action="{{ route('settings.sync') }}" method="POST">
                @csrf
                <button type="submit" class="px-6 py-3 rounded-xl font-semibold text-sm text-white transition-all duration-200" style="background: var(--primary);">
                    Sync Now
                </button>
            </form>
        </div>
        @endif
    </div>

</div>

</x-layouts.app>
