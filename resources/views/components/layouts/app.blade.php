@props(['title' => null])

@php
$pageTitles = [
    'dashboard'           => 'Dashboard',
    'repositories.index'  => 'Repositories',
    'repositories.show'   => 'Repository',
    'analysis'            => 'AI Analysis',
    'insights'            => 'Insights',
    'profile.index'       => 'Profile',
    'settings'            => 'Settings',
];
$currentRoute = request()->route()?->getName();
$headerTitle = $title ?? ($pageTitles[$currentRoute] ?? null);
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    {{-- Resolve and apply the theme before first paint to prevent a flash of
         the wrong theme. Light is the default for every new visitor. --}}
    <script>
        (function () {
            try {
                var stored = localStorage.getItem('gitradar-theme') || localStorage.getItem('devscore-theme') || 'light';
                var theme = stored === 'dark' ? 'dark' : 'light';

                if (stored !== theme) {
                    localStorage.setItem('gitradar-theme', theme);
                }

                if (theme === 'dark') {
                    document.documentElement.classList.add('dark');
                }

                document.documentElement.setAttribute('data-theme-pref', theme);
            } catch (e) { /* localStorage unavailable — fall back to light */ }
        })();
    </script>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="GitRadar — GitHub portfolio scores in less than 1 minute, recruiter insights, and career recommendations.">
    <meta property="og:title" content="{{ config('app.name', 'GitRadar') }}{{ isset($title) ? ' — ' . $title : '' }}">
    <meta property="og:description" content="GitHub portfolio analysis for developers">
    <meta property="og:type" content="website">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <title>{{ config('app.name', 'GitRadar') }}{{ isset($title) ? ' — ' . $title : '' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>

<body class="bg-[var(--bg-primary)] text-[var(--text-primary)] antialiased min-h-screen"
      x-data="{ sidebarOpen: false }"
      x-effect="document.body.classList.toggle('drawer-open', sidebarOpen && window.innerWidth < 1024)"
      @keydown.escape.window="sidebarOpen = false"
      @resize.window="if (window.innerWidth >= 1024) sidebarOpen = false">

    <a href="#main-content" class="skip-link">Skip to content</a>

    {{-- Subtle grid + single ambient glow — developer-tool feel, not generic AI gradient soup --}}
    <div class="app-shell-bg pointer-events-none fixed inset-0 overflow-hidden" aria-hidden="true"></div>

    <div class="relative z-10 flex min-h-screen min-w-0">

        {{-- Drawer backdrop (mobile + tablet) --}}
        <div x-show="sidebarOpen"
             x-transition:enter="transition-opacity ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="sidebarOpen = false"
             aria-hidden="true"
             class="fixed inset-0 z-20 bg-black/50 backdrop-blur-sm lg:hidden"
             style="display:none">
        </div>

        {{-- Sidebar --}}
        <x-sidebar />

        {{-- Main content --}}
        <div class="flex min-w-0 flex-1 flex-col lg:ml-72">

            {{-- Top bar --}}
            <header class="sticky top-0 z-10 flex items-center justify-between gap-2 border-b border-[var(--border-color)] bg-[var(--bg-primary)]/90 px-3 py-3 backdrop-blur-xl sm:gap-3 sm:px-6 sm:py-4">
                <div class="flex min-w-0 flex-1 items-center gap-2 sm:gap-3">
                    {{-- Compact header: menu + branding (mobile + tablet) --}}
                    <button type="button"
                            @click="sidebarOpen = !sidebarOpen"
                            aria-label="Toggle navigation menu"
                            aria-controls="app-sidebar"
                            :aria-expanded="sidebarOpen.toString()"
                            class="touch-target shrink-0 rounded-lg p-2 text-[var(--text-secondary)] transition hover:bg-[var(--bg-hover)] hover:text-[var(--text-primary)] lg:hidden">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>

                    {{-- Compact branding + page context (mobile + tablet) --}}
                    <div class="min-w-0 lg:hidden">
                        <x-gitradar-logo variant="compact" class="!gap-0" />
                        @if($headerTitle)
                        <p class="truncate text-xs font-medium text-[var(--text-muted)]">{{ $headerTitle }}</p>
                        @endif
                    </div>

                    {{-- Desktop page context --}}
                    @if($headerTitle)
                    <div class="hidden min-w-0 lg:block">
                        <p class="section-kicker">GitRadar</p>
                        <p class="truncate text-sm font-semibold text-[var(--text-primary)]">{{ $headerTitle }}</p>
                    </div>
                    @endif

                    {{-- Flash messages (desktop/tablet inline; mobile below header row when present) --}}
                    <div class="hidden min-w-0 flex-1 sm:flex sm:flex-wrap sm:items-center sm:gap-2">
                        @if(session('success'))
                        <div class="flex max-w-full items-center gap-2 truncate rounded-lg px-3 py-2 text-sm font-medium sm:px-4"
                             style="background: var(--success-soft); border: 1px solid var(--success-soft-border); color: var(--success);">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="truncate">{{ session('success') }}</span>
                        </div>
                        @endif

                        @if(session('error'))
                        <div class="flex max-w-full items-center gap-2 truncate rounded-lg px-3 py-2 text-sm font-medium sm:px-4"
                             style="background: var(--danger-soft); border: 1px solid var(--danger-soft-border); color: var(--danger);">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="truncate">{{ session('error') }}</span>
                        </div>
                        @endif

                        @if(session('info'))
                        <div class="flex max-w-full items-center gap-2 truncate rounded-lg px-3 py-2 text-sm font-medium sm:px-4"
                             style="background: var(--info-soft); border: 1px solid var(--info-soft-border); color: var(--info);">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="truncate">{{ session('info') }}</span>
                        </div>
                        @endif
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                    {{-- Theme toggle (light ↔ dark) --}}
                    <div x-data="themeToggle">
                        <button type="button"
                                @click="toggle()"
                                aria-label="Toggle theme"
                                :aria-pressed="(pref === 'dark').toString()"
                                class="touch-target flex h-9 w-9 items-center justify-center rounded-lg border border-[var(--border-color)] bg-[var(--bg-card)] text-[var(--text-secondary)] transition hover:bg-[var(--bg-hover)] hover:text-[var(--text-primary)]">
                            <svg x-show="pref === 'light'" x-cloak class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            <svg x-show="pref === 'dark'" x-cloak class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                            </svg>
                        </button>
                    </div>

                    {{-- User avatar / name — links to Profile --}}
                    @auth
                    <a href="{{ route('profile.index') }}"
                       aria-label="Open your profile"
                       class="touch-target flex max-w-[9rem] items-center gap-2 rounded-full border border-[var(--border-color)] bg-[var(--bg-card)] py-1 pl-1 pr-2.5 transition hover:bg-[var(--bg-hover)] sm:max-w-none sm:pr-3">
                        @if(auth()->user()->githubAccount?->avatar_url)
                        <img src="{{ auth()->user()->githubAccount->avatar_url }}"
                             alt="{{ auth()->user()->name }}"
                             class="h-7 w-7 shrink-0 rounded-full border border-[var(--border-color)] object-cover">
                        @else
                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full" style="background: var(--primary-soft); color: var(--primary);">
                            <span class="text-xs font-bold">{{ substr(auth()->user()->name, 0, 1) }}</span>
                        </div>
                        @endif
                        <span class="hidden truncate text-sm font-medium text-[var(--text-primary)] sm:block">{{ auth()->user()->name }}</span>
                    </a>
                    @endauth
                </div>
            </header>

            {{-- Mobile flash messages --}}
            @if(session('success') || session('error') || session('info'))
            <div class="space-y-2 border-b border-[var(--border-color)] px-3 py-2 sm:hidden">
                @if(session('success'))
                <div class="rounded-lg px-3 py-2 text-sm font-medium" style="background: var(--success-soft); border: 1px solid var(--success-soft-border); color: var(--success);">
                    {{ session('success') }}
                </div>
                @endif
                @if(session('error'))
                <div class="rounded-lg px-3 py-2 text-sm font-medium" style="background: var(--danger-soft); border: 1px solid var(--danger-soft-border); color: var(--danger);">
                    {{ session('error') }}
                </div>
                @endif
                @if(session('info'))
                <div class="rounded-lg px-3 py-2 text-sm font-medium" style="background: var(--info-soft); border: 1px solid var(--info-soft-border); color: var(--info);">
                    {{ session('info') }}
                </div>
                @endif
            </div>
            @endif

            {{-- Page content --}}
            <main id="main-content" class="min-w-0 flex-1 px-3 py-5 sm:px-6 sm:py-8" tabindex="-1">
                {{ $slot }}
            </main>

        </div>
    </div>

    @stack('scripts')
</body>
</html>
