<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('titulo', 'Torre de Control - Taxi Kaluz')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 flex h-screen overflow-hidden">
    <!-- Sidebar Azul Marino #0B1B3D -->
    <aside class="w-64 bg-[#0B1B3D] text-white flex flex-col shadow-xl">
        <div class="p-5 border-b border-white/10 flex items-center justify-between">
            <div>
                <h2 class="text-xl font-extrabold text-amber-400 tracking-tight">TAXI KALUZ</h2>
                <p class="text-xs text-blue-200">Torre de Control</p>
            </div>
            <span class="text-xs bg-emerald-500/20 text-emerald-300 px-2 py-0.5 rounded font-semibold">VIVO</span>
        </div>

        <nav class="flex-1 p-4 space-y-1.5 text-sm">
            <a href="/dashboard" class="flex items-center gap-3 py-2.5 px-4 rounded-lg hover:bg-white/10 transition {{ request()->is('dashboard') ? 'bg-red-600 font-semibold' : '' }}">
                <span>📊</span> Panel General
            </a>
            <a href="{{ route('bot.tablero') }}" class="flex items-center gap-3 py-2.5 px-4 rounded-lg hover:bg-white/10 transition {{ request()->is('bot/tablero*') ? 'bg-red-600 font-semibold' : '' }}">
                <span>📈</span> Tablero KPIs (Dev 4)
            </a>
            <a href="{{ route('bot.simulador') }}" class="flex items-center gap-3 py-2.5 px-4 rounded-lg hover:bg-white/10 transition {{ request()->is('bot/simulador*') ? 'bg-red-600 font-semibold' : '' }}">
                <span>💬</span> Simulador WhatsApp (Dev 4)
            </a>
            <a href="{{ route('finanzas.padron') }}" class="flex items-center gap-3 py-2.5 px-4 rounded-lg hover:bg-white/10 transition {{ request()->is('finanzas*') ? 'bg-red-600 font-semibold' : '' }}">
                <span>🔒</span> Padrón y Candado S/ 1 (Dev 2)
            </a>
            <a href="{{ route('turismo.index') }}" class="flex items-center gap-3 py-2.5 px-4 rounded-lg hover:bg-white/10 transition {{ request()->is('turismo*') ? 'bg-red-600 font-semibold' : '' }}">
                <span>🚐</span> Turismo y Combis (Dev 3)
            </a>
            <a href="{{ route('despacho.pwa') }}" class="flex items-center gap-3 py-2.5 px-4 rounded-lg hover:bg-white/10 transition {{ request()->is('despacho*') ? 'bg-red-600 font-semibold' : '' }}">
                <span>📱</span> PWA Conductor (Dev 1)
            </a>
        </nav>

        <div class="p-4 border-t border-white/10 text-xs text-blue-200 flex items-center justify-between">
            <span>👤 {{ session('nombre', 'Administrador') }}</span>
            <a href="/" class="text-amber-300 hover:underline">Salir</a>
        </div>
    </aside>

    <!-- Contenido Principal -->
    <main class="flex-1 flex flex-col overflow-hidden">
        <header class="bg-white shadow-sm px-6 py-4 flex items-center justify-between">
            <h1 class="text-xl font-bold text-slate-800">@yield('encabezado', 'Torre de Control Operativa')</h1>
            <span class="text-xs text-slate-500">Sesión activa: <strong class="text-slate-700">{{ session('usuario', 'admin') }}</strong></span>
        </header>
        <div class="p-6 overflow-y-auto flex-1">
            @yield('content')
        </div>
    </main>
</body>
</html>
