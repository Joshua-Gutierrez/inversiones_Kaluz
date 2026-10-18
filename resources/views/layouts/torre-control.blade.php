<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'Torre de Control - KALUZ')</title>

    <!-- Google Fonts: Montserrat (Headline) & Inter (Body/Label) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Montserrat:wght@500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- RemixIcon (Librería de iconos vectoriales reales, cero emojis) -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.6.0/fonts/remixicon.css" rel="stylesheet">

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
    </style>
    @yield('head')
</head>
<body class="bg-[#DCE6F2] font-body text-slate-800 flex h-screen overflow-hidden antialiased">
    <!-- Sidebar Azul Marino Profundo KALUZ #08142C -->
    <aside class="w-60 bg-[#08142C] text-white flex flex-col justify-between shrink-0 shadow-2xl border-r border-white/5 select-none">
        <div>
            <!-- Logo KALUZ -->
            <div class="px-6 pt-6 pb-5 flex items-center justify-between">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                    <span class="font-headline font-black text-2xl tracking-wider text-white">KALUZ</span>
                </a>
                <span class="inline-flex items-center gap-1 text-[10px] font-semibold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 px-2 py-0.5 rounded-full">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    LIVE
                </span>
            </div>

            <!-- Navegación Principal con Iconos Reales -->
            <nav class="px-3 space-y-1 text-sm font-medium">
                <a href="{{ route('dashboard') }}"
                   class="flex items-center gap-3 py-2.5 px-3.5 rounded-xl transition {{ request()->is('dashboard') ? 'bg-[#132347] text-white font-semibold shadow-sm border border-white/10' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                    <i class="ri-layout-grid-line text-lg {{ request()->is('dashboard') ? 'text-white' : 'text-slate-400' }}"></i>
                    <span>Dashboard KPI</span>
                </a>

                <a href="{{ url('/dashboard#mapa-en-vivo') }}"
                   class="flex items-center gap-3 py-2.5 px-3.5 rounded-xl transition text-slate-300 hover:bg-white/5 hover:text-white">
                    <i class="ri-map-2-line text-lg text-emerald-400"></i>
                    <span>Mapa en Vivo</span>
                </a>

                <a href="{{ route('finanzas.padron') }}"
                   class="flex items-center gap-3 py-2.5 px-3.5 rounded-xl transition {{ request()->is('finanzas/padron*') ? 'bg-[#132347] text-white font-semibold shadow-sm border border-white/10' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                    <i class="ri-group-line text-lg {{ request()->is('finanzas/padron*') ? 'text-white' : 'text-slate-400' }}"></i>
                    <span>Conductores y Padrón</span>
                </a>

                <a href="{{ route('finanzas.yape') }}"
                   class="flex items-center gap-3 py-2.5 px-3.5 rounded-xl transition {{ request()->is('finanzas/yape*') ? 'bg-[#132347] text-white font-semibold shadow-sm border border-white/10' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                    <i class="ri-shield-check-line text-lg {{ request()->is('finanzas/yape*') ? 'text-emerald-400' : 'text-slate-400' }}"></i>
                    <span>Validación Yape</span>
                </a>

                <a href="{{ route('turismo.index') }}"
                   class="flex items-center gap-3 py-2.5 px-3.5 rounded-xl transition {{ request()->is('turismo*') ? 'bg-[#132347] text-white font-semibold shadow-sm border border-white/10' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                    <i class="ri-bus-2-line text-lg {{ request()->is('turismo*') ? 'text-white' : 'text-slate-400' }}"></i>
                    <span>Turismo y Combis</span>
                </a>

                <div class="pt-4 mt-4 border-t border-white/10 space-y-1">
                    <p class="px-3.5 text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">App Móvil Taxista</p>
                    <a href="{{ route('despacho.seleccion') }}" target="_blank"
                       class="flex items-center justify-between py-2.5 px-3.5 rounded-xl bg-[#E31B23]/15 hover:bg-[#E31B23]/25 text-white border border-[#E31B23]/30 transition">
                        <span class="flex items-center gap-2.5 text-xs font-semibold">
                            <i class="ri-smartphone-line text-base text-[#E31B23]"></i>
                            <span>Abrir PWA Conductor</span>
                        </span>
                        <i class="ri-external-link-line text-xs text-slate-300"></i>
                    </a>
                </div>
            </nav>
        </div>

        <!-- Perfil Admin Inferior -->
        <div class="p-4 border-t border-white/10 bg-[#060F22] flex items-center justify-between">
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-[#1E3A8A] to-[#3B82F6] border border-white/20 flex items-center justify-center font-headline font-bold text-xs text-white shrink-0">
                    {{ strtoupper(substr(session('nombre', 'Admin'), 0, 1)) }}
                </div>
                <div class="truncate">
                    <p class="text-xs font-semibold text-white truncate">{{ session('nombre', 'Admin') }}</p>
                    <p class="text-[10px] text-emerald-400 flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> En línea
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="/" title="Cerrar sesión" class="w-8 h-8 rounded-lg hover:bg-white/10 flex items-center justify-center text-slate-400 hover:text-white transition">
                    <i class="ri-shut-down-line text-base"></i>
                </a>
            </div>
        </div>
    </aside>

    <!-- Contenido Principal -->
    <main class="flex-1 flex flex-col overflow-hidden bg-[#DCE6F2]">
        <!-- Topbar de Búsqueda y Estado -->
        <header class="px-6 pt-4 pb-2 flex items-center justify-between gap-4 shrink-0">
            <form action="{{ route('finanzas.padron') }}" method="GET" class="relative flex-1 max-w-2xl">
                <i class="ri-search-line absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-base"></i>
                <input
                    type="search"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Buscar conductor, placa, DNI o documento..."
                    class="w-full bg-white/90 hover:bg-white focus:bg-white rounded-xl pl-10 pr-4 py-2 text-sm text-slate-700 placeholder-slate-400 border border-white/60 shadow-xs focus:outline-none focus:ring-2 focus:ring-[#08142C]/20 transition"
                >
            </form>

            <div class="flex items-center gap-3">
                <a href="{{ route('finanzas.yape') }}"
                   class="relative w-9 h-9 rounded-xl bg-white/90 hover:bg-white border border-white/60 shadow-xs flex items-center justify-center text-slate-600 hover:text-[#08142C] transition"
                   title="Pagos Yape pendientes de validar">
                    <i class="ri-notification-3-line text-lg"></i>
                    <span class="absolute top-1.5 right-2 w-2 h-2 rounded-full bg-[#E31B23]"></span>
                </a>

                <div class="bg-white/90 border border-white/60 shadow-xs rounded-xl px-3.5 py-1.5 flex items-center gap-2 text-xs font-medium text-slate-700">
                    <span>{{ session('nombre', 'Admin') }} -</span>
                    <span class="text-emerald-600 font-semibold flex items-center gap-1.5">
                        En línea
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    </span>
                </div>
            </div>
        </header>

        <!-- Canvas Principal Scrollable -->
        <div class="px-6 pb-6 pt-2 overflow-y-auto flex-1">
            @yield('content')
        </div>
    </main>

    @yield('scripts')
</body>
</html>
