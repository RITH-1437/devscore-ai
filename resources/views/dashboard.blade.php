<x-layouts.app>

    <div class="max-w-6xl mx-auto py-10">

        <h1 class="text-4xl font-bold mb-8">
            DevScore AI 🚀
        </h1>

        <h2 class="text-xl mb-6">
            GitHub Repositories
        </h2>

        <table class="w-full border">

            <thead>
                <tr>
                    <th>Name</th>
                    <th>Language</th>
                    <th>Stars</th>
                </tr>
            </thead>

            <tbody>

            @foreach($repositories as $repo)

                <tr>
                    <td>{{ $repo->name }}</td>
                    <td>{{ $repo->language }}</td>
                    <td>{{ $repo->stars }}</td>
                </tr>

            @endforeach

            </tbody>

        </table>

    </div>

</x-layouts.app>