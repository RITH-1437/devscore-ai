<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        use App\Support\Seo;

        $canonicalUrl = Seo::publicUrl().'/';
        $seoDescription = 'GitRadar analyzes your GitHub portfolio with AI — get portfolio scores in under a minute, recruiter-ready insights, and actionable career recommendations for developers.';
        $ogImage = Seo::publicUrl().'/'.ltrim(config('seo.og_image'), '/');
        $siteName = config('app.name', 'GitRadar');
        $pageTitle = 'GitRadar — AI-Powered GitHub Portfolio Analysis';
    @endphp
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $seoDescription }}">
    <meta name="twitter:image" content="{{ $ogImage }}">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'WebSite',
                'name' => $siteName,
                'url' => $canonicalUrl,
                'description' => $seoDescription,
            ],
            [
                '@type' => 'SoftwareApplication',
                'name' => $siteName,
                'applicationCategory' => 'DeveloperApplication',
                'operatingSystem' => 'Web',
                'url' => $canonicalUrl,
                'description' => $seoDescription,
                'offers' => [
                    '@type' => 'Offer',
                    'price' => '0',
                    'priceCurrency' => 'USD',
                ],
            ],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#080C14] text-white antialiased">

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
    <div class="pointer-events-none fixed inset-0 overflow-hidden" aria-hidden="true">
        <div class="absolute inset-0 opacity-[0.35]" style="background-image: linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px); background-size: 28px 28px;"></div>
        <div class="absolute top-[-8%] left-[10%] h-[420px] w-[420px] rounded-full bg-orange-600/15 blur-[100px]"></div>
    </div>

    {{-- Nav --}}
    <header class="relative z-50">
        <nav class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-4 sm:px-6 sm:py-5" aria-label="Primary">
            <a href="{{ url('/') }}" class="flex items-center gap-3" aria-label="GitRadar home">
                <span class="h-10 w-auto inline-flex text-gray-100 [&>svg]:h-full [&>svg]:w-auto">
                    {!! str_replace('<svg', '<svg role="img" aria-label="GitRadar logo"', file_get_contents(public_path('images/logo.svg'))) !!}
                </span>
            </a>

            <a href="{{ route('github.login') }}"
               class="flex shrink-0 items-center gap-2 rounded-xl border border-white/10 bg-white/10 px-3 py-2.5 text-xs font-semibold backdrop-blur-sm transition-all duration-200 hover:border-white/20 hover:bg-white/15 sm:gap-2.5 sm:px-5 sm:text-sm">
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M12 0C5.37 0 0 5.373 0 12c0 5.303 3.438 9.8 8.205 11.387.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 21.795 24 17.298 24 12c0-6.627-5.373-12-12-12"/>
                </svg>
                <span class="sm:hidden">Sign in</span>
                <span class="hidden sm:inline">Sign in with GitHub</span>
            </a>
        </nav>
    </header>

    <main id="main-content">
    {{-- Hero --}}
    <section class="relative z-10 mx-auto max-w-5xl px-4 pb-16 pt-12 text-center sm:px-6 sm:pb-20 sm:pt-24" aria-labelledby="hero-heading">

        <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-orange-500/25 bg-orange-500/10 px-3 py-1.5 text-xs font-medium text-white sm:mb-8 sm:px-4 sm:py-2 sm:text-sm">
            AI GitHub portfolio analysis · multi-model scoring
        </div>

        <h1 id="hero-heading" class="mb-5 text-4xl font-bold leading-[1.1] tracking-tight sm:mb-6 sm:text-6xl lg:text-7xl">
            AI GitHub Portfolio Analyzer
        </h1>

        <p class="mx-auto mb-3 max-w-2xl text-xl font-semibold leading-snug text-orange-400 sm:mb-4 sm:text-2xl lg:text-3xl">
            Know how recruiters read your GitHub profile
        </p>

        <p class="mx-auto mb-8 max-w-2xl text-base leading-relaxed text-slate-400 sm:mb-10 sm:text-lg">
            GitRadar scores your repositories, highlights portfolio strengths and gaps, and turns them into
            actionable improvements for developers — synced straight from GitHub in under a minute.
        </p>

        <div class="flex flex-col items-center justify-center gap-3 sm:flex-row sm:gap-4">
            <a href="{{ route('github.login') }}"
               class="flex w-full max-w-sm items-center justify-center gap-3 rounded-xl bg-orange-600 px-6 py-3.5 text-base font-semibold transition hover:bg-orange-500 sm:w-auto sm:px-8 sm:py-4 sm:text-lg">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 0C5.37 0 0 5.373 0 12c0 5.303 3.438 9.8 8.205 11.387.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 21.795 24 17.298 24 12c0-6.627-5.373-12-12-12"/>
                </svg>
                Connect - Free
            </a>
            <span class="text-slate-500 text-sm">Free · GitHub OAuth · under a minute</span>
        </div>
    </section>

    {{-- Feature Cards --}}
    <section class="relative z-10 mx-auto max-w-7xl px-4 py-14 sm:px-6 sm:py-20">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3 md:gap-6">

            @php
            $features = [
                [
                    'title' => 'GitHub portfolio score',
                    'desc'  => 'A 0–100 AI score across code quality, documentation, activity, and how your open-source work reads to hiring teams.',
                ],
                [
                    'title' => 'Recruiter insights',
                    'desc'  => 'See which repositories stand out, which look unfinished, and where your developer portfolio loses credibility.',
                ],
                [
                    'title' => 'Actionable next steps',
                    'desc'  => 'Concrete recommendations per repo — README gaps, showcase projects, and skills to highlight on your GitHub profile.',
                ],
            ];
            @endphp

            @foreach($features as $f)
            <div class="rounded-xl border border-white/[0.08] bg-white/[0.03] p-5 shadow-[0_1px_2px_rgba(0,0,0,0.18)] backdrop-blur-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-white/[0.14] hover:bg-white/[0.05] hover:shadow-[0_4px_14px_rgba(0,0,0,0.22)] sm:p-6">
                <h3 class="mb-2 text-base font-semibold text-orange-300">{{ $f['title'] }}</h3>
                <p class="text-sm leading-relaxed text-slate-400">{{ $f['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </section>

    {{-- Stats --}}
    <section class="relative z-10 mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-12">
        <div class="rounded-xl border border-white/[0.07] bg-white/[0.02] p-6 sm:p-8">
            <div class="grid grid-cols-2 gap-6 text-center md:grid-cols-4 md:gap-8">
                @foreach([
                    ['Repos', 'Per-project scores'],
                    ['Docs', 'README & activity'],
                    ['Profile', 'Recruiter view'],
                    ['<1 min', 'First analysis'],
                ] as [$val, $label])
                <div>
                    <div class="mb-1 text-xl font-semibold text-orange-300 sm:text-2xl">{{ $val }}</div>
                    <div class="text-slate-500 text-xs sm:text-sm">{{ $label }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="relative z-10 mx-auto max-w-3xl px-4 py-16 text-center sm:px-6 sm:py-24" aria-labelledby="cta-heading">
        <h2 id="cta-heading" class="mb-3 text-2xl font-bold sm:mb-4 sm:text-3xl">Analyze your GitHub portfolio with AI</h2>
        <p class="mb-6 text-sm text-slate-400 sm:mb-8 sm:text-base">No manual uploads. Sign in with GitHub, sync repositories, and get your first portfolio analysis in under a minute.</p>
        <a href="{{ route('github.login') }}"
           class="inline-flex w-full max-w-sm items-center justify-center gap-3 rounded-xl bg-orange-600 px-6 py-3.5 text-base font-semibold transition hover:bg-orange-500 sm:w-auto sm:px-8 sm:py-4 sm:text-lg">
            Get Started Free
        </a>
    </section>
    </main>

    {{-- Footer --}}
    <footer class="relative z-10 border-t border-white/[0.06] px-4 py-6 text-center text-xs text-slate-600 sm:px-6 sm:py-8 sm:text-sm">
        <p>GitRadar &copy; {{ date('Y') }} · AI-powered GitHub portfolio analysis for developers</p>
    </footer>

</body>
</html>
