<x-layouts.app>

<div class="max-w-7xl mx-auto space-y-8"
     x-data="{
        refreshing: false,
        errorMessage: @js(session('error')),
        infoMessage: @js(session('info')),
     }">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-3xl font-black tracking-tight text-[var(--text-primary)]">AI Analysis</h1>
            <p class="text-[var(--text-secondary)] mt-1 text-sm max-w-2xl">
                Portfolio-wide insights from your GitHub repositories, powered by GitRadar.
            </p>
        </div>

        <form action="{{ route('analysis.run') }}"
              method="POST"
              @submit="if (refreshing) { $event.preventDefault(); } else { refreshing = true; }">
            @csrf
            <button type="submit"
                    :disabled="refreshing"
                    :aria-busy="refreshing.toString()"
                    :class="refreshing ? 'opacity-70 cursor-not-allowed' : 'hover:border-[var(--primary)]/40 hover:bg-[var(--bg-hover)]'"
                    class="inline-flex items-center gap-2 rounded-xl border border-[var(--border-color)] bg-[var(--bg-card)] px-5 py-2.5 text-sm font-semibold text-[var(--text-primary)] shadow-[var(--shadow-card)] transition-all">
                <svg class="h-4 w-4" :class="refreshing ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--primary);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span x-text="refreshing ? 'Refreshing...' : 'Refresh Analysis'">Refresh Analysis</span>
            </button>
        </form>
    </div>

    {{-- Error / info banners --}}
    <template x-if="errorMessage">
        <div class="flex flex-col gap-3 rounded-2xl border p-4 sm:flex-row sm:items-center sm:justify-between"
             style="background: var(--danger-soft); border-color: var(--danger-soft-border);">
            <div class="flex items-start gap-3">
                <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--danger);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p class="text-sm font-semibold" style="color: var(--danger);">Analysis refresh failed</p>
                    <p class="text-sm text-[var(--text-secondary)]" x-text="errorMessage"></p>
                </div>
            </div>
            <form action="{{ route('analysis.run') }}" method="POST" @submit="refreshing = true">
                @csrf
                <button type="submit" class="rounded-lg border border-[var(--border-color)] bg-[var(--bg-card)] px-4 py-2 text-sm font-semibold text-[var(--text-primary)] hover:bg-[var(--bg-hover)] transition">
                    Retry
                </button>
            </form>
        </div>
    </template>

    <template x-if="infoMessage && !errorMessage">
        <div class="rounded-2xl border p-4"
             style="background: var(--info-soft); border-color: var(--info-soft-border);">
            <p class="text-sm" style="color: var(--info);" x-text="infoMessage"></p>
        </div>
    </template>

    {{-- Portfolio header --}}
    <section class="rounded-2xl border border-[var(--border-color)] bg-[var(--bg-card)] p-6 shadow-[var(--shadow-card)] sm:p-8">
        <div class="grid gap-8 lg:grid-cols-[auto,1fr] lg:items-center">
            <div class="flex justify-center lg:justify-start">
                @if($portfolio->isAnalyzed())
                <x-score-ring :score="$portfolio->score" :size="140" :stroke="11" label="Portfolio Score" />
                @else
                <div class="flex flex-col items-center gap-3 py-4">
                    <div class="flex h-36 w-36 items-center justify-center rounded-full border border-[var(--border-color)] bg-[var(--bg-muted)]">
                        <span class="px-4 text-center text-sm font-medium leading-tight text-[var(--text-muted)]">
                            {{ $portfolio->statusLabel() }}
                        </span>
                    </div>
                    <span class="text-xs font-medium text-[var(--text-secondary)]">Portfolio Score</span>
                </div>
                @endif
            </div>

            <div class="min-w-0">
                <h2 class="text-xl font-black text-[var(--text-primary)]">Portfolio Assessment</h2>
                <p class="mt-2 text-sm text-[var(--text-secondary)]">
                    Based on {{ $portfolio->totalRepositories }} repositories across {{ $languageCount }} languages.
                    @if(! $portfolio->isAnalyzed())
                    <span class="text-[var(--warning)]">
                        {{ $portfolio->analyzedRepositories }} analyzed — run AI analysis on repositories to generate a score.
                    </span>
                    @endif
                </p>

                <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                    <div class="rounded-xl border border-[var(--border-color)] bg-[var(--bg-muted)] px-3 py-3">
                        <p class="text-xs text-[var(--text-muted)]">Overall Score</p>
                        <p class="mt-1 text-lg font-black text-[var(--text-primary)]">
                            {{ $portfolio->isAnalyzed() ? $portfolio->score . '/100' : '—' }}
                        </p>
                    </div>
                    <div class="rounded-xl border border-[var(--border-color)] bg-[var(--bg-muted)] px-3 py-3">
                        <p class="text-xs text-[var(--text-muted)]">Repos Analyzed</p>
                        <p class="mt-1 text-lg font-black text-[var(--text-primary)]">{{ $portfolio->analyzedRepositories }}/{{ $portfolio->totalRepositories }}</p>
                    </div>
                    <div class="rounded-xl border border-[var(--border-color)] bg-[var(--bg-muted)] px-3 py-3">
                        <p class="text-xs text-[var(--text-muted)]">Languages</p>
                        <p class="mt-1 text-lg font-black text-[var(--text-primary)]">{{ $languageCount }}</p>
                    </div>
                    <div class="rounded-xl border border-[var(--border-color)] bg-[var(--bg-muted)] px-3 py-3">
                        <p class="text-xs text-[var(--text-muted)]">Strengths</p>
                        <p class="mt-1 text-lg font-black text-[var(--text-primary)]">{{ count($portfolio->strengths) }}</p>
                    </div>
                    <div class="rounded-xl border border-[var(--border-color)] bg-[var(--bg-muted)] px-3 py-3 col-span-2 sm:col-span-1">
                        <p class="text-xs text-[var(--text-muted)]">Improve / Recs</p>
                        <p class="mt-1 text-lg font-black text-[var(--text-primary)]">{{ count($portfolio->weaknesses) }} / {{ count($portfolio->recommendations) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Insight sections --}}
    <div class="grid gap-4 lg:grid-cols-3">
        <x-analysis-insight-list
            title="Strengths"
            tone="success"
            :items="$portfolio->strengths"
            empty="Analyze your repositories to see strengths."
            icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>'
        />

        <x-analysis-insight-list
            title="Areas to Improve"
            tone="danger"
            :items="$portfolio->weaknesses"
            empty="Analyze your repositories to identify areas to improve."
            icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>'
        />

        <x-analysis-insight-list
            title="Recommendations"
            tone="info"
            :items="$portfolio->recommendations"
            empty="Analyze your repositories to get recommendations."
            icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>'
        />
    </div>

    {{-- Best & weakest highlights --}}
    @if($topRepoByScore || $weakestRepo)
    <div class="grid gap-4 md:grid-cols-2">
        @if($topRepoByScore)
        <div class="rounded-2xl border border-[var(--success-soft-border)] bg-[var(--success-soft)] p-5">
            <h3 class="mb-3 text-sm font-bold text-[var(--success)]">Best Repository (AI score)</h3>
            <a href="{{ route('repositories.show', $topRepoByScore) }}" class="block rounded-xl p-3 transition hover:bg-[var(--bg-card)]/60">
                <p class="font-bold text-[var(--text-primary)]">{{ $topRepoByScore->name }}</p>
                <p class="mt-1 text-xs text-[var(--text-secondary)] line-clamp-2">{{ $topRepoByScore->description ?: 'No description.' }}</p>
                <p class="mt-3 text-2xl font-black text-[var(--success)]">{{ $topRepoByScore->score }}/100</p>
            </a>
        </div>
        @endif

        @if($weakestRepo && $weakestRepo->id !== $topRepoByScore?->id)
        <div class="rounded-2xl border border-[var(--warning-soft-border)] bg-[var(--warning-soft)] p-5">
            <h3 class="mb-3 text-sm font-bold text-[var(--warning)]">Most Improvement Potential</h3>
            <a href="{{ route('repositories.show', $weakestRepo) }}" class="block rounded-xl p-3 transition hover:bg-[var(--bg-card)]/60">
                <p class="font-bold text-[var(--text-primary)]">{{ $weakestRepo->name }}</p>
                <p class="mt-1 text-xs text-[var(--text-secondary)] line-clamp-2">{{ $weakestRepo->description ?: 'No description.' }}</p>
                <p class="mt-3 text-2xl font-black text-[var(--warning)]">{{ $weakestRepo->score }}/100</p>
            </a>
        </div>
        @endif
    </div>
    @endif

    {{-- Repository-level analysis --}}
    @if($analyzedRepos->isNotEmpty())
    <section class="space-y-4">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-lg font-bold text-[var(--text-primary)]">Repository Analysis</h2>
                <p class="text-sm text-[var(--text-secondary)]">Per-repository AI scores and recruiter signals.</p>
            </div>
            <p class="text-xs text-[var(--text-muted)]">{{ $analyzedRepos->count() }} repositories analyzed</p>
        </div>

        <div class="overflow-hidden rounded-2xl border border-[var(--border-color)] bg-[var(--bg-card)] shadow-[var(--shadow-card)]">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[56rem]">
                    <thead>
                        <tr class="border-b border-[var(--border-color)]">
                            <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-[var(--text-muted)]">Repository</th>
                            <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-[var(--text-muted)] hidden md:table-cell">Language</th>
                            <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-[var(--text-muted)]">AI Score</th>
                            <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-[var(--text-muted)] hidden lg:table-cell">Difficulty</th>
                            <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-[var(--text-muted)] hidden lg:table-cell">Developer Level</th>
                            <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-[var(--text-muted)] hidden xl:table-cell">Recruiter</th>
                            <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-[var(--text-muted)] hidden xl:table-cell">Hiring Prob.</th>
                            <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-[var(--text-muted)] hidden xl:table-cell">Experience</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--border-color)]">
                        @foreach($analyzedRepos as $repo)
                        @php
                            $analysis = is_array($repo->ai_analysis) ? $repo->ai_analysis : [];
                            $langColor = $repo->language
                                ? \App\Helpers\LanguageColorHelper::getLanguageColor($repo->language)
                                : 'var(--lang-default)';
                        @endphp
                        <tr class="transition hover:bg-[var(--bg-muted)]">
                            <td class="px-5 py-4">
                                <a href="{{ route('repositories.show', $repo) }}" class="text-sm font-semibold text-[var(--text-primary)] hover:text-[var(--primary)] transition">
                                    {{ $repo->name }}
                                </a>
                            </td>
                            <td class="px-5 py-4 hidden md:table-cell">
                                @if($repo->language)
                                <span class="inline-flex items-center gap-1.5 rounded-lg border border-[var(--border-color)] bg-[var(--bg-muted)] px-2.5 py-1 text-xs font-medium text-[var(--text-secondary)]">
                                    <span class="h-2 w-2 rounded-full" style="background: {{ $langColor }};"></span>
                                    {{ $repo->language }}
                                </span>
                                @else
                                <span class="text-xs text-[var(--text-muted)]">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <span class="text-sm font-bold {{ $repo->score_color }}">{{ $repo->score }}/100</span>
                            </td>
                            <td class="px-5 py-4 hidden lg:table-cell">
                                <span class="text-xs text-[var(--text-secondary)]">{{ ucfirst($analysis['difficulty'] ?? '—') }}</span>
                            </td>
                            <td class="px-5 py-4 hidden lg:table-cell">
                                <span class="text-xs text-[var(--text-secondary)]">{{ ucfirst($analysis['portfolio_level'] ?? '—') }}</span>
                            </td>
                            <td class="px-5 py-4 hidden xl:table-cell">
                                <span class="text-xs text-[var(--text-secondary)]">{{ ($analysis['recruiter_rating'] ?? null) !== null ? ($analysis['recruiter_rating'] . '/10') : '—' }}</span>
                            </td>
                            <td class="px-5 py-4 hidden xl:table-cell">
                                <span class="text-xs text-[var(--text-secondary)]">{{ ($analysis['hiring_probability'] ?? null) !== null ? ($analysis['hiring_probability'] . '%') : '—' }}</span>
                            </td>
                            <td class="px-5 py-4 hidden xl:table-cell">
                                <span class="text-xs text-[var(--text-secondary)]">{{ ucfirst($analysis['estimated_experience'] ?? '—') }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
    @endif

    {{-- Recent AI analyses --}}
    @if($recentAnalyses->isNotEmpty())
    <section>
        <h2 class="mb-4 text-base font-bold text-[var(--text-primary)]">Recent AI Analyses</h2>
        <div class="overflow-hidden rounded-2xl border border-[var(--border-color)] bg-[var(--bg-card)] shadow-[var(--shadow-card)]">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[40rem]">
                    <thead>
                        <tr class="border-b border-[var(--border-color)]">
                            <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-[var(--text-muted)]">Repository</th>
                            <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-[var(--text-muted)] hidden md:table-cell">Score</th>
                            <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-[var(--text-muted)] hidden md:table-cell">Level</th>
                            <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-[var(--text-muted)] hidden lg:table-cell">Model</th>
                            <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-[var(--text-muted)]">Analyzed</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--border-color)]">
                        @foreach($recentAnalyses as $analysis)
                        <tr class="transition hover:bg-[var(--bg-muted)]">
                            <td class="px-5 py-4">
                                @if($analysis->repository)
                                <a href="{{ route('repositories.show', $analysis->repository) }}" class="text-sm font-medium text-[var(--text-primary)] hover:text-[var(--primary)] transition">
                                    {{ $analysis->repository->name }}
                                </a>
                                @else
                                <span class="text-sm text-[var(--text-muted)]">Deleted</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 hidden md:table-cell">
                                <span class="text-sm font-bold">{{ $analysis->score }}/100</span>
                            </td>
                            <td class="px-5 py-4 hidden md:table-cell">
                                <span class="text-xs text-[var(--text-secondary)]">{{ ucfirst($analysis->portfolio_level ?? '—') }}</span>
                            </td>
                            <td class="px-5 py-4 hidden lg:table-cell">
                                <span class="font-mono text-xs text-[var(--text-muted)]">{{ $analysis->model_used ? \Illuminate\Support\Str::limit($analysis->model_used, 30) : '—' }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <span class="text-xs text-[var(--text-muted)]">{{ $analysis->updated_at->diffForHumans() }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
    @endif

</div>

</x-layouts.app>
