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
                var resolved = stored === 'system'
                    ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
                    : stored;
                if (resolved === 'dark') {
                    document.documentElement.classList.add('dark');
                }
                document.documentElement.setAttribute('data-theme-pref', stored);
                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function () {
                    try {
                        var pref = localStorage.getItem('gitradar-theme') || localStorage.getItem('devscore-theme') || 'light';
                        if (pref !== 'system') return;
                        document.documentElement.classList.toggle('dark', window.matchMedia('(prefers-color-scheme: dark)').matches);
                    } catch (e) {}
                });
            } catch (e) { /* localStorage unavailable — fall back to light */ }
        })();
    </script>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="GitRadar - AI Portfolio Analyzer for GitHub. Get scores in 3-5 minutes, recruiter insights, and career recommendations.">
    <meta property="og:title" content="{{ config('app.name', 'GitRadar') }}{{ isset($title) ? ' — ' . $title : '' }}">
    <meta property="og:description" content="AI Portfolio Analyzer for GitHub developers">
    <meta property="og:type" content="website">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <title>{{ config('app.name', 'GitRadar') }}{{ isset($title) ? ' — ' . $title : '' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>

<body class="bg-[var(--bg-primary)] text-[var(--text-primary)] antialiased min-h-screen"
      x-data="{ sidebarOpen: false }"
      @keydown.escape.window="sidebarOpen = false">

    <a href="#main-content" class="skip-link">Skip to content</a>

    {{-- Ambient background glows --}}
    <div class="fixed inset-0 pointer-events-none overflow-hidden">
        <div class="absolute top-0 left-0 w-[500px] h-[500px] rounded-full blur-[150px] -translate-x-1/2 -translate-y-1/2" style="background: var(--glow-primary);"></div>
        <div class="absolute bottom-0 right-0 w-[600px] h-[600px] rounded-full blur-[150px] translate-x-1/3 translate-y-1/3" style="background: var(--glow-secondary);"></div>
    </div>

    <div class="relative z-10 flex min-h-screen">

        {{-- Sidebar overlay (mobile) --}}
        <div x-show="sidebarOpen"
             x-transition:enter="transition-opacity ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="sidebarOpen = false"
             aria-hidden="true"
             class="fixed inset-0 bg-black/50 backdrop-blur-sm z-20 lg:hidden"
             style="display:none">
        </div>

        {{-- Sidebar --}}
        <x-sidebar />

        {{-- Main content --}}
        <div class="flex-1 flex flex-col min-w-0 lg:ml-72">

            {{-- Top bar --}}
            <header class="sticky top-0 z-10 flex items-center justify-between gap-3 px-4 py-4 border-b border-[var(--border-color)] bg-[var(--bg-primary)]/85 backdrop-blur-xl sm:px-6">
                <div class="flex items-center gap-3 min-w-0 sm:gap-4">
                    {{-- Mobile menu toggle --}}
                    <button type="button"
                            @click="sidebarOpen = !sidebarOpen"
                            aria-label="Toggle navigation menu"
                            aria-controls="app-sidebar"
                            :aria-expanded="sidebarOpen.toString()"
                            class="lg:hidden p-2 rounded-lg text-[var(--text-secondary)] hover:bg-[var(--bg-hover)] hover:text-[var(--text-primary)] transition shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>

                    {{-- Flash messages --}}
                    @if(session('success'))
                    <div class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium truncate"
                         style="background: var(--success-soft); border: 1px solid var(--success-soft-border); color: var(--success);">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="truncate">{{ session('success') }}</span>
                    </div>
                    @endif

                    @if(session('error'))
                    <div class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium truncate"
                         style="background: var(--danger-soft); border: 1px solid var(--danger-soft-border); color: var(--danger);">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="truncate">{{ session('error') }}</span>
                    </div>
                    @endif

                    @if(session('info'))
                    <div class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium truncate"
                         style="background: var(--info-soft); border: 1px solid var(--info-soft-border); color: var(--info);">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="truncate">{{ session('info') }}</span>
                    </div>
                    @endif
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    {{-- Theme switcher --}}
                    <div x-data="{
                            open: false,
                            pref: localStorage.getItem('gitradar-theme') || localStorage.getItem('devscore-theme') || 'light',
                            options: [
                                { value: 'light', label: 'Light' },
                                { value: 'dark', label: 'Dark' },
                                { value: 'system', label: 'System' },
                            ],
                            apply(value) {
                                this.pref = value;
                                localStorage.setItem('gitradar-theme', value);
                                document.documentElement.setAttribute('data-theme-pref', value);
                                const resolved = value === 'system'
                                    ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
                                    : value;
                                document.documentElement.classList.toggle('dark', resolved === 'dark');
                                this.open = false;
                            }
                        }"
                        @keydown.escape.window="open = false"
                        class="relative">
                        <button @click="open = !open"
                                type="button"
                                aria-label="Change theme"
                                aria-haspopup="menu"
                                :aria-expanded="open.toString()"
                                class="flex items-center justify-center w-9 h-9 rounded-lg border border-[var(--border-color)] bg-[var(--bg-card)] text-[var(--text-secondary)] hover:text-[var(--text-primary)] hover:bg-[var(--bg-hover)] transition">
                            <svg x-show="pref === 'light'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            <svg x-show="pref === 'dark'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                            </svg>
                            <svg x-show="pref === 'system'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </button>

                        <div x-show="open"
                             x-transition
                             @click.outside="open = false"
                             role="menu"
                             class="absolute right-0 mt-2 w-36 rounded-xl border border-[var(--border-color)] bg-[var(--bg-card)] shadow-lg py-1.5 z-20"
                             style="display:none;">
                            <template x-for="option in options" :key="option.value">
                                <button type="button"
                                        @click="apply(option.value)"
                                        role="menuitemradio"
                                        :aria-checked="(pref === option.value).toString()"
                                        class="w-full flex items-center justify-between px-3.5 py-2 text-sm text-[var(--text-secondary)] hover:bg-[var(--bg-hover)] hover:text-[var(--text-primary)] transition"
                                        :class="pref === option.value ? 'text-[var(--text-primary)] font-semibold' : ''">
                                    <span x-text="option.label"></span>
                                    <svg x-show="pref === option.value" class="w-3.5 h-3.5 text-[var(--primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- User avatar / name — links to Profile --}}
                    @auth
                    <a href="{{ route('profile.index') }}"
                       aria-label="Open your profile"
                       class="flex items-center gap-2.5 pl-1 pr-3 py-1 rounded-full border border-[var(--border-color)] bg-[var(--bg-card)] hover:bg-[var(--bg-hover)] transition group">
                        @if(auth()->user()->githubAccount?->avatar_url)
                        <img src="{{ auth()->user()->githubAccount->avatar_url }}"
                             alt="{{ auth()->user()->name }}"
                             class="w-7 h-7 rounded-full border border-[var(--border-color)]">
                        @else
                        <div class="w-7 h-7 rounded-full flex items-center justify-center shrink-0" style="background: var(--primary-soft); color: var(--primary);">
                            <span class="font-bold text-xs">{{ substr(auth()->user()->name, 0, 1) }}</span>
                        </div>
                        @endif
                        <span class="text-sm font-medium text-[var(--text-primary)] hidden sm:block group-hover:text-[var(--primary)] transition">{{ auth()->user()->name }}</span>
                    </a>
                    @endauth
                </div>
            </header>

            {{-- Page content --}}
            <main id="main-content" class="flex-1 px-4 py-6 sm:px-6 sm:py-8" tabindex="-1">
                {{ $slot }}
            </main>

        </div>
    </div>

    @stack('scripts')
</body>
</html>
