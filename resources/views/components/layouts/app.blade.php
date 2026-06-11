<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>DevScore AI</title>
</head>

<body class="min-h-screen bg-[#0B1120] text-white">

    <div class="fixed inset-0 overflow-hidden">

        <div class="absolute top-0 left-0 w-96 h-96 bg-blue-600/20 blur-3xl rounded-full"></div>

        <div class="absolute bottom-0 right-0 w-96 h-96 bg-purple-600/20 blur-3xl rounded-full"></div>

    </div>

    <div class="relative z-10 flex">

    <x-sidebar />

    <main class="flex-1">
        {{ $slot }}
    </main>

</div>

</body>
</html>