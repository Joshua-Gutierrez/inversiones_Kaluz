<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Taxi Kaluz</title>
    @vite('resources/css/app.css')
</head>
<body class="bg-gray-100 flex h-screen overflow-hidden">
    <aside class="w-64 bg-gray-900 text-white flex flex-col">
        <div class="p-4 bg-gray-950 text-center"><h2 class="text-xl font-bold text-red-500">Taxi Kaluz</h2></div>
        <nav class="flex-1 p-4 space-y-2">
            <a href="/dashboard" class="block py-2 px-4 rounded bg-red-600">📊 Panel General</a>
        </nav>
    </aside>
    <main class="flex-1 flex flex-col">
        <header class="bg-white shadow p-4"><h2 class="text-xl font-semibold">Panel General</h2></header>
        <div class="p-6 overflow-y-auto flex-1">
            @yield('content')
        </div>
    </main>
</body>
</html>
