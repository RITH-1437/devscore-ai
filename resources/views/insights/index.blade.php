<x-layouts.app>

<div class="max-w-7xl mx-auto space-y-8">

    {{-- Header --}}
    <div>
        <h1 class="text-3xl font-black tracking-tight text-[var(--text-primary)]">Insights</h1>
        <p class="text-[var(--text-secondary)] mt-1 text-sm max-w-3xl">
            Understand your development portfolio and identify opportunities for growth.
        </p>
    </div>

    {{-- A. Portfolio Health --}}
    <section class="space-y-4">
        <div>
            <h2 class="text-lg font-bold text-[var(--text-primary)]">Portfolio Health</h2>
            <p class="text-sm text-[var(--text-secondary)]">Key metrics across your synced GitHub repositories.</p>
        </div>

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4 xl:grid-cols-7">
            <x-stat-card
                title="Portfolio Score"
                :value="$portfolio->isAnalyzed() ? $portfolio->score . '/100' : 'Not analyzed'"
                color="emerald"
                :sub="$portfolio->isAnalyzed() ? $portfolio->analyzedRepositories . ' repos analyzed' : 'Run AI analysis to score'"
                icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>'
            />
            <x-stat-card title="Repositories" :value="$repositories->count()" color="violet" :sub="$analyzedCount . ' analyzed'" icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>' />
            <x-stat-card title="Doc Coverage" :value="$docCoverage . '%'" color="blue" :sub="$docCoverage . '% with README'" icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>' />
            <x-stat-card title="Languages" :value="$languageCount" color="violet" sub="Language diversity" icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>' />
            <x-stat-card title="Total Stars" :value="number_format($totalStars)" color="amber" icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>' />
            <x-stat-card title="Total Forks" :value="number_format($totalForks)" color="blue" icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/>' />
            <x-stat-card title="Analysis Coverage" :value="$analysisCoverage . '%'" color="emerald" :sub="$notAnalyzedCount . ' not analyzed'" icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>' />
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-2">
        {{-- B. Technology / Language Insights --}}
        <section class="rounded-2xl border border-[var(--border-color)] bg-[var(--bg-card)] p-6 shadow-[var(--shadow-card)]">
            <h2 class="font-bold text-base text-[var(--text-primary)] mb-1">Technology &amp; Language Insights</h2>
            <p class="text-sm text-[var(--text-secondary)] mb-5">Distribution of primary languages across your repositories.</p>

            @if($languages->isNotEmpty())
            <div class="space-y-4">
                @php $maxLang = $languages->max(); @endphp
                @foreach($languages->take(10) as $lang => $count)
                @php
                    $langColor = \App\Helpers\LanguageColorHelper::getLanguageColor($lang);
                    $pct = $totalLangCount > 0 ? round(($count / $totalLangCount) * 100) : 0;
                    $barPct = $maxLang > 0 ? round(($count / $maxLang) * 100) : 0;
                @endphp
                <div>
                    <div class="mb-1.5 flex items-center justify-between text-sm">
                        <span class="flex items-center gap-2 font-medium text-[var(--text-primary)]">
                            <span class="h-2.5 w-2.5 rounded-full" style="background: {{ $langColor }};"></span>
                            {{ $lang ?: 'Unknown' }}
                        </span>
                        <span class="text-[var(--text-muted)]">{{ $count }} repos · {{ $pct }}%</span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-[var(--bg-muted)]">
                        <div class="h-full rounded-full transition-all duration-700" style="width: {{ $barPct }}%; background: {{ $langColor }};"></div>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <p class="py-8 text-center text-sm text-[var(--text-muted)]">No language data available.</p>
            @endif
        </section>

        {{-- C. Repository Quality --}}
        <section class="rounded-2xl border border-[var(--border-color)] bg-[var(--bg-card)] p-6 shadow-[var(--shadow-card)]">
            <h2 class="font-bold text-base text-[var(--text-primary)] mb-1">Repository Quality</h2>
            <p class="text-sm text-[var(--text-secondary)] mb-5">Analysis status, documentation, and activity signals.</p>

            <div class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-xl border border-[var(--border-color)] bg-[var(--bg-muted)] p-4">
                    <p class="text-xs text-[var(--text-muted)]">Analyzed</p>
                    <p class="mt-1 text-2xl font-black text-[var(--text-primary)]">{{ $analyzedCount }}</p>
                </div>
                <div class="rounded-xl border border-[var(--border-color)] bg-[var(--bg-muted)] p-4">
                    <p class="text-xs text-[var(--text-muted)]">Not Analyzed</p>
                    <p class="mt-1 text-2xl font-black text-[var(--text-primary)]">{{ $notAnalyzedCount }}</p>
                </div>
                <div class="rounded-xl border border-[var(--border-color)] bg-[var(--bg-muted)] p-4">
                    <p class="text-xs text-[var(--text-muted)]">High Scoring (75+)</p>
                    <p class="mt-1 text-2xl font-black text-[var(--success)]">{{ $highScoringRepos->count() }}</p>
                </div>
                <div class="rounded-xl border border-[var(--border-color)] bg-[var(--bg-muted)] p-4">
                    <p class="text-xs text-[var(--text-muted)]">Needs Work (&lt;60)</p>
                    <p class="mt-1 text-2xl font-black text-[var(--warning)]">{{ $lowScoringRepos->count() }}</p>
                </div>
            </div>

            @if($mostActiveRepo)
            <div class="mt-4 rounded-xl border border-[var(--border-color)] bg-[var(--bg-muted)] p-4">
                <p class="text-xs text-[var(--text-muted)]">Most Recently Active</p>
                <a href="{{ route('repositories.show', $mostActiveRepo) }}" class="mt-1 block font-semibold text-[var(--primary)] hover:text-[var(--primary-hover)] transition">
                    {{ $mostActiveRepo->name }}
                </a>
                <p class="text-xs text-[var(--text-secondary)] mt-1">Last push {{ $mostActiveRepo->pushed_at?->diffForHumans() ?? '—' }}</p>
            </div>
            @endif

            <p class="mt-4 text-xs text-[var(--text-muted)]">{{ $recentlyActive }} repositories active in the last 90 days.</p>
        </section>
    </div>

    {{-- D. AI Insights --}}
    <section class="rounded-2xl border border-[var(--border-color)] bg-[var(--bg-card)] p-6 shadow-[var(--shadow-card)]">
        <h2 class="font-bold text-base text-[var(--text-primary)] mb-1">AI Insights</h2>
        <p class="text-sm text-[var(--text-secondary)] mb-5">Aggregated findings from completed repository analyses.</p>

        @if($portfolio->isAnalyzed() && (count($portfolio->strengths) || count($portfolio->weaknesses) || count($portfolio->recommendations)))
        <div class="grid gap-4 lg:grid-cols-3">
            <x-analysis-insight-list
                title="Portfolio Strengths"
                tone="success"
                :items="$portfolio->strengths"
                :limit="4"
                empty="No strengths aggregated yet."
                icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>'
            />
            <x-analysis-insight-list
                title="Growth Areas"
                tone="danger"
                :items="$portfolio->weaknesses"
                :limit="4"
                empty="No improvement areas aggregated yet."
                icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>'
            />
            <x-analysis-insight-list
                title="Recommended Actions"
                tone="info"
                :items="$portfolio->recommendations"
                :limit="4"
                empty="No recommendations aggregated yet."
                icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>'
            />
        </div>
        @else
        <div class="rounded-xl border border-dashed border-[var(--border-color)] bg-[var(--bg-muted)] p-8 text-center">
            <p class="text-sm font-medium text-[var(--text-primary)]">Insufficient AI analysis data</p>
            <p class="mt-1 text-sm text-[var(--text-secondary)]">Run AI analysis on your repositories to unlock portfolio insights here.</p>
            <a href="{{ route('analysis') }}" class="mt-4 inline-flex items-center rounded-xl px-4 py-2 text-sm font-semibold text-white transition" style="background: var(--primary);">
                Go to AI Analysis
            </a>
        </div>
        @endif
    </section>

    {{-- E. Development Overview --}}
    <section class="space-y-4">
        <div>
            <h2 class="text-lg font-bold text-[var(--text-primary)]">Development Overview</h2>
            <p class="text-sm text-[var(--text-secondary)]">Score distribution, community signals, and repository activity.</p>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            {{-- Score distribution --}}
            <div class="rounded-2xl border border-[var(--border-color)] bg-[var(--bg-card)] p-6 shadow-[var(--shadow-card)] lg:col-span-1">
                <h3 class="font-bold text-sm text-[var(--text-primary)] mb-4">Score Distribution</h3>
                @if($analyzedCount > 0)
                @php
                    $distributionLabels = [
                        'excellent' => ['label' => 'Excellent (90+)', 'color' => 'var(--success)'],
                        'strong' => ['label' => 'Strong (75–89)', 'color' => '#0891b2'],
                        'developing' => ['label' => 'Developing (60–74)', 'color' => 'var(--info)'],
                        'needsWork' => ['label' => 'Needs Work (40–59)', 'color' => 'var(--warning)'],
                        'weak' => ['label' => 'Weak (&lt;40)', 'color' => 'var(--danger)'],
                    ];
                    $maxBucket = max($scoreDistribution);
                @endphp
                <div class="space-y-3">
                    @foreach($distributionLabels as $key => $meta)
                    @php $count = $scoreDistribution[$key]; $width = $maxBucket > 0 ? round(($count / $maxBucket) * 100) : 0; @endphp
                    <div>
                        <div class="mb-1 flex justify-between text-xs">
                            <span class="text-[var(--text-secondary)]">{!! $meta['label'] !!}</span>
                            <span class="font-semibold text-[var(--text-primary)]">{{ $count }}</span>
                        </div>
                        <div class="h-2 rounded-full bg-[var(--bg-muted)] overflow-hidden">
                            <div class="h-full rounded-full" style="width: {{ max($width, $count > 0 ? 8 : 0) }}%; background: {{ $meta['color'] }};"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="py-6 text-center text-sm text-[var(--text-muted)]">No analyzed repositories yet.</p>
                @endif
            </div>

            {{-- Top repositories --}}
            <div class="rounded-2xl border border-[var(--border-color)] bg-[var(--bg-card)] p-6 shadow-[var(--shadow-card)] lg:col-span-2">
                <h3 class="font-bold text-sm text-[var(--text-primary)] mb-4">Top Repositories by Stars</h3>
                @if($topRepositories->isNotEmpty())
                <div class="space-y-2">
                    @foreach($topRepositories->take(8) as $repo)
                    @php $langColor = $repo->language ? \App\Helpers\LanguageColorHelper::getLanguageColor($repo->language) : 'var(--lang-default)'; @endphp
                    <a href="{{ route('repositories.show', $repo) }}" class="flex items-center gap-3 rounded-xl border border-transparent p-3 transition hover:border-[var(--border-color)] hover:bg-[var(--bg-muted)]">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[var(--bg-muted)] text-xs font-bold text-[var(--text-muted)]">{{ $loop->iteration }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-[var(--text-primary)]">{{ $repo->name }}</p>
                            <p class="truncate text-xs text-[var(--text-muted)] flex items-center gap-1.5">
                                @if($repo->language)
                                <span class="inline-block h-2 w-2 rounded-full" style="background: {{ $langColor }};"></span>
                                @endif
                                {{ $repo->language ?? 'Unknown' }}
                            </p>
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="text-sm font-bold text-[var(--text-primary)]">{{ number_format($repo->stars) }} ★</p>
                            @if($repo->isAnalyzed())
                            <p class="text-xs text-[var(--text-muted)]">{{ $repo->score }}/100</p>
                            @endif
                        </div>
                    </a>
                    @endforeach
                </div>
                @else
                <p class="py-6 text-center text-sm text-[var(--text-muted)]">No repositories yet.</p>
                @endif
            </div>
        </div>

        @if($timeline->isNotEmpty())
        <div class="rounded-2xl border border-[var(--border-color)] bg-[var(--bg-card)] p-6 shadow-[var(--shadow-card)]">
            <h3 class="font-bold text-sm text-[var(--text-primary)] mb-4">Repository Creation Timeline</h3>
            <div class="flex h-32 items-end gap-2 sm:gap-3">
                @php $maxTimeline = $timeline->max(); @endphp
                @foreach($timeline as $year => $count)
                @php $heightPct = $maxTimeline > 0 ? round(($count / $maxTimeline) * 100) : 0; @endphp
                <div class="flex min-w-0 flex-1 flex-col items-center gap-2">
                    <span class="text-xs font-bold text-[var(--text-secondary)]">{{ $count }}</span>
                    <div class="w-full rounded-t-lg border border-[var(--primary-soft-border)] transition hover:opacity-90"
                         style="height: {{ max($heightPct, 8) }}%; background: var(--primary-soft);"></div>
                    <span class="w-full truncate text-center text-xs text-[var(--text-muted)]">{{ $year }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        @if($topics->isNotEmpty())
        <div class="rounded-2xl border border-[var(--border-color)] bg-[var(--bg-card)] p-6 shadow-[var(--shadow-card)]">
            <h3 class="font-bold text-sm text-[var(--text-primary)] mb-4">Top Topics</h3>
            <div class="flex flex-wrap gap-2">
                @foreach($topics as $topic => $count)
                <span class="rounded-lg border border-[var(--border-color)] bg-[var(--bg-muted)] px-3 py-1.5 text-xs font-medium text-[var(--text-secondary)]">
                    {{ $topic }} <span class="text-[var(--text-muted)]">{{ $count }}</span>
                </span>
                @endforeach
            </div>
        </div>
        @endif
    </section>

</div>

</x-layouts.app>
