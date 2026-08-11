<x-layouts.app>

<div class="max-w-5xl mx-auto space-y-8">

    {{-- Header --}}
    <div>
        <h1 class="text-3xl font-black tracking-tight text-[var(--text-primary)]">Settings</h1>
        <p class="text-[var(--text-secondary)] mt-1 text-sm">Manage your account and preferences.</p>
    </div>

    {{-- Account Section --}}
    <div class="p-6 rounded-2xl bg-[var(--bg-card)] border border-[var(--border-color)] shadow-[var(--shadow-card)]">
        <h2 class="font-bold text-lg text-[var(--text-primary)] mb-5 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style="background: var(--primary-soft);">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--primary);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            <span>Account</span>
        </h2>

        <div class="space-y-5">
            <div class="flex flex-col gap-2 py-3 border-b border-[var(--border-color)] sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <span class="text-sm font-medium text-[var(--text-primary)]">Name</span>
                    <p class="text-xs text-[var(--text-muted)] mt-0.5">Your display name</p>
                </div>
                <span class="text-sm font-semibold text-[var(--text-primary)] break-anywhere sm:text-right">{{ $user->name }}</span>
            </div>
            <div class="flex flex-col gap-2 py-3 border-b border-[var(--border-color)] sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <span class="text-sm font-medium text-[var(--text-primary)]">Email</span>
                    <p class="text-xs text-[var(--text-muted)] mt-0.5">Your primary email address</p>
                </div>
                <span class="text-sm font-semibold text-[var(--text-primary)] break-anywhere sm:text-right">{{ $user->email }}</span>
            </div>
            <div class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <span class="text-sm font-medium text-[var(--text-primary)]">Member Since</span>
                    <p class="text-xs text-[var(--text-muted)] mt-0.5">When you joined GitRadar</p>
                </div>
                <span class="text-sm font-semibold text-[var(--text-primary)]">{{ $user->created_at->format('F Y') }}</span>
            </div>
        </div>
    </div>

    {{-- GitHub Account Section --}}
    @if($account)
    <div class="p-6 rounded-2xl bg-[var(--bg-card)] border border-[var(--border-color)] shadow-[var(--shadow-card)]">
        <h2 class="font-bold text-lg text-[var(--text-primary)] mb-5 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style="background: var(--primary-soft);">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 0C5.37 0 0 5.373 0 12c0 5.303 3.438 9.8 8.205 11.387.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 21.795 24 17.298 24 12c0-6.627-5.373-12-12-12"/>
                </svg>
            </div>
            <span>GitHub Account</span>
        </h2>

        <div class="flex flex-col sm:flex-row sm:items-start gap-5 mb-6">
            <img src="{{ $account->avatar_url }}" 
                 alt="{{ $account->username }}"
                 class="w-20 h-20 rounded-xl border border-[var(--border-color)] mx-auto sm:mx-0">
            <div class="flex-1 text-center sm:text-left">
                <a href="https://github.com/{{ $account->username }}" 
                   target="_blank"
                   rel="noopener noreferrer"
                   class="text-lg font-bold text-[var(--primary)] hover:text-[var(--primary-hover)] transition">
                    {{ $account->username }}
                </a>
                @if($account->name)
                <p class="text-sm text-[var(--text-muted)] mt-0.5">{{ $account->name }}</p>
                @endif
                @if($account->bio)
                <p class="text-sm text-[var(--text-secondary)] mt-2">{{ $account->bio }}</p>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
            <div class="p-3 rounded-xl bg-[var(--bg-muted)] border border-[var(--border-color)] text-center">
                <p class="text-xs text-[var(--text-muted)] mb-1">Followers</p>
                <p class="text-lg font-bold text-[var(--text-primary)]">{{ number_format($account->followers) }}</p>
            </div>
            <div class="p-3 rounded-xl bg-[var(--bg-muted)] border border-[var(--border-color)] text-center">
                <p class="text-xs text-[var(--text-muted)] mb-1">Following</p>
                <p class="text-lg font-bold text-[var(--text-primary)]">{{ number_format($account->following) }}</p>
            </div>
            <div class="p-3 rounded-xl bg-[var(--bg-muted)] border border-[var(--border-color)] text-center">
                <p class="text-xs text-[var(--text-muted)] mb-1">Public Repos</p>
                <p class="text-lg font-bold text-[var(--text-primary)]">{{ number_format($account->public_repos) }}</p>
            </div>
            <div class="p-3 rounded-xl bg-[var(--bg-muted)] border border-[var(--border-color)] text-center">
                <p class="text-xs text-[var(--text-muted)] mb-1">Public Gists</p>
                <p class="text-lg font-bold text-[var(--text-primary)]">{{ number_format($account->public_gists) }}</p>
            </div>
        </div>

        @if($account->company || $account->location || $account->blog)
        <div class="space-y-3 mb-6 p-4 rounded-xl bg-[var(--bg-muted)] border border-[var(--border-color)]">
            <h3 class="text-sm font-medium text-[var(--text-primary)] mb-2">Profile Information</h3>
            @if($account->company)
            <div class="flex items-center gap-2 text-sm text-[var(--text-secondary)]">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                <span>{{ $account->company }}</span>
            </div>
            @endif
            @if($account->location)
            <div class="flex items-center gap-2 text-sm text-[var(--text-secondary)]">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span>{{ $account->location }}</span>
            </div>
            @endif
            @if($account->blog)
            <div class="flex items-center gap-2 text-sm text-[var(--text-secondary)]">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
                @if($account->portfolio_url)
                <a href="{{ $account->portfolio_url }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="text-[var(--primary)] hover:text-[var(--primary-hover)] transition truncate max-w-[40ch] hover:underline">
                    {{ $account->blog }}
                </a>
                @else
                <span>{{ $account->blog }}</span>
                @endif
            </div>
            @endif
        </div>
        @endif

        <div class="space-y-4">
            <form action="{{ route('settings.sync') }}" method="POST" x-data="{ syncing: false }" @submit="syncing = true">
                @csrf
                <button type="submit"
                        :disabled="syncing"
                        :aria-busy="syncing.toString()"
                        :class="syncing ? 'opacity-70 cursor-not-allowed' : ''"
                        class="w-full flex items-center justify-center gap-2 px-5 py-3 rounded-xl font-semibold text-sm transition-all duration-200 text-white"
                        style="background: var(--primary);">
                    <svg class="w-4 h-4" :class="syncing ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span x-text="syncing ? 'Syncing...' : 'Sync Repositories from GitHub'">Sync Repositories from GitHub</span>
                </button>
            </form>

            <p class="text-xs text-[var(--text-muted)] text-center">
                Last synchronized: {{ $account->updated_at->diffForHumans() }}
            </p>
        </div>
    </div>
    @else
    <div class="p-8 rounded-2xl border shadow-[var(--shadow-card)] text-center" style="background: var(--danger-soft); border-color: var(--danger-soft-border);">
        <svg class="w-12 h-12 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--danger);">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <h3 class="font-bold text-[var(--danger)] mb-1">No GitHub Account Connected</h3>
        <p class="text-sm text-[var(--text-secondary)]">Please reconnect your GitHub account.</p>
    </div>
    @endif

    {{-- Appearance Section --}}
    <div class="p-6 rounded-2xl bg-[var(--bg-card)] border border-[var(--border-color)] shadow-[var(--shadow-card)]">
        <h2 class="font-bold text-lg text-[var(--text-primary)] mb-5 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style="background: var(--primary-soft);">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--primary);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                </svg>
            </div>
            <span>Appearance</span>
        </h2>

        <div class="space-y-4">
            <div class="flex flex-col gap-3 py-3 border-b border-[var(--border-color)] sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <span class="text-sm font-medium text-[var(--text-primary)]">Theme</span>
                    <p class="text-xs text-[var(--text-muted)] mt-0.5">Choose between light, dark, or system theme</p>
                </div>
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
                    class="relative w-full sm:w-auto">
                    <button type="button"
                            @click="open = !open"
                            aria-haspopup="menu"
                            :aria-expanded="open.toString()"
                            class="flex w-full items-center justify-between gap-2 px-4 py-2 rounded-xl border border-[var(--border-color)] bg-[var(--bg-muted)] hover:bg-[var(--bg-hover)] text-sm font-medium text-[var(--text-primary)] transition sm:w-auto">
                        <span x-text="pref === 'dark' ? 'Dark' : pref === 'system' ? 'System' : 'Light'">Theme</span>
                        <svg class="w-4 h-4 text-[var(--text-muted)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open"
                         x-transition
                         @click.outside="open = false"
                         role="menu"
                         class="absolute right-0 z-20 mt-2 w-full min-w-36 rounded-xl border border-[var(--border-color)] bg-[var(--bg-card)] py-1.5 shadow-lg sm:w-36"
                         style="display:none;">
                        <template x-for="option in options" :key="option.value">
                            <button type="button"
                                    @click="apply(option.value)"
                                    role="menuitemradio"
                                    :aria-checked="(pref === option.value).toString()"
                                    class="flex w-full items-center justify-between px-3.5 py-2 text-left text-sm text-[var(--text-secondary)] transition hover:bg-[var(--bg-hover)] hover:text-[var(--text-primary)]"
                                    :class="pref === option.value ? 'font-semibold text-[var(--text-primary)]' : ''">
                                <span x-text="option.label"></span>
                                <svg x-show="pref === option.value" class="h-3.5 w-3.5 text-[var(--primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Danger Zone --}}
    <div class="p-6 rounded-2xl bg-[var(--bg-card)] border border-[var(--border-color)] shadow-[var(--shadow-card)]">
        <h2 class="font-bold text-lg text-[var(--text-primary)] mb-5 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style="background: var(--danger-soft);">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--danger);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <span>Danger Zone</span>
        </h2>

        <div class="space-y-5">
            <div class="p-4 rounded-xl border" style="background: var(--danger-soft); border-color: var(--danger-soft-border);">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-semibold mb-1" style="color: var(--danger);">Sign Out</h3>
                        <p class="text-xs text-[var(--text-muted)]">End your current session and return to the home page.</p>
                    </div>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit"
                                aria-label="Sign out of GitRadar"
                                class="flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl border text-sm font-medium transition-all duration-200 whitespace-nowrap shrink-0"
                                style="background: var(--danger); color: white; border-color: transparent;">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            Sign Out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>

</x-layouts.app>
