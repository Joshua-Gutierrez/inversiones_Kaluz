<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('titulo', 'Torre de Control - Taxi Kaluz')</title>

    <!-- Google Fonts: Montserrat (Headline) & Inter (Body/Label) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#EAF0F8] font-body text-slate-800 flex h-screen overflow-hidden antialiased">
    <!-- Sidebar Azul Marino Kaluz #08183D -->
    <aside class="w-64 bg-[#08183D] text-white flex flex-col shadow-2xl border-r border-white/5">
        <div class="p-5 border-b border-white/10 flex items-center justify-between">
            <div>
                <h2 class="text-xl font-headline font-black text-white tracking-tight flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#E31B23]"></span>
                    TAXI KALUZ
                </h2>
                <p class="text-xs font-body text-slate-400">Torre de Control</p>
            </div>
            <span class="text-[10px] font-label bg-[#E31B23]/20 text-[#E31B23] border border-[#E31B23]/40 px-2 py-0.5 rounded-full font-bold">
                EN VIVO
            </span>
        </div>

        <nav class="flex-1 p-4 space-y-1.5 text-sm overflow-y-auto font-label">
            <a href="/dashboard" class="flex items-center gap-3 py-2.5 px-4 rounded-xl transition {{ request()->is('dashboard') ? 'bg-[#E31B23] text-white font-bold shadow-sm' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                <span>📊</span> Panel General
            </a>
            <a href="{{ route('bot.tablero') }}" class="flex items-center gap-3 py-2.5 px-4 rounded-xl transition {{ request()->is('bot/tablero*') ? 'bg-[#E31B23] text-white font-bold shadow-sm' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                <span>📈</span> Tablero KPIs (Dev 4)
            </a>
            <a href="{{ route('bot.simulador') }}" class="flex items-center gap-3 py-2.5 px-4 rounded-xl transition {{ request()->is('bot/simulador*') ? 'bg-[#E31B23] text-white font-bold shadow-sm' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                <span>💬</span> Simulador WhatsApp (Dev 4)
            </a>
            <a href="{{ route('finanzas.padron') }}" class="flex items-center gap-3 py-2.5 px-4 rounded-xl transition {{ request()->is('finanzas*') ? 'bg-[#E31B23] text-white font-bold shadow-sm' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                <span>🔒</span> Padrón y Candado S/ 1 (Dev 2)
            </a>
            <a href="{{ route('turismo.index') }}" class="flex items-center gap-3 py-2.5 px-4 rounded-xl transition {{ request()->is('turismo*') ? 'bg-[#E31B23] text-white font-bold shadow-sm' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                <span>🚐</span> Turismo y Combis (Dev 3)
            </a>
            <a href="{{ route('despacho.pwa') }}" class="flex items-center gap-3 py-2.5 px-4 rounded-xl transition {{ request()->is('despacho*') ? 'bg-[#E31B23] text-white font-bold shadow-sm' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                <span>📱</span> PWA Conductor (Dev 1)
            </a>

            <!-- Acceso a la Carpeta General / Guía de Estilos -->
            <div class="pt-3 mt-3 border-t border-white/10">
                <a href="{{ route('general.index') }}" class="flex items-center gap-3 py-2.5 px-4 rounded-xl transition {{ request()->is('general*') ? 'bg-white text-[#08183D] font-bold shadow-sm' : 'text-amber-300 hover:bg-white/10' }}">
                    <span>🎨</span> <strong>Guía de Estilos General</strong>
                </a>
            </div>
        </nav>

        <div class="p-4 border-t border-white/10 text-xs font-body text-slate-300 flex items-center justify-between bg-[#081328]">
            <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-full bg-[#08183D] border border-white/20 flex items-center justify-center text-[10px] font-bold text-white">
                    {{ strtoupper(substr(session('nombre', 'Admin'), 0, 1)) }}
                </span>
                <span class="truncate max-w-[110px]">{{ session('nombre', 'Administrador') }}</span>
            </div>
            <a href="/" class="text-[#E31B23] hover:underline font-bold">Salir</a>
        </div>
    </aside>

    <!-- Contenido Principal con Fondo Canvas #EAF0F8 -->
    <main class="flex-1 flex flex-col overflow-hidden bg-[#EAF0F8]">
        <header class="bg-white/90 backdrop-blur-sm border-b border-slate-200/80 px-6 py-4 flex items-center justify-between shadow-xs">
            <h1 class="text-xl font-headline font-bold text-[#08183D]">@yield('encabezado', 'Torre de Control Operativa')</h1>
            <div class="flex items-center gap-3">
                <span class="text-xs font-label text-[#64748B]">Sesión activa: <strong class="text-[#08183D]">{{ session('usuario', 'admin') }}</strong></span>
                <a href="{{ route('general.index') }}" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-[#08183D] rounded-lg text-xs font-label font-semibold border border-slate-200 transition">
                    🎨 Estilos Generales
                </a>
            </div>
        </header>
        <div class="p-6 overflow-y-auto flex-1">
            @yield('content')
        </div>
    </main>
</body>
</html>
