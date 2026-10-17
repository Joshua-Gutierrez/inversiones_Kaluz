<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#08183D">
    <title>@yield('titulo', 'Conductor PWA - Taxi Kaluz')</title>

    <!-- Google Fonts: Montserrat & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-950 font-body text-white min-h-screen flex flex-col select-none antialiased">
    <!-- Barra de Estado Nativa PWA en Azul Marino #08183D -->
    <header class="bg-[#08183D] px-4 py-3 flex items-center justify-between border-b border-white/10 shadow-lg sticky top-0 z-50">
        <div class="flex items-center gap-2">
            <span class="w-3 h-3 rounded-full bg-emerald-400 animate-pulse"></span>
            <span class="font-headline font-bold tracking-wide text-sm text-white flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-[#E31B23]"></span>
                Taxi Kaluz • Socio PWA
            </span>
        </div>
        <span class="text-xs font-label bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 px-2.5 py-0.5 rounded-full font-semibold">
            En Línea
        </span>
    </header>

    <!-- Contenido Principal Mobile-First -->
    <main class="flex-1 overflow-y-auto p-4 max-w-md w-full mx-auto">
        @yield('content')
    </main>
</body>
</html>
