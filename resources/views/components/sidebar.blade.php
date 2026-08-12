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

{{--
    Navigation sidebar — one component, two modes via CSS breakpoint:
    • < lg (1024px): off-canvas drawer, toggled by sidebarOpen
    • >= lg: permanently visible
--}}
<aside id="app-sidebar"
       aria-label="Primary navigation"
       class="fixed top-0 left-0 z-30 flex h-full w-72 flex-col border-r border-[var(--border-color)] bg-[var(--bg-secondary)]/95 backdrop-blur-xl transition-transform duration-300 ease-out lg:translate-x-0"
       x-bind:class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">

    <div class="flex items-center justify-between border-b border-[var(--border-color)] px-4 py-4 lg:px-6 lg:py-6">
        <x-gitradar-logo
            variant="full"
            x-on:click="sidebarOpen = false"
            class="min-w-0"
        />
        <button type="button"
                @click="sidebarOpen = false"
                aria-label="Close navigation menu"
                class="touch-target shrink-0 rounded-lg p-2 text-[var(--text-secondary)] transition hover:bg-[var(--bg-hover)] hover:text-[var(--text-primary)] lg:hidden">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    @auth
    <div class="border-b border-[var(--border-color)] px-4 py-4">
        <a href="{{ route('profile.index') }}" class="block" @click="sidebarOpen = false">
            <div class="group flex cursor-pointer items-center gap-3 rounded-xl bg-[var(--bg-muted)] px-3 py-3 transition-all duration-200 hover:bg-[var(--bg-hover)]">
                @if(auth()->user()->githubAccount?->avatar_url)
                <img src="{{ auth()->user()->githubAccount->avatar_url }}"
                     alt="{{ auth()->user()->name }}"
                     class="h-10 w-10 shrink-0 rounded-full border border-[var(--border-color)] object-cover transition-colors group-hover:border-[var(--border-strong)]">
                @else
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full transition-colors" style="background: var(--primary-soft);">
                    <span class="text-sm font-bold" style="color: var(--primary);">{{ substr(auth()->user()->name, 0, 1) }}</span>
                </div>
                @endif
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-[var(--text-primary)]">{{ auth()->user()->name }}</p>
                    <p class="truncate text-xs text-[var(--text-muted)]">
                        {{ auth()->user()->githubAccount?->username ?? '' }}
                    </p>
                </div>
                <svg class="ml-auto h-4 w-4 shrink-0 text-[var(--text-muted)] transition-colors group-hover:text-[var(--text-secondary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </div>
        </a>
    </div>
    @endauth

    <nav class="flex-1 space-y-1 overflow-y-auto overflow-x-hidden px-2 py-4 lg:px-3" aria-label="Main">
        @foreach($navItems as $item)
        @php
            $isActive = in_array($currentRoute, $item['matches']);
        @endphp
        <a href="{{ route($item['route']) }}"
           @click="sidebarOpen = false"
           @if($isActive) aria-current="page" @endif
           class="sidebar-nav-link flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition-all duration-200
                  {{ $isActive
                     ? 'bg-[var(--bg-muted)] font-semibold text-[var(--text-primary)]'
                     : 'font-medium text-[var(--text-secondary)] hover:bg-[var(--bg-hover)] hover:text-[var(--text-primary)]' }}">
            <svg class="h-[1.125rem] w-[1.125rem] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                {!! $item['icon'] !!}
            </svg>
            <span class="truncate">{{ $item['label'] }}</span>
        </a>
        @endforeach
    </nav>

    <div class="border-t border-[var(--border-color)] px-2 py-4 lg:px-3">
        <form action="{{ route('logout') }}"
              method="POST"
              x-data="{
                  signingOut: false,
                  exiting: false,
                  pressed: false,
                  error: null,
                  onPress() {
                      if (!this.signingOut) {
                          this.pressed = true;
                      }
                  },
                  onRelease() {
                      this.pressed = false;
                  },
                  async submitLogout(event) {
                      if (this.signingOut) {
                          event.preventDefault();
                          return;
                      }
                      event.preventDefault();
                      this.signingOut = true;
                      this.pressed = false;
                      this.error = null;
                      const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                      try {
                          const response = await fetch(event.target.action, {
                              method: 'POST',
                              headers: {
                                  'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
                                  'Accept': 'text/html',
                              },
                              body: new FormData(event.target),
                              credentials: 'same-origin',
                          });
                          if (!response.ok) {
                              throw new Error('logout failed');
                          }
                          this.exiting = true;
                          window.setTimeout(() => {
                              window.location.href = '/';
                          }, reducedMotion ? 0 : 100);
                      } catch {
                          this.signingOut = false;
                          this.exiting = false;
                          this.error = 'Could not sign out. Please try again.';
                      }
                  },
              }"
              @submit="submitLogout($event)">
            @csrf
            <button type="submit"
                    x-bind:disabled="signingOut"
                    x-bind:aria-busy="signingOut.toString()"
                    aria-label="Sign out of GitRadar"
                    x-bind:class="{ 'is-exiting': exiting, 'is-pressed': pressed }"
                    @pointerdown="onPress()"
                    @pointerup="onRelease()"
                    @pointerleave="onRelease()"
                    @pointercancel="onRelease()"
                    class="sidebar-nav-link sidebar-sign-out-btn flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-[var(--text-secondary)] transition-all duration-200 hover:bg-[var(--danger-soft)] hover:text-[var(--danger)] disabled:cursor-wait disabled:opacity-80">
                <span class="relative flex h-5 w-5 shrink-0 items-center justify-center">
                    <svg x-show="!signingOut"
                         class="sidebar-sign-out-icon h-5 w-5"
                         fill="none"
                         stroke="currentColor"
                         viewBox="0 0 24 24"
                         aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    <svg x-show="signingOut"
                         x-cloak
                         class="sidebar-sign-out-spinner h-5 w-5 text-[var(--danger)]"
                         fill="none"
                         viewBox="0 0 24 24"
                         aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </span>
                <span class="sidebar-sign-out-label truncate"
                      x-text="signingOut ? 'Signing out...' : 'Sign Out'">Sign Out</span>
            </button>
            <p x-show="error"
               x-cloak
               x-text="error"
               role="alert"
               aria-live="polite"
               class="mt-2 px-3 text-xs font-medium text-[var(--danger)]"></p>
        </form>
    </div>

</aside>
