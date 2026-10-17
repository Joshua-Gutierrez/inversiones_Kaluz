<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('titulo', 'Dashboard - Taxi Kaluz')</title>

    <!-- Google Fonts: Montserrat & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#EAF0F8] font-body text-slate-800 flex h-screen overflow-hidden antialiased">
    <aside class="w-64 bg-[#08183D] text-white flex flex-col shadow-2xl border-r border-white/5">
        <div class="p-5 border-b border-white/10 text-center">
            <h2 class="text-xl font-headline font-black text-white flex items-center justify-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-full bg-[#E31B23]"></span>
                Taxi Kaluz
            </h2>
            <p class="text-xs font-body text-slate-400">Torre de Control</p>
        </div>
        <nav class="flex-1 p-4 space-y-1.5 text-sm font-label overflow-y-auto">
            <a href="/dashboard" class="block py-2.5 px-4 rounded-xl transition {{ request()->is('dashboard') ? 'bg-[#E31B23] text-white font-bold shadow-sm' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">📊 Panel General</a>
            <a href="{{ route('despacho.pwa') }}" class="block py-2.5 px-4 rounded-xl transition hover:bg-white/10 text-slate-300 hover:text-white">📱 Zona Dev 1: Despacho PWA</a>
            <a href="{{ route('finanzas.padron') }}" class="block py-2.5 px-4 rounded-xl transition hover:bg-white/10 text-slate-300 hover:text-white">🔒 Zona Dev 2: Finanzas / Padrón</a>
            <a href="{{ route('turismo.index') }}" class="block py-2.5 px-4 rounded-xl transition hover:bg-white/10 text-slate-300 hover:text-white">🚐 Zona Dev 3: Turismo y GPS</a>
            <a href="{{ route('bot.simulador') }}" class="block py-2.5 px-4 rounded-xl transition hover:bg-white/10 text-slate-300 hover:text-white">💬 Zona Dev 4: Bot WhatsApp</a>
            <a href="{{ route('general.index') }}" class="block py-2.5 px-4 rounded-xl transition text-amber-300 hover:bg-white/10 font-semibold mt-3 pt-3 border-t border-white/10">🎨 Guía de Estilos General</a>
        </nav>
        <div class="p-4 border-t border-white/10 text-xs font-body text-slate-300 flex items-center justify-between bg-[#081328]">
            <span>👤 {{ session('nombre', 'Admin') }}</span>
            <a href="/" class="text-[#E31B23] hover:underline font-bold">Salir</a>
        </div>
    </aside>
    <main class="flex-1 flex flex-col overflow-hidden bg-[#EAF0F8]">
        <header class="bg-white/90 backdrop-blur-sm border-b border-slate-200/80 px-6 py-4 flex items-center justify-between shadow-xs">
            <h2 class="text-xl font-headline font-bold text-[#08183D]">Panel General</h2>
            <div class="flex items-center gap-3">
                <span class="text-xs font-label text-[#64748B]">Bienvenido, <strong class="text-[#08183D]">{{ session('nombre', 'Admin') }}</strong></span>
                <a href="{{ route('general.index') }}" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-[#08183D] rounded-lg text-xs font-label font-semibold border border-slate-200 transition">
                    🎨 Estilos
                </a>
            </div>
        </header>
        <div class="p-6 overflow-y-auto flex-1">
            @yield('content')
        </div>
    </main>
</body>
</html>
