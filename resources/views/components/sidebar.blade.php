@php
    $currentRoute = request()->route()?->getName();
    $navItems = [
        [
            'route'   => 'dashboard',
            'label'   => 'Overview',
            'icon'    => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/>',
            'matches' => ['dashboard'],
        ],
        [
            'route'   => 'repositories.index',
            'label'   => 'Repositories',
            'icon'    => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>',
            'matches' => ['repositories.index', 'repositories.show'],
        ],
        [
            'route'   => 'analysis',
            'label'   => 'AI Analysis',
            'icon'    => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>',
            'matches' => ['analysis'],
        ],
        [
            'route'   => 'insights',
            'label'   => 'Insights',
            'icon'    => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>',
            'matches' => ['insights'],
        ],
        [
            'route'   => 'profile.index',
            'label'   => 'Profile',
            'icon'    => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>',
            'matches' => ['profile.index'],
        ],
        [
            'route'   => 'settings',
            'label'   => 'Settings',
            'icon'    => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>',
            'matches' => ['settings'],
        ],
    ];
@endphp

<aside id="app-sidebar"
       aria-label="Primary navigation"
       class="fixed top-0 left-0 h-full w-72 z-30 flex flex-col
              border-r border-[var(--border-color)]
              bg-[var(--bg-secondary)]/95 backdrop-blur-xl
              transition-transform duration-300 ease-out
              -translate-x-full lg:translate-x-0"
       :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">

    {{-- Logo --}}
    <div class="px-6 py-6 border-b border-[var(--border-color)]">
        <x-gitradar-logo variant="full" @click="sidebarOpen = false" class="w-full" />
    </div>

    {{-- User info --}}
    @auth
    <div class="px-4 py-4 border-b border-[var(--border-color)]">
        <a href="{{ route('profile.index') }}" class="block" @click="sidebarOpen = false">
            <div class="flex items-center gap-3 px-3 py-3 rounded-xl bg-[var(--bg-muted)] hover:bg-[var(--bg-hover)] transition-all duration-200 cursor-pointer group">
                @if(auth()->user()->githubAccount?->avatar_url)
                <img src="{{ auth()->user()->githubAccount->avatar_url }}"
                     alt="{{ auth()->user()->name }}"
                     class="w-10 h-10 rounded-full border border-[var(--border-color)] shrink-0 group-hover:border-[var(--border-strong)] transition-colors">
                @else
                <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 transition-colors" style="background: var(--primary-soft);">
                    <span class="font-bold text-sm" style="color: var(--primary);">{{ substr(auth()->user()->name, 0, 1) }}</span>
                </div>
                @endif
                <div class="min-w-0">
                    <p class="text-sm font-semibold truncate text-[var(--text-primary)]">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-[var(--text-muted)] truncate">
                        {{ auth()->user()->githubAccount?->username ?? '' }}
                    </p>
                </div>
                <svg class="w-4 h-4 ml-auto text-[var(--text-muted)] group-hover:text-[var(--text-secondary)] transition-colors shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </div>
        </a>
    </div>
    @endauth

    {{-- Navigation --}}
    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto" aria-label="Main">
        @foreach($navItems as $item)
        @php
            $isActive = in_array($currentRoute, $item['matches']);
        @endphp
        <a href="{{ route($item['route']) }}"
           @click="sidebarOpen = false"
           @if($isActive) aria-current="page" @endif
           class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-200
                  {{ $isActive
                     ? 'text-[var(--primary)]'
                     : 'text-[var(--text-secondary)] hover:text-[var(--text-primary)] hover:bg-[var(--bg-hover)]' }}"
           style="{{ $isActive ? 'background: var(--primary-soft); border: 1px solid var(--primary-soft-border);' : '' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                {!! $item['icon'] !!}
            </svg>
            {{ $item['label'] }}
            @if($isActive)
            <div class="ml-auto w-1.5 h-1.5 rounded-full" style="background: var(--primary);"></div>
            @endif
        </a>
        @endforeach
    </nav>

    {{-- Footer: logout --}}
    <div class="px-3 py-4 border-t border-[var(--border-color)]">
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit"
                    class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-[var(--text-secondary)] hover:text-[var(--danger)] hover:bg-[var(--danger-soft)] transition-all duration-200">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                Sign Out
            </button>
        </form>
    </div>

</aside>
