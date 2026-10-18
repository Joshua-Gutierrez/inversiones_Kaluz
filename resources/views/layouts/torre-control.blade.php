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

    <script>
        (function () {
            const origWarn = console.warn;
            console.warn = function (...args) {
                if (args[0] && typeof args[0] === 'string' && args[0].includes('cdn.tailwindcss.com')) return;
                origWarn.apply(console, args);
            };
        })();
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        headline: ['Montserrat', 'sans-serif'],
                        body: ['Inter', 'sans-serif'],
                    },
                },
            },
        };
    </script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
    </style>
    @yield('head')
</head>
<body class="bg-[#DCE6F2] font-body text-slate-800 min-h-screen lg:h-screen lg:overflow-hidden flex flex-col lg:flex-row antialiased"
      x-data="{
          menuMovil: false,
          modalToken: false,
          nuevoToken: '',
          guardandoToken: false,
          msgToken: '',
          async guardarTokenWa() {
              if (!this.nuevoToken.trim()) return;
              this.guardandoToken = true;
              this.msgToken = '';
              try {
                  const res = await fetch('{{ route('dashboard.api.whatsapp-token') }}', {
                      method: 'POST',
                      headers: {
                          'Content-Type': 'application/json',
                          'X-CSRF-TOKEN': '{{ csrf_token() }}'
                      },
                      body: JSON.stringify({ token: this.nuevoToken.trim() })
                  });
                  const json = await res.json();
                  if (json.ok) {
                      this.msgToken = '✅ ' + json.mensaje;
                      setTimeout(() => { this.modalToken = false; this.nuevoToken = ''; }, 1800);
                  } else {
                      this.msgToken = '❌ ' + (json.mensaje || 'Error al actualizar');
                  }
              } catch (e) {
                  this.msgToken = '❌ Error de conexión';
              } finally {
                  this.guardandoToken = false;
              }
          }
      }">

    <!-- Backdrop para Drawer Móvil -->
    <div x-show="menuMovil"
         x-cloak
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-[#08142C]/75 backdrop-blur-xs z-50 lg:hidden"
         @click="menuMovil = false">
    </div>

    <!-- Drawer Móvil (Slide-Over para Celulares) -->
    <aside x-show="menuMovil"
           x-cloak
           x-transition:enter="transition ease-in-out duration-300 transform"
           x-transition:enter-start="-translate-x-full"
           x-transition:enter-end="translate-x-0"
           x-transition:leave="transition ease-in-out duration-300 transform"
           x-transition:leave-start="translate-x-0"
           x-transition:leave-end="-translate-x-full"
           class="fixed inset-y-0 left-0 w-72 bg-[#08142C] text-white z-50 flex flex-col justify-between shadow-2xl border-r border-white/10 lg:hidden select-none">
        <div>
            <!-- Header Drawer -->
            <div class="px-5 pt-5 pb-4 flex items-center justify-between border-b border-white/10">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2" @click="menuMovil = false">
                    <span class="font-headline font-black text-2xl tracking-wider text-white">KALUZ</span>
                    <span class="text-[10px] font-semibold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 px-2 py-0.5 rounded-full">
                        LIVE
                    </span>
                </a>
                <button type="button" @click="menuMovil = false" class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center text-white/80 hover:text-white">
                    <i class="ri-close-line text-xl"></i>
                </button>
            </div>

            <!-- Navegación Drawer -->
            <nav class="p-3 space-y-1.5 text-sm font-medium">
                <a href="{{ route('dashboard') }}" @click="menuMovil = false"
                   class="flex items-center gap-3 py-2.5 px-3.5 rounded-xl transition {{ request()->is('dashboard') ? 'bg-[#132347] text-white font-semibold border border-white/10' : 'text-slate-300 hover:bg-white/5' }}">
                    <i class="ri-layout-grid-line text-lg {{ request()->is('dashboard') ? 'text-white' : 'text-slate-400' }}"></i>
                    <span>Dashboard KPI</span>
                </a>

                <a href="{{ url('/dashboard#mapa-en-vivo') }}" @click="menuMovil = false"
                   class="flex items-center gap-3 py-2.5 px-3.5 rounded-xl transition text-slate-300 hover:bg-white/5">
                    <i class="ri-map-2-line text-lg text-emerald-400"></i>
                    <span>Mapa GPS Huancayo</span>
                </a>

                <a href="{{ route('finanzas.padron') }}" @click="menuMovil = false"
                   class="flex items-center gap-3 py-2.5 px-3.5 rounded-xl transition {{ request()->is('finanzas/padron*') ? 'bg-[#132347] text-white font-semibold border border-white/10' : 'text-slate-300 hover:bg-white/5' }}">
                    <i class="ri-group-line text-lg {{ request()->is('finanzas/padron*') ? 'text-white' : 'text-slate-400' }}"></i>
                    <span>Conductores y Padrón</span>
                </a>

                <a href="{{ route('finanzas.yape') }}" @click="menuMovil = false"
                   class="flex items-center gap-3 py-2.5 px-3.5 rounded-xl transition {{ request()->is('finanzas/yape*') ? 'bg-[#132347] text-white font-semibold border border-white/10' : 'text-slate-300 hover:bg-white/5' }}">
                    <i class="ri-shield-check-line text-lg {{ request()->is('finanzas/yape*') ? 'text-emerald-400' : 'text-slate-400' }}"></i>
                    <span>Validación Yape (S/ 1)</span>
                </a>

                <a href="{{ route('turismo.index') }}" @click="menuMovil = false"
                   class="flex items-center gap-3 py-2.5 px-3.5 rounded-xl transition {{ request()->is('turismo*') ? 'bg-[#132347] text-white font-semibold border border-white/10' : 'text-slate-300 hover:bg-white/5' }}">
                    <i class="ri-bus-2-line text-lg {{ request()->is('turismo*') ? 'text-white' : 'text-slate-400' }}"></i>
                    <span>Turismo y Combis</span>
                </a>

                <button type="button" @click="menuMovil = false; modalToken = true"
                   class="w-full flex items-center gap-3 py-2.5 px-3.5 rounded-xl transition text-amber-300 bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/30 text-left">
                    <i class="ri-key-2-line text-lg"></i>
                    <span>Actualizar Token Bot WA</span>
                </button>

                <div class="pt-3 mt-3 border-t border-white/10">
                    <p class="px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">App Taxista PWA</p>
                    <a href="{{ route('despacho.seleccion') }}" target="_blank"
                       class="flex items-center justify-between py-2.5 px-3.5 rounded-xl bg-[#E31B23]/15 text-white border border-[#E31B23]/30">
                        <span class="flex items-center gap-2.5 text-xs font-semibold">
                            <i class="ri-smartphone-line text-base text-[#E31B23]"></i>
                            <span>Abrir PWA Conductor</span>
                        </span>
                        <i class="ri-external-link-line text-xs text-slate-300"></i>
                    </a>
                </div>
            </nav>
        </div>

        <!-- Perfil Móvil -->
        <div class="p-4 border-t border-white/10 bg-[#060F22] flex items-center justify-between">
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-[#1E3A8A] to-[#3B82F6] flex items-center justify-center font-headline font-bold text-xs text-white shrink-0">
                    {{ strtoupper(substr(session('nombre', 'Admin'), 0, 1)) }}
                </div>
                <div class="truncate">
                    <p class="text-xs font-semibold text-white truncate">{{ session('nombre', 'Admin') }}</p>
                    <p class="text-[10px] text-emerald-400 flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Conectado
                    </p>
                </div>
            </div>
            <a href="/" title="Cerrar sesión" class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center text-slate-300 hover:text-white">
                <i class="ri-shut-down-line text-base"></i>
            </a>
        </div>
    </aside>

    <!-- Sidebar de Escritorio (lg+) -->
    <aside class="hidden lg:flex w-60 bg-[#08142C] text-white flex-col justify-between shrink-0 shadow-2xl border-r border-white/5 select-none">
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

            <!-- Navegación Principal Escritorio -->
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

                <button type="button" @click="modalToken = true"
                   class="w-full flex items-center gap-3 py-2.5 px-3.5 rounded-xl transition text-amber-300 bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/30 text-left">
                    <i class="ri-key-2-line text-lg"></i>
                    <span>Token WhatsApp</span>
                </button>

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

        <!-- Perfil Admin Inferior Escritorio -->
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
    <main class="flex-1 flex flex-col min-h-screen lg:h-screen lg:overflow-hidden bg-[#DCE6F2]">
        <!-- Topbar Adaptativo (Móvil + Escritorio) -->
        <header class="px-3 sm:px-6 pt-3 sm:pt-4 pb-2 flex items-center justify-between gap-2 sm:gap-4 shrink-0">
            <!-- Botón Hamburguesa + Logo en Móvil -->
            <div class="flex items-center gap-2 lg:hidden">
                <button type="button" @click="menuMovil = true"
                        class="w-9 h-9 rounded-xl bg-white/95 border border-white/60 shadow-xs flex items-center justify-center text-slate-700 hover:text-[#08142C]">
                    <i class="ri-menu-2-line text-lg"></i>
                </button>
                <a href="{{ route('dashboard') }}" class="font-headline font-black text-lg tracking-wider text-[#08142C]">
                    KALUZ
                </a>
            </div>

            <!-- Barra de búsqueda responsiva -->
            <form action="{{ route('finanzas.padron') }}" method="GET" class="relative flex-1 max-w-xl">
                <i class="ri-search-line absolute left-3 sm:left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm sm:text-base"></i>
                <input
                    type="search"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Buscar conductor, placa, DNI..."
                    class="w-full bg-white/90 hover:bg-white focus:bg-white rounded-xl pl-8 sm:pl-10 pr-3 sm:pr-4 py-1.5 sm:py-2 text-xs sm:text-sm text-slate-700 placeholder-slate-400 border border-white/60 shadow-xs focus:outline-none focus:ring-2 focus:ring-[#08142C]/20 transition"
                >
            </form>

            <!-- Acciones Topbar -->
            <div class="flex items-center gap-1.5 sm:gap-3 shrink-0">
                <!-- Botón rápido Token WhatsApp -->
                <button type="button" @click="modalToken = true"
                   class="relative w-8 sm:w-9 h-8 sm:h-9 rounded-xl bg-amber-50 hover:bg-amber-100 border border-amber-200 text-amber-700 shadow-xs flex items-center justify-center transition"
                   title="Configurar Token Meta WhatsApp en vivo">
                    <i class="ri-key-2-line text-sm sm:text-base"></i>
                </button>

                <!-- Notificación Yape -->
                <a href="{{ route('finanzas.yape') }}"
                   class="relative w-8 sm:w-9 h-8 sm:h-9 rounded-xl bg-white/90 hover:bg-white border border-white/60 shadow-xs flex items-center justify-center text-slate-600 hover:text-[#08142C] transition"
                   title="Pagos Yape pendientes de validar">
                    <i class="ri-notification-3-line text-sm sm:text-lg"></i>
                    <span class="absolute top-1 right-1.5 w-2 h-2 rounded-full bg-[#E31B23]"></span>
                </a>

                <!-- Estado Admin -->
                <div class="hidden sm:flex bg-white/90 border border-white/60 shadow-xs rounded-xl px-3 py-1.5 items-center gap-2 text-xs font-medium text-slate-700">
                    <span class="truncate max-w-[100px]">{{ session('nombre', 'Admin') }}</span>
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                </div>
            </div>
        </header>

        <!-- Canvas Principal con Padding para no chocar con el Dock Móvil -->
        <div class="px-3 sm:px-6 pb-24 lg:pb-6 pt-1 sm:pt-2 overflow-y-auto flex-1">
            @yield('content')
        </div>
    </main>

    <!-- Dock Inferior Fijo para Celular (Navegación Táctil a 1 Pulgar para el Administrador) -->
    <nav class="lg:hidden fixed bottom-0 left-0 right-0 z-40 bg-[#08142C]/95 backdrop-blur-md border-t border-white/10 px-2 py-1.5 flex items-center justify-around shadow-2xl select-none">
        <a href="{{ route('dashboard') }}"
           class="flex flex-col items-center py-1 px-2.5 rounded-xl transition {{ request()->is('dashboard') ? 'text-white font-bold bg-white/10' : 'text-slate-400 hover:text-white' }}">
            <i class="ri-layout-grid-line text-lg {{ request()->is('dashboard') ? 'text-white' : '' }}"></i>
            <span class="text-[9px] mt-0.5">KPIs</span>
        </a>

        <a href="{{ url('/dashboard#mapa-en-vivo') }}"
           class="flex flex-col items-center py-1 px-2.5 rounded-xl transition text-slate-400 hover:text-white">
            <i class="ri-map-2-line text-lg text-emerald-400"></i>
            <span class="text-[9px] mt-0.5">Mapa</span>
        </a>

        <a href="{{ route('finanzas.padron') }}"
           class="flex flex-col items-center py-1 px-2.5 rounded-xl transition {{ request()->is('finanzas/padron*') ? 'text-white font-bold bg-white/10' : 'text-slate-400 hover:text-white' }}">
            <i class="ri-group-line text-lg {{ request()->is('finanzas/padron*') ? 'text-white' : '' }}"></i>
            <span class="text-[9px] mt-0.5">Padrón</span>
        </a>

        <a href="{{ route('finanzas.yape') }}"
           class="flex flex-col items-center py-1 px-2.5 rounded-xl transition {{ request()->is('finanzas/yape*') ? 'text-white font-bold bg-white/10' : 'text-slate-400 hover:text-white' }}">
            <i class="ri-shield-check-line text-lg {{ request()->is('finanzas/yape*') ? 'text-emerald-400' : '' }}"></i>
            <span class="text-[9px] mt-0.5">Yape S/1</span>
        </a>

        <a href="{{ route('turismo.index') }}"
           class="flex flex-col items-center py-1 px-2.5 rounded-xl transition {{ request()->is('turismo*') ? 'text-white font-bold bg-white/10' : 'text-slate-400 hover:text-white' }}">
            <i class="ri-bus-2-line text-lg {{ request()->is('turismo*') ? 'text-white' : '' }}"></i>
            <span class="text-[9px] mt-0.5">Turismo</span>
        </a>
    </nav>

    <!-- Modal Global: Actualizar Token WhatsApp en Vivo sin Desplegar Vercel -->
    <div x-show="modalToken" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-[#08142C]/75 backdrop-blur-xs" @click="modalToken = false"></div>
        <div x-transition class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md p-5 sm:p-6 space-y-4 border border-slate-100">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center">
                        <i class="ri-key-2-line text-lg"></i>
                    </div>
                    <div>
                        <h3 class="font-headline font-bold text-base text-[#08142C]">Token WhatsApp en Vivo</h3>
                        <p class="text-[11px] text-slate-500">Actualiza el token temporal de Meta sin re-desplegar</p>
                    </div>
                </div>
                <button type="button" @click="modalToken = false" class="text-slate-400 hover:text-slate-700">
                    <i class="ri-close-line text-xl"></i>
                </button>
            </div>

            <div class="space-y-3 text-xs">
                <p class="text-slate-600 leading-relaxed">
                    Si el token de prueba de 24h de Meta Developers expiró, pega aquí tu nuevo token que inicia con <strong class="text-[#08142C]">EAAP...</strong> y se guardará al instante en Supabase.
                </p>

                <textarea x-model="nuevoToken" rows="4"
                    placeholder="Pega aquí el nuevo Bearer Token (EAAP...)"
                    class="w-full rounded-xl border border-slate-300 p-3 font-mono text-[11px] focus:outline-none focus:ring-2 focus:ring-[#08142C]"></textarea>

                <template x-if="msgToken">
                    <div class="p-2.5 rounded-xl text-xs font-semibold"
                         :class="msgToken.includes('✅') ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-red-50 text-red-800 border border-red-200'"
                         x-text="msgToken">
                    </div>
                </template>
            </div>

            <div class="pt-2 flex justify-end gap-2">
                <button type="button" @click="modalToken = false"
                    class="px-4 py-2.5 rounded-xl border border-slate-300 font-semibold text-slate-600 hover:bg-slate-50 text-xs">
                    Cerrar
                </button>
                <button type="button" @click="guardarTokenWa()" :disabled="guardandoToken || !nuevoToken.trim()"
                    class="px-5 py-2.5 rounded-xl bg-[#08142C] hover:bg-[#13264E] text-white font-headline font-bold shadow-sm text-xs flex items-center gap-1.5 disabled:opacity-50">
                    <i class="ri-save-line" x-show="!guardandoToken"></i>
                    <i class="ri-loader-4-line animate-spin" x-show="guardandoToken"></i>
                    <span x-text="guardandoToken ? 'Guardando...' : 'Guardar y Activar'"></span>
                </button>
            </div>
        </div>
    </div>

    @yield('scripts')
</body>
</html>

