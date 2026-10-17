<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0B1B3D">
    <title>@yield('titulo', 'Conductor PWA - Taxi Kaluz')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-950 text-white min-h-screen flex flex-col select-none antialiased">
    <!-- Barra de Estado Nativa PWA -->
    <header class="bg-[#0B1B3D] px-4 py-3 flex items-center justify-between border-b border-slate-800 shadow-md">
        <div class="flex items-center gap-2">
            <span class="w-3 h-3 rounded-full bg-emerald-400 animate-pulse"></span>
            <span class="font-bold tracking-wide text-sm uppercase">Taxi Kaluz • Socio PWA</span>
        </div>
        <span class="text-xs bg-emerald-500/20 text-emerald-300 px-2.5 py-1 rounded-full font-semibold">
            En Línea
        </span>
    </header>

    <!-- Contenido Principal Mobile-First -->
    <main class="flex-1 overflow-y-auto p-4 max-w-md w-full mx-auto">
        @yield('content')
    </main>
</body>
</html>
