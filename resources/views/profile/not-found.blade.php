<x-layouts.app title="Profile">

<div class="max-w-3xl mx-auto">
    <div class="rounded-2xl border border-[var(--border-color)] bg-[var(--bg-card)] p-6 sm:p-8 text-center shadow-[var(--shadow-card)]">
        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl" style="background: var(--warning-soft); color: var(--warning);">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h1 class="text-xl font-black tracking-tight text-[var(--text-primary)]">No GitHub Profile Connected</h1>
        <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-[var(--text-secondary)]">
            Connect your GitHub account to view portfolio stats, repository activity, and profile details.
        </p>
        <a href="{{ route('github.login') }}"
           class="mt-6 inline-flex items-center justify-center gap-2 rounded-xl px-5 py-2.5 text-sm font-semibold text-white transition"
           style="background: var(--primary);">
            Connect GitHub
        </a>
    </div>
</div>

</x-layouts.app>
