<x-layouts.app>

<div class="max-w-7xl mx-auto space-y-6">

    {{-- Header --}}
    <div>
        <h1 class="text-3xl font-black tracking-tight text-[var(--text-primary)]">Repositories</h1>
        <p class="text-[var(--text-secondary)] mt-1 text-sm">Browse and analyze your GitHub repositories</p>
    </div>

    {{-- Soft search + filters --}}
    <div
        x-data="{
            search: {{ Js::from($search ?? '') }},
            language: {{ Js::from($lang ?? '') }},
            sort: {{ Js::from($sort ?? 'stars') }},
            order: {{ Js::from($order ?? 'desc') }},
            visibility: {{ Js::from($visibility ?? '') }},
            origin: {{ Js::from($origin ?? '') }},
            state: {{ Js::from($state ?? '') }},
            analysis: {{ Js::from($analysis ?? '') }},
            highlight: {{ Js::from($highlight ?? '') }},
            loading: false,
            timer: null,
            activeMenu: null,
            sortLabels: {
                stars: 'Most Stars',
                forks: 'Most Forks',
                updated_at: 'Recently Updated',
                pushed_at: 'Recently Pushed',
                name: 'Name A-Z',
                open_issues: 'Most Issues',
            },
            openMenu(menu) {
                this.activeMenu = menu;
            },
            closeMenu(menu) {
                if (this.activeMenu === menu) this.activeMenu = null;
            },
            toggleMenu(menu) {
                this.activeMenu = this.activeMenu === menu ? null : menu;
            },
            selectFilter(filter, value) {
                this[filter] = value;
                this.activeMenu = null;
                this.runSearch();
            },
            buildQuery() {
                const params = new URLSearchParams();
                if (this.search.trim()) params.set('search', this.search.trim());
                if (this.language) params.set('language', this.language);
                if (this.sort) params.set('sort', this.sort);
                if (this.order) params.set('order', this.order);
                if (this.visibility) params.set('visibility', this.visibility);
                if (this.origin) params.set('origin', this.origin);
                if (this.state) params.set('state', this.state);
                if (this.analysis) params.set('analysis', this.analysis);
                if (this.highlight) params.set('highlight', this.highlight);
                return params.toString();
            },
            async runSearch() {
                const qs = this.buildQuery();
                const url = qs ? (window.location.pathname + '?' + qs) : window.location.pathname;
                history.replaceState(null, '', url);
                this.loading = true;
                try {
                    const res = await fetch(url, {
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin',
                    });
                    if (!res.ok) throw new Error('Search request failed');
                    const html = await res.text();
                    if (this.$refs.grid) this.$refs.grid.innerHTML = html;
                } catch (e) {
                    window.location.href = url;
                } finally {
                    this.loading = false;
                }
            },
            debouncedSearch() {
                clearTimeout(this.timer);
                this.timer = setTimeout(() => this.runSearch(), 300);
            },
            clearSearch() {
                this.search = '';
                this.debouncedSearch();
            }
        }"
        class="p-5 rounded-2xl bg-[var(--bg-card)] border border-[var(--border-color)] shadow-[var(--shadow-card)]">

        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">

            {{-- Search (debounced, no submit required) --}}
            <div class="relative w-full flex-1 sm:min-w-[220px]">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-[var(--text-muted)]"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text"
                       x-model="search"
                       @input="debouncedSearch()"
                       placeholder="Search repositories…"
                       autocomplete="off"
                       class="w-full pl-10 pr-10 py-2.5 rounded-xl bg-[var(--bg-muted)] border border-[var(--border-color)]
                              text-sm text-[var(--text-primary)] placeholder:text-[var(--text-muted)] 
                              focus:outline-none focus:ring-2 focus:ring-[var(--primary)] focus:border-transparent transition">
                <button type="button"
                        x-show="search.length > 0"
                        x-transition.opacity
                        @click="clearSearch()"
                        aria-label="Clear search"
                        class="absolute right-3 top-1/2 -translate-y-1/2 p-1 rounded-full text-[var(--text-muted)]
                               hover:text-[var(--text-primary)] hover:bg-[var(--bg-hover)] transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Hoverable filter menus keep click and keyboard support for touch devices. --}}
            <div class="relative w-full sm:w-auto"
                 @mouseenter="openMenu('language')"
                 @mouseleave="closeMenu('language')"
                 @focusin="openMenu('language')"
                 @focusout="setTimeout(() => { if (!$el.contains(document.activeElement)) closeMenu('language') })"
                 @keydown.escape.prevent="closeMenu('language')">
                <button type="button"
                        @click="toggleMenu('language')"
                        aria-haspopup="menu"
                        aria-controls="language-filter-menu"
                        :aria-expanded="(activeMenu === 'language').toString()"
                        class="flex w-full items-center justify-between gap-3 rounded-xl border border-[var(--border-color)] bg-[var(--bg-muted)] px-4 py-2.5 text-sm text-[var(--text-primary)] transition hover:bg-[var(--bg-hover)] sm:w-auto sm:min-w-40">
                    <span x-text="language || 'All Languages'"></span>
                    <svg class="h-4 w-4 shrink-0 text-[var(--text-muted)] transition-transform" :class="activeMenu === 'language' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/>
                    </svg>
                </button>
                <div id="language-filter-menu"
                     x-cloak
                     x-show="activeMenu === 'language'"
                     x-transition.origin.top.left
                     role="menu"
                     aria-label="Filter by language"
                     class="absolute z-20 mt-1 max-h-64 w-full min-w-48 overflow-y-auto rounded-xl border border-[var(--border-color)] bg-[var(--bg-card)] p-1 shadow-[var(--shadow-card)] sm:w-max">
                    <button type="button" role="menuitemradio" @click="selectFilter('language', '')" :aria-checked="(language === '').toString()" class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-[var(--text-primary)] hover:bg-[var(--bg-hover)] focus:bg-[var(--bg-hover)]">
                        All Languages
                    </button>
                    @foreach($languages as $language)
                    <button type="button" role="menuitemradio" @click="selectFilter('language', {{ Js::from($language) }})" :aria-checked="(language === {{ Js::from($language) }}).toString()" class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-[var(--text-primary)] hover:bg-[var(--bg-hover)] focus:bg-[var(--bg-hover)]">
                        {{ $language }}
                    </button>
                    @endforeach
                </div>
            </div>

            <div class="relative w-full sm:w-auto"
                 @mouseenter="openMenu('sort')"
                 @mouseleave="closeMenu('sort')"
                 @focusin="openMenu('sort')"
                 @focusout="setTimeout(() => { if (!$el.contains(document.activeElement)) closeMenu('sort') })"
                 @keydown.escape.prevent="closeMenu('sort')">
                <button type="button"
                        @click="toggleMenu('sort')"
                        aria-haspopup="menu"
                        aria-controls="sort-filter-menu"
                        :aria-expanded="(activeMenu === 'sort').toString()"
                        class="flex w-full items-center justify-between gap-3 rounded-xl border border-[var(--border-color)] bg-[var(--bg-muted)] px-4 py-2.5 text-sm text-[var(--text-primary)] transition hover:bg-[var(--bg-hover)] sm:w-auto sm:min-w-44">
                    <span x-text="sortLabels[sort]"></span>
                    <svg class="h-4 w-4 shrink-0 text-[var(--text-muted)] transition-transform" :class="activeMenu === 'sort' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/>
                    </svg>
                </button>
                <div id="sort-filter-menu"
                     x-cloak
                     x-show="activeMenu === 'sort'"
                     x-transition.origin.top.left
                     role="menu"
                     aria-label="Sort repositories"
                     class="absolute z-20 mt-1 w-full min-w-48 rounded-xl border border-[var(--border-color)] bg-[var(--bg-card)] p-1 shadow-[var(--shadow-card)] sm:w-max">
                    <template x-for="[value, label] in Object.entries(sortLabels)" :key="value">
                        <button type="button" role="menuitemradio" @click="selectFilter('sort', value)" :aria-checked="(sort === value).toString()" class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-[var(--text-primary)] hover:bg-[var(--bg-hover)] focus:bg-[var(--bg-hover)]" x-text="label"></button>
                    </template>
                </div>
            </div>

            <div class="relative w-full sm:w-auto"
                 @mouseenter="openMenu('order')"
                 @mouseleave="closeMenu('order')"
                 @focusin="openMenu('order')"
                 @focusout="setTimeout(() => { if (!$el.contains(document.activeElement)) closeMenu('order') })"
                 @keydown.escape.prevent="closeMenu('order')">
                <button type="button"
                        @click="toggleMenu('order')"
                        aria-haspopup="menu"
                        aria-controls="order-filter-menu"
                        :aria-expanded="(activeMenu === 'order').toString()"
                        class="flex w-full items-center justify-between gap-3 rounded-xl border border-[var(--border-color)] bg-[var(--bg-muted)] px-4 py-2.5 text-sm text-[var(--text-primary)] transition hover:bg-[var(--bg-hover)] sm:w-auto sm:min-w-36">
                    <span x-text="order === 'asc' ? 'Ascending' : 'Descending'"></span>
                    <svg class="h-4 w-4 shrink-0 text-[var(--text-muted)] transition-transform" :class="activeMenu === 'order' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/>
                    </svg>
                </button>
                <div id="order-filter-menu"
                     x-cloak
                     x-show="activeMenu === 'order'"
                     x-transition.origin.top.left
                     role="menu"
                     aria-label="Sort order"
                     class="absolute z-20 mt-1 w-full min-w-40 rounded-xl border border-[var(--border-color)] bg-[var(--bg-card)] p-1 shadow-[var(--shadow-card)] sm:w-max">
                    <button type="button" role="menuitemradio" @click="selectFilter('order', 'desc')" :aria-checked="(order === 'desc').toString()" class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-[var(--text-primary)] hover:bg-[var(--bg-hover)] focus:bg-[var(--bg-hover)]">Descending</button>
                    <button type="button" role="menuitemradio" @click="selectFilter('order', 'asc')" :aria-checked="(order === 'asc').toString()" class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-[var(--text-primary)] hover:bg-[var(--bg-hover)] focus:bg-[var(--bg-hover)]">Ascending</button>
                </div>
            </div>

            {{-- Subtle loading indicator --}}
            <div x-show="loading" x-transition.opacity class="flex items-center gap-2 text-xs text-[var(--text-muted)]" role="status" aria-live="polite">
                <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--primary);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span>Searching…</span>
            </div>
        </div>
    </div>

    {{-- Results (replaced via fetch on soft search) --}}
    <div x-ref="grid">
        @include('repositories.partials.grid', [
            'repositories' => $repositories,
            'languages'    => $languages,
            'search'       => $search,
            'lang'         => $lang,
            'sort'         => $sort,
            'order'        => $order,
            'visibility'   => $visibility,
            'origin'       => $origin,
            'state'        => $state,
            'analysis'     => $analysis,
            'highlight'    => $highlight,
        ])
    </div>

</div>

</x-layouts.app>
