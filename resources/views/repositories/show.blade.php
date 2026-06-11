<x-layouts.app>

<div class="max-w-5xl mx-auto px-6 py-10">

    <div
        class="backdrop-blur-xl
               bg-white/5
               border border-white/10
               rounded-3xl
               p-8">

        <h1 class="text-5xl font-bold mb-4">
            {{ $repository->name }}
        </h1>

        <p class="text-slate-400 mb-8">
            {{ $repository->description ?? 'No description available.' }}
        </p>

        <div class="grid md:grid-cols-3 gap-6">

            <x-stat-card
                title="Language"
                :value="$repository->language ?? 'N/A'"
            />

            <x-stat-card
                title="Stars"
                :value="$repository->stars"
            />

            <x-stat-card
                title="Forks"
                :value="$repository->forks"
            />

        </div>

        <div class="mt-8">

            <a
                href="{{ $repository->html_url }}"
                target="_blank"
                class="inline-flex items-center
                       px-6 py-3
                       rounded-xl
                       bg-blue-600
                       hover:bg-blue-500
                       transition">

                View on GitHub →
            </a>

        </div>

    </div>

</div>

</x-layouts.app>