<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="GitRadar — AI Portfolio Analyzer. Get GitHub portfolio scores in less than 1 minute, recruiter insights, and career recommendations.">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <title>GitRadar — AI Portfolio Analyzer</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#080C14] text-white antialiased overflow-x-hidden">

    {{-- Flash / validation errors --}}
    @if ($errors->any())
        <div class="relative z-50 px-6 py-4 bg-red-500/10 border-b border-red-500/30">
            <div class="max-w-7xl mx-auto flex items-center gap-3">
                <svg class="w-5 h-5 text-red-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
                <div class="text-red-300 text-sm font-medium">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Ambient background --}}
    <div class="fixed inset-0 pointer-events-none">
        <div class="absolute top-[-10%] left-[-5%] w-[600px] h-[600px] bg-orange-600/20 rounded-full blur-[120px]"></div>
        <div class="absolute top-[30%] right-[-10%] w-[500px] h-[500px] bg-violet-600/15 rounded-full blur-[120px]"></div>
        <div class="absolute bottom-[-10%] left-[30%] w-[400px] h-[400px] bg-orange-500/10 rounded-full blur-[100px]"></div>
    </div>

    {{-- Nav --}}
    <nav class="relative z-50 flex items-center justify-between px-6 py-5 max-w-7xl mx-auto">
        <a href="{{ url('/') }}" class="flex items-center gap-3" aria-label="GitRadar home">
            <span class="h-10 w-auto inline-flex text-gray-100 [&>svg]:h-full [&>svg]:w-auto" aria-hidden="true">
                {!! str_replace('<svg', '<svg role="img" aria-label="GitRadar"', file_get_contents(public_path('images/logo.svg'))) !!}
            </span>
        </a>

        <a href="{{ route('github.login') }}"
           class="flex items-center gap-2.5 px-5 py-2.5 rounded-xl bg-white/10 hover:bg-white/15 border border-white/10 hover:border-white/20 text-sm font-semibold transition-all duration-200 backdrop-blur-sm">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 0C5.37 0 0 5.373 0 12c0 5.303 3.438 9.8 8.205 11.387.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 21.795 24 17.298 24 12c0-6.627-5.373-12-12-12"/>
            </svg>
            Sign in with GitHub
        </a>
    </nav>

    {{-- Hero --}}
    <section class="relative z-10 pt-24 pb-20 px-6 text-center max-w-5xl mx-auto">

        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-orange-500/10 border border-orange-500/20 text-orange-300 text-sm font-medium mb-8">
            <span class="w-2 h-2 rounded-full bg-orange-400 animate-pulse"></span>
            Powered By Cross Multiple AI.
        </div>

        <h1 class="text-6xl sm:text-7xl font-black tracking-tight leading-[1.05] mb-6">
            Score Your GitHub
            <span class="block text-orange-400">
                Portfolio with AI
            </span>
        </h1>

        <p class="text-xl text-slate-400 max-w-2xl mx-auto mb-10 leading-relaxed">
            Connect your GitHub account and get AI-powered scores in less than 1 minute, recruiter insights,
            career recommendations, and improvement roadmaps across your entire portfolio.
        </p>

        <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
            <a href="{{ route('github.login') }}"
               class="flex items-center gap-3 px-8 py-4 rounded-2xl bg-orange-600 hover:bg-orange-500 font-bold text-lg shadow-2xl shadow-orange-500/30 hover:shadow-orange-500/50 transition-all duration-300 hover:-translate-y-0.5">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 0C5.37 0 0 5.373 0 12c0 5.303 3.438 9.8 8.205 11.387.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 21.795 24 17.298 24 12c0-6.627-5.373-12-12-12"/>
                </svg>
                Analyze My Portfolio — Free
            </a>
            <span class="text-slate-500 text-sm">No credit card · Results in less than 1 minute</span>
        </div>
    </section>

    {{-- Feature Cards --}}
    <section class="relative z-10 py-20 px-6 max-w-7xl mx-auto">
        <div class="grid md:grid-cols-3 gap-6">

            @php
            $features = [
                [
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>',
                    'color' => 'orange',
                    'title' => 'Portfolio Score',
                    'desc'  => 'Get a comprehensive 0–100 score across code quality, documentation, activity, and recruiter appeal.',
                ],
                [
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>',
                    'color' => 'violet',
                    'title' => 'Recruiter Insights',
                    'desc'  => 'Understand how recruiters at top companies see your GitHub profile. Get a hiring probability score.',
                ],
                [
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>',
                    'color' => 'violet',
                    'title' => 'AI Recommendations',
                    'desc'  => 'Receive personalized action items: what to build, how to document, and which companies to target.',
                ],
            ];
            @endphp

            @foreach($features as $f)
            <div class="group p-7 rounded-2xl bg-white/[0.04] border border-white/[0.08] hover:bg-white/[0.07] hover:border-white/[0.15] transition-all duration-300 backdrop-blur-sm">
                <div class="w-12 h-12 rounded-xl bg-{{ $f['color'] }}-500/10 border border-{{ $f['color'] }}-500/20 flex items-center justify-center mb-5">
                    <svg class="w-6 h-6 text-{{ $f['color'] }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        {!! $f['icon'] !!}
                    </svg>
                </div>
                <h3 class="text-lg font-bold mb-2">{{ $f['title'] }}</h3>
                <p class="text-slate-400 text-sm leading-relaxed">{{ $f['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </section>

    {{-- Stats --}}
    <section class="relative z-10 py-12 px-6 max-w-7xl mx-auto">
        <div class="rounded-2xl bg-white/[0.03] border border-white/[0.07] p-10">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-10 text-center">
                @foreach([['5+', 'AI Models'], ['20+', 'Metrics Tracked'], ['Free', 'Always'], ['<1', 'Minute']] as [$val, $label])
                <div>
                    <div class="text-4xl font-black text-orange-400 mb-1">{{ $val }}</div>
                    <div class="text-slate-500 text-sm">{{ $label }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="relative z-10 py-24 px-6 text-center max-w-3xl mx-auto">
        <h2 class="text-4xl font-black mb-4">Ready to stand out?</h2>
        <p class="text-slate-400 mb-8">Join developers getting AI-powered feedback on their GitHub portfolio.</p>
        <a href="{{ route('github.login') }}"
           class="inline-flex items-center gap-3 px-8 py-4 rounded-2xl bg-orange-600 hover:bg-orange-500 font-bold text-lg transition-all duration-300 hover:-translate-y-0.5 shadow-2xl shadow-orange-500/20">
            Get Started Free
        </a>
    </section>

    {{-- Footer --}}
    <footer class="relative z-10 border-t border-white/[0.06] py-8 px-6 text-center text-slate-600 text-sm">
        <p>GitRadar &copy; {{ date('Y') }} — AI Portfolio Analyzer · Built with Laravel · Cross Multiple AI</p>
    </footer>

</body>
</html>
