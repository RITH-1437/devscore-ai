<x-layouts.app>

    <div class="max-w-7xl mx-auto px-6 py-10">

        <!-- Header -->
        <div class="flex justify-between items-center mb-10">

            <div>
                <h1 class="text-6xl font-bold tracking-tight">
                    DevScore AI
                </h1>

                <p class="text-slate-400 mt-3">
                    AI-Powered GitHub Portfolio Analysis
                </p>
            </div>

            <img src="{{ auth()->user()->githubAccount->avatar_url }}" alt="Avatar"
                class="w-16 h-16 rounded-full border border-white/20 shadow-lg" />

        </div>

        <!-- Stats Cards -->
        <div class="grid lg:grid-cols-4 gap-6 mb-10">

            <x-stat-card title="Repositories" :value="$totalRepos" />

            <x-stat-card title="Stars" :value="$totalStars" />

            <x-stat-card title="Top Language" :value="$topLanguages->keys()->first() ?? 'N/A'" />

            <x-stat-card title="Portfolio Score" value="72" />

        </div>

        <!-- Repository Table -->
        <div class="backdrop-blur-xl
               bg-white/5
               border border-white/10
               rounded-3xl
               overflow-hidden
               shadow-xl">

            <table class="w-full">

                <thead class="bg-white/5">

                    <tr>
                        <th class="text-left p-5 text-slate-300">
                            Repository
                        </th>

                        <th class="text-left p-5 text-slate-300">
                            Language
                        </th>

                        <th class="text-left p-5 text-slate-300">
                            Stars
                        </th>
                    </tr>

                </thead>

                <tbody>

                    @foreach($repositories as $repo)

                        <tr class="border-t border-white/10
                               hover:bg-white/5
                               transition duration-200">

                            <td class="p-5">

                                <a href="#" class="font-semibold hover:text-blue-400 transition">

                                    <a href="{{ route('repositories.show', $repo) }}"
                                        class="font-semibold hover:text-purple-400 transition">

                                        {{ $repo->name }}

                                    </a>

                                </a>

                            </td>

                            <td class="p-5 text-slate-300">
                                {{ $repo->language ?? 'N/A' }}
                            </td>

                            <td class="p-5">
                                ⭐ {{ $repo->stars }}
                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    </div>

</x-layouts.app>