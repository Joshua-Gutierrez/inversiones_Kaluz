<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Taxi Kaluz</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 flex h-screen overflow-hidden">
    <aside class="w-64 bg-[#0B1B3D] text-white flex flex-col">
        <div class="p-4 bg-gray-950 text-center">
            <h2 class="text-xl font-bold text-red-500">Taxi Kaluz</h2>
            <p class="text-xs text-gray-400">Torre de Control</p>
        </div>
        <nav class="flex-1 p-4 space-y-2 text-sm">
            <a href="/dashboard" class="block py-2 px-4 rounded {{ request()->is('dashboard') ? 'bg-red-600 font-semibold' : 'hover:bg-white/10' }}">📊 Panel General</a>
            <a href="{{ route('despacho.pwa') }}" class="block py-2 px-4 rounded hover:bg-white/10">📱 Zona Dev 1: Despacho PWA</a>
            <a href="{{ route('finanzas.padron') }}" class="block py-2 px-4 rounded hover:bg-white/10">🔒 Zona Dev 2: Finanzas / Padrón</a>
            <a href="{{ route('turismo.index') }}" class="block py-2 px-4 rounded hover:bg-white/10">🚐 Zona Dev 3: Turismo y GPS</a>
            <a href="{{ route('bot.simulador') }}" class="block py-2 px-4 rounded hover:bg-white/10">💬 Zona Dev 4: Bot WhatsApp</a>
        </nav>
    </aside>
    <main class="flex-1 flex flex-col">
        <header class="bg-white shadow p-4 flex items-center justify-between">
            <h2 class="text-xl font-semibold">Panel General</h2>
            <span class="text-sm text-gray-600">Bienvenido, <strong>{{ session('nombre', 'Admin') }}</strong></span>
        </header>
        <div class="p-6 overflow-y-auto flex-1">
            @yield('content')
        </div>
    </main>
</body>
</html>
