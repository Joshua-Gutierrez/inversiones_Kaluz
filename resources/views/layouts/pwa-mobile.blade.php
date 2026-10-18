<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0A1329">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="KALUZ Conductor">
    <meta name="description" content="Taxi KALUZ - Radar de viajes para conductores de Huancayo">

    <title>@yield('titulo', 'Taxi KALUZ Conductor')</title>

    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('icons/icon-192.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/icon-192.png') }}">

    <!-- Google Fonts: Montserrat & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@600;700;800;900&display=swap" rel="stylesheet">

    <!-- RemixIcon (Iconos reales vectoriales, cero emojis) -->
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
                    colors: {
                        kaluz: {
                            navy: '#0A1329',
                            card: '#111E3B',
                            red: '#E31B23',
                            bg: '#DCE6F2',
                        },
                    },
                    fontFamily: {
                        headline: ['Montserrat', 'sans-serif'],
                        body: ['Inter', 'sans-serif'],
                    },
                },
            },
        };
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        body { -webkit-tap-highlight-color: transparent; }
    </style>
    @yield('head')
</head>
@php
    $temaOscuro = trim($__env->yieldContent('tema', 'oscuro')) === 'oscuro';
    $navConductorId = isset($conductor) && isset($conductor->id)
        ? $conductor->id
        : (request()->input('conductor_id') ?? session('conductor_id'));
    $navParams = $navConductorId ? ['conductor_id' => $navConductorId] : [];
@endphp
<body class="{{ $temaOscuro ? 'bg-[#081124] text-white' : 'bg-[#DCE6F2] text-[#0A1329]' }} font-body min-h-screen flex flex-col select-none antialiased">

    {{-- Header superior móvil --}}
    @hasSection('ocultar_header')
    @else
        <header class="bg-[#0A1329] border-b border-white/10 px-4 py-3 sticky top-0 z-40">
            <div class="max-w-md mx-auto flex items-center justify-between gap-2">
                @hasSection('ocultar_nav')
                    <span class="font-headline font-black text-xl tracking-wider text-white shrink-0">
                        KALUZ
                    </span>
                @else
                    <a href="{{ route('despacho.pwa', $navParams) }}" class="font-headline font-black text-xl tracking-wider text-white shrink-0">
                        KALUZ
                    </a>
                @endif

                @yield('header_centro')

                <div class="flex items-center gap-2 min-w-0">
                    @yield('header_derecha')
                </div>
            </div>
        </header>
    @endif

    {{-- Contenido principal mobile-first (viewport ~410px centrado en desktop) --}}
    <main class="flex-1 overflow-y-auto px-4 pt-4 @hasSection('ocultar_nav') pb-6 @else pb-28 @endif max-w-md w-full mx-auto">
        @yield('content')
    </main>

    {{-- Barra de navegación inferior (4 pestañas de la Imagen 1 con iconos reales) --}}
    @hasSection('ocultar_nav')
    @else
        <nav class="fixed bottom-0 inset-x-0 z-40 bg-[#1B2845]/95 backdrop-blur-md border-t border-white/10">
            <div class="max-w-md mx-auto grid grid-cols-4 text-[11px] font-medium">
                <a href="{{ route('despacho.pwa', $navParams) }}"
                   class="flex flex-col items-center justify-center py-2.5 px-1 transition {{ request()->routeIs('despacho.pwa') || request()->routeIs('despacho.viaje') ? 'bg-[#081124] text-[#E31B23] font-bold' : 'text-slate-300 hover:text-white' }}">
                    <i class="ri-map-2-fill text-xl leading-none mb-1"></i>
                    <span>Radar/Mapa</span>
                </a>

                <a href="{{ route('despacho.cuota', $navParams) }}"
                   class="flex flex-col items-center justify-center py-2.5 px-1 transition {{ request()->routeIs('despacho.cuota') || request()->routeIs('despacho.desbloqueado') ? 'bg-[#081124] text-emerald-400 font-bold' : 'text-slate-300 hover:text-white' }}">
                    <i class="ri-cash-line text-xl leading-none mb-1"></i>
                    <span class="leading-tight text-center">Mis Ingresos<br><span class="text-[9px] opacity-80">(S/ 1.00 diario)</span></span>
                </a>

                <a href="{{ route('despacho.seleccion', $navParams) }}"
                   class="flex flex-col items-center justify-center py-2.5 px-1 transition {{ request()->routeIs('despacho.seleccion') ? 'bg-[#081124] text-white font-bold' : 'text-slate-300 hover:text-white' }}">
                    <i class="ri-user-3-fill text-xl leading-none mb-1"></i>
                    <span>Mi Cuenta</span>
                </a>

                <a href="{{ route('despacho.pwa.bloqueado', $navParams) }}"
                   class="flex flex-col items-center justify-center py-2.5 px-1 transition {{ request()->routeIs('despacho.pwa.bloqueado') ? 'bg-[#081124] text-[#E31B23] font-bold' : 'text-slate-300 hover:text-white' }}">
                    <i class="ri-notification-3-fill text-xl leading-none mb-1"></i>
                    <span>Alertas</span>
                </a>
            </div>
        </nav>
    @endif

    @yield('footer')

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('{{ asset('sw.js') }}').catch(() => {});
            });
        }

        @if (!View::hasSection('ocultar_nav') && !empty($navConductorId) && !request()->routeIs('despacho.viaje'))
        (function () {
            const conductorId = {{ (int) $navConductorId }};
            const urlUbicacion = @json(route('despacho.api.ubicacion', ['conductor_id' => (int) $navConductorId]));
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
            let latActual = {{ isset($conductor) && !empty($conductor->lat_actual) ? (float) $conductor->lat_actual : -12.0651 }};
            let lngActual = {{ isset($conductor) && !empty($conductor->lng_actual) ? (float) $conductor->lng_actual : -75.2049 }};

            function enviarGpsPwa(lat, lng) {
                fetch(urlUbicacion, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ lat: lat, lng: lng, conductor_id: conductorId }),
                }).catch(() => {});
            }

            function reportarUbicacionPwa() {
                if (document.hidden) return;
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        (pos) => {
                            latActual = pos.coords.latitude;
                            lngActual = pos.coords.longitude;
                            enviarGpsPwa(latActual, lngActual);
                        },
                        () => {
                            enviarGpsPwa(latActual, lngActual);
                        },
                        { enableHighAccuracy: true, timeout: 6000, maximumAge: 4000 }
                    );
                } else {
                    enviarGpsPwa(latActual, lngActual);
                }
            }

            window.addEventListener('load', () => {
                reportarUbicacionPwa();
                setInterval(reportarUbicacionPwa, 6000);
            });
        })();
        @endif
    </script>
    @yield('scripts')
</body>
</html>
