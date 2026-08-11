<x-layouts.app>

<div class="max-w-5xl mx-auto space-y-6">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-slate-500">
        <a href="{{ route('repositories.index') }}" class="hover:text-white transition">Repositories</a>
        <span>/</span>
        <span class="text-white">{{ $repository->name }}</span>
    </nav>

    {{-- Repository Header --}}
    <div class="p-6 rounded-2xl bg-white/[0.04] border border-white/[0.08]">
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-3 flex-wrap mb-2">
                    <h1 class="text-2xl font-black">{{ $repository->name }}</h1>

                    @if($repository->language)
                    <span class="px-2.5 py-1 text-xs font-medium rounded-lg bg-violet-500/10 border border-violet-500/20 text-violet-300">
                        {{ $repository->language }}
                    </span>
                    @endif

                    @if($repository->is_archived)
                    <span class="px-2.5 py-1 text-xs font-medium rounded-lg bg-amber-500/10 border border-amber-500/20 text-amber-400">
                        Archived
                    </span>
                    @endif

                    @if($repository->is_fork)
                    <span class="px-2.5 py-1 text-xs font-medium rounded-lg bg-slate-500/10 border border-slate-500/20 text-slate-400">
                        Fork
                    </span>
                    @endif
                </div>

                @if($repository->description)
                <p class="text-slate-400 text-sm mb-4">{{ $repository->description }}</p>
                @endif

                {{-- Topics --}}
                @if(!empty($repository->topics))
                <div class="flex flex-wrap gap-2 mb-4">
                    @foreach($repository->topics as $topic)
                    <span class="px-2.5 py-1 text-xs rounded-lg bg-blue-500/10 border border-blue-500/20 text-blue-300">
                        {{ $topic }}
                    </span>
                    @endforeach
                </div>
                @endif

                {{-- Stats row --}}
                <div class="flex flex-wrap items-center gap-5 text-sm text-slate-400">
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        <span>{{ $repository->stars }} stars</span>
                    </span>
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/>
                        </svg>
                        <span>{{ $repository->forks }} forks</span>
                    </span>
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <span>{{ $repository->open_issues }} issues</span>
                    </span>
                    @if($repository->license)
                    <span class="text-slate-500">{{ $repository->license }}</span>
                    @endif
                    @if($repository->pushed_at)
                    <span class="text-slate-500">Pushed {{ $repository->pushed_at->diffForHumans() }}</span>
                    @endif
                </div>
            </div>

            {{-- Right: score + actions --}}
            <div class="flex flex-col items-end gap-3 shrink-0">
                @if($repository->isAnalyzed())
                <x-score-ring :score="$repository->score" :size="90" :stroke="8" label="AI Score" />
                @endif

                <div class="flex items-center gap-2">
                    <a href="{{ $repository->html_url }}" target="_blank"
                       class="flex items-center gap-2 px-4 py-2 rounded-xl bg-white/[0.06] hover:bg-white/[0.1] border border-white/[0.1] text-sm font-medium transition">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 0C5.37 0 0 5.373 0 12c0 5.303 3.438 9.8 8.205 11.387.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 21.795 24 17.298 24 12c0-6.627-5.373-12-12-12"/>
                        </svg>
                        GitHub
                    </a>

                    <form action="{{ route('repositories.pin', $repository) }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="flex items-center gap-2 px-4 py-2 rounded-xl border text-sm font-medium transition
                                       {{ $repository->is_pinned
                                          ? 'bg-amber-500/10 border-amber-500/20 text-amber-400 hover:bg-amber-500/20'
                                          : 'bg-white/[0.06] border-white/[0.1] hover:bg-white/[0.1]' }}">
                            <svg class="w-4 h-4" fill="{{ $repository->is_pinned ? 'currentColor' : 'none' }}"
                                 stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                            </svg>
                            {{ $repository->is_pinned ? 'Pinned' : 'Pin' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Analyze Button --}}
    @if(!$repository->isAnalyzed() || $repository->analysis_status === 'failed')
    <div class="p-6 rounded-2xl bg-violet-500/[0.05] border border-violet-500/[0.15]">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h3 class="font-bold mb-1">Run AI Analysis</h3>
                <p class="text-slate-400 text-sm">
                    Get a comprehensive score, strengths, weaknesses, recruiter insights, and career recommendations.
                </p>
                @if($repository->analysis_status === 'failed')
                <div class="mt-3 flex items-start gap-2 text-red-400 text-xs rounded-xl bg-red-500/[0.08] border border-red-500/20 px-3 py-2 max-w-xl">
                    <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <span class="font-semibold">Analysis failed — retry.</span>
                        @if($failureMessage)
                        <span class="block mt-0.5 text-red-300/80">{{ $failureMessage }}</span>
                        @endif
                    </div>
                </div>
                @endif
            </div>
            <form action="{{ route('repositories.analyze', $repository) }}" method="POST"
                  class="shrink-0" x-data="{ submitting: false }" @submit="submitting = true">
                @csrf
                <button type="submit"
                        :disabled="submitting"
                        :class="submitting ? 'opacity-60 cursor-not-allowed' : ''"
                        class="flex items-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-violet-600 to-blue-600
                               hover:from-violet-500 hover:to-blue-500 font-bold text-sm shadow-lg shadow-violet-500/20
                               transition-all duration-200 hover:-translate-y-0.5">
                    <svg x-show="submitting" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <svg x-show="!submitting" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                    <span x-text="submitting ? 'Analyzing…' : 'Analyze with AI'"></span>
                </button>
            </form>
        </div>
    </div>
    @elseif($repository->isAnalyzing())
    <div class="p-6 rounded-2xl bg-blue-500/[0.05] border border-blue-500/[0.15]"
         x-data="{
            step: 0,
            steps: ['Preparing analysis…', 'Analyzing repository…', 'Processing AI results…'],
            countdown: 5,
            interval: null
         }"
         x-init="interval = setInterval(() => {
            step = (step + 1) % steps.length;
            countdown--;
            if (countdown <= 0) { clearInterval(interval); window.location.reload(); }
         }, 1000)"
         x-on:beforeunload.window="clearInterval(interval)">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-blue-400 animate-spin shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            <div class="flex-1">
                <p class="font-medium text-blue-300" x-text="steps[step]"></p>
                <p class="text-slate-400 text-sm">
                    <span x-text="'Refreshing in ' + countdown + 's...'"></span>
                </p>
            </div>
            <button type="button"
                    @click="clearInterval(interval); countdown = -1"
                    class="px-3 py-1.5 rounded-lg bg-white/[0.06] hover:bg-white/[0.10] border border-white/[0.1] text-xs text-slate-400 transition">
                Cancel auto-refresh
            </button>
        </div>
    </div>
    @endif

    {{-- Analysis Results --}}
    @if(!empty($analysis))
    @php
        $score                = $analysis['score'] ?? 0;
        $difficulty           = $analysis['difficulty'] ?? 'N/A';
        $portfolioLevel       = $analysis['portfolio_level'] ?? 'N/A';
        $recruiterRating      = $analysis['recruiter_rating'] ?? 0;
        $estimatedExp         = $analysis['estimated_experience'] ?? 'N/A';
        $hiringProb           = $analysis['hiring_probability'] ?? 0;
        $marketReadiness      = $analysis['market_readiness'] ?? 'N/A';
        $strengths            = $analysis['strengths'] ?? [];
        $weaknesses           = $analysis['weaknesses'] ?? [];
        $recommendations      = $analysis['recommendations'] ?? [];
        $architectureReview   = $analysis['architecture_review'] ?? [];
        $securityReview       = $analysis['security_review'] ?? [];
        $performanceReview    = $analysis['performance_review'] ?? [];
        $codeStyleReview      = $analysis['code_style_review'] ?? [];
        $missingFeatures      = $analysis['missing_features'] ?? [];
        $resumeSuggestions    = $analysis['resume_suggestions'] ?? [];
        $interviewQuestions   = $analysis['interview_questions'] ?? [];
        $bestCompanies        = $analysis['best_companies'] ?? [];
        $improvementRoadmap   = $analysis['improvement_roadmap'] ?? [];
    @endphp

    {{-- Scores overview --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        @foreach([
            ['label' => 'AI Score',         'value' => $score . '/100',     'color' => 'violet'],
            ['label' => 'Difficulty',        'value' => ucfirst($difficulty),'color' => 'blue'],
            ['label' => 'Level',             'value' => ucfirst($portfolioLevel), 'color' => 'cyan'],
            ['label' => 'Recruiter Rating',  'value' => $recruiterRating . '/10', 'color' => 'emerald'],
            ['label' => 'Hiring Prob.',      'value' => $hiringProb . '%',   'color' => 'amber'],
            ['label' => 'Experience',        'value' => $estimatedExp,       'color' => 'rose'],
        ] as $metric)
        <div class="p-4 rounded-xl bg-white/[0.04] border border-white/[0.08] text-center">
            <p class="text-slate-500 text-xs mb-1">{{ $metric['label'] }}</p>
            <p class="font-black text-lg leading-tight">{{ $metric['value'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- Strengths / Weaknesses / Recommendations --}}
    <div class="grid md:grid-cols-3 gap-4">

        <div class="p-5 rounded-2xl bg-emerald-500/[0.05] border border-emerald-500/[0.15]">
            <h3 class="font-bold text-sm mb-4 flex items-center gap-2 text-emerald-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Strengths
            </h3>
            @forelse($strengths as $item)
            <div class="flex items-start gap-2 py-2 border-b border-white/[0.04] last:border-0">
                <span class="text-emerald-400 mt-0.5 shrink-0">✓</span>
                <span class="text-sm text-slate-300">{{ $item }}</span>
            </div>
            @empty
            <p class="text-slate-500 text-sm italic">No strengths identified.</p>
            @endforelse
        </div>

        <div class="p-5 rounded-2xl bg-red-500/[0.05] border border-red-500/[0.15]">
            <h3 class="font-bold text-sm mb-4 flex items-center gap-2 text-red-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                Weaknesses
            </h3>
            @forelse($weaknesses as $item)
            <div class="flex items-start gap-2 py-2 border-b border-white/[0.04] last:border-0">
                <span class="text-red-400 mt-0.5 shrink-0">⚠</span>
                <span class="text-sm text-slate-300">{{ $item }}</span>
            </div>
            @empty
            <p class="text-slate-500 text-sm italic">No weaknesses identified.</p>
            @endforelse
        </div>

        <div class="p-5 rounded-2xl bg-blue-500/[0.05] border border-blue-500/[0.15]">
            <h3 class="font-bold text-sm mb-4 flex items-center gap-2 text-blue-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Recommendations
            </h3>
            @forelse($recommendations as $item)
            <div class="flex items-start gap-2 py-2 border-b border-white/[0.04] last:border-0">
                <span class="text-blue-400 mt-0.5 shrink-0">→</span>
                <span class="text-sm text-slate-300">{{ $item }}</span>
            </div>
            @empty
            <p class="text-slate-500 text-sm italic">No recommendations available.</p>
            @endforelse
        </div>
    </div>

    {{-- Technical Reviews --}}
    @php
    $reviews = [
        ['title' => 'Architecture Review',  'data' => $architectureReview,  'color' => 'violet', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>'],
        ['title' => 'Security Review',      'data' => $securityReview,      'color' => 'red',    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>'],
        ['title' => 'Performance Review',   'data' => $performanceReview,   'color' => 'amber',  'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>'],
        ['title' => 'Code Style Review',    'data' => $codeStyleReview,     'color' => 'cyan',   'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>'],
    ];
    $reviewColors = ['violet' => 'text-violet-400 bg-violet-500/10 border-violet-500/20', 'red' => 'text-red-400 bg-red-500/10 border-red-500/20', 'amber' => 'text-amber-400 bg-amber-500/10 border-amber-500/20', 'cyan' => 'text-cyan-400 bg-cyan-500/10 border-cyan-500/20'];
    @endphp

    <div class="grid md:grid-cols-2 gap-4">
        @foreach($reviews as $review)
        @if(!empty($review['data']))
        <div class="p-5 rounded-2xl bg-white/[0.04] border border-white/[0.08]">
            <h3 class="font-bold text-sm mb-4 flex items-center gap-2">
                <svg class="w-4 h-4 {{ explode(' ', $reviewColors[$review['color']])[0] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    {!! $review['icon'] !!}
                </svg>
                {{ $review['title'] }}
            </h3>
            <ul class="space-y-2">
                @foreach($review['data'] as $item)
                <li class="flex items-start gap-2 text-sm text-slate-300">
                    <span class="{{ explode(' ', $reviewColors[$review['color']])[0] }} mt-0.5 shrink-0">•</span>
                    {{ $item }}
                </li>
                @endforeach
            </ul>
        </div>
        @endif
        @endforeach
    </div>

    {{-- Career Section --}}
    <div class="grid md:grid-cols-2 gap-4">

        @if(!empty($resumeSuggestions))
        <div class="p-5 rounded-2xl bg-white/[0.04] border border-white/[0.08]">
            <h3 class="font-bold text-sm mb-4 text-emerald-400">📄 Resume Suggestions</h3>
            <ul class="space-y-2">
                @foreach($resumeSuggestions as $item)
                <li class="text-sm text-slate-300 flex items-start gap-2">
                    <span class="text-emerald-400 mt-0.5 shrink-0">→</span>{{ $item }}
                </li>
                @endforeach
            </ul>
        </div>
        @endif

        @if(!empty($interviewQuestions))
        <div class="p-5 rounded-2xl bg-white/[0.04] border border-white/[0.08]">
            <h3 class="font-bold text-sm mb-4 text-blue-400">❓ Interview Questions</h3>
            <ul class="space-y-2">
                @foreach($interviewQuestions as $item)
                <li class="text-sm text-slate-300 flex items-start gap-2">
                    <span class="text-blue-400 mt-0.5 shrink-0">Q.</span>{{ $item }}
                </li>
                @endforeach
            </ul>
        </div>
        @endif

        @if(!empty($bestCompanies))
        <div class="p-5 rounded-2xl bg-white/[0.04] border border-white/[0.08]">
            <h3 class="font-bold text-sm mb-4 text-violet-400">🏢 Best Companies to Apply</h3>
            <div class="flex flex-wrap gap-2">
                @foreach($bestCompanies as $company)
                <span class="px-3 py-1.5 rounded-lg text-xs font-medium bg-violet-500/10 border border-violet-500/20 text-violet-300">
                    {{ $company }}
                </span>
                @endforeach
            </div>
        </div>
        @endif

        @if(!empty($improvementRoadmap))
        <div class="p-5 rounded-2xl bg-white/[0.04] border border-white/[0.08]">
            <h3 class="font-bold text-sm mb-4 text-amber-400">🗺 Improvement Roadmap</h3>
            <ol class="space-y-2">
                @foreach($improvementRoadmap as $i => $item)
                <li class="flex items-start gap-3 text-sm text-slate-300">
                    <span class="w-5 h-5 rounded-full bg-amber-500/20 border border-amber-500/30 text-amber-400 text-xs flex items-center justify-center shrink-0 mt-0.5">{{ $i + 1 }}</span>
                    {{ $item }}
                </li>
                @endforeach
            </ol>
        </div>
        @endif

    </div>

    {{-- Re-analyze and Export --}}
    <div class="flex flex-wrap items-center justify-end gap-3">
        @if($repository->isAnalyzed())
        <a href="{{ route('repositories.export.json', $repository) }}"
           class="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-white/[0.06] hover:bg-white/[0.1]
                  border border-white/[0.1] text-sm font-medium transition">
            <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Export JSON
        </a>
        <a href="{{ route('repositories.export.markdown', $repository) }}"
           class="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-white/[0.06] hover:bg-white/[0.1]
                  border border-white/[0.1] text-sm font-medium transition">
            <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
            </svg>
            Export Markdown
        </a>
        @endif
        
        <form action="{{ route('repositories.analyze', $repository) }}" method="POST"
              x-data="{ submitting: false }" @submit="submitting = true">
            @csrf
            <button type="submit"
                    :disabled="submitting"
                    :class="submitting ? 'opacity-60 cursor-not-allowed' : ''"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-xl bg-white/[0.06] hover:bg-white/[0.1]
                           border border-white/[0.1] text-sm font-medium transition">
                <svg x-show="submitting" class="w-4 h-4 text-violet-400 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <svg x-show="!submitting" class="w-4 h-4 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span x-text="submitting ? 'Analyzing…' : 'Re-analyze'"></span>
            </button>
        </form>
    </div>

    @endif

</div>

</x-layouts.app>
