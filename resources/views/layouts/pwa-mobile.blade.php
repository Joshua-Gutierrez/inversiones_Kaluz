<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0B1B3D">
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

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fonts

    <style>
        [x-cloak] { display: none !important; }
        body { -webkit-tap-highlight-color: transparent; }
    </style>
    @yield('head')
</head>
<body class="bg-[#F4F5F7] font-body text-[#0B1B3D] min-h-screen flex flex-col select-none antialiased">

    {{-- Header azul marino corporativo --}}
    <header class="bg-[#0B1B3D] px-4 py-3 flex items-center justify-between shadow-md sticky top-0 z-40 safe-top">
        <div class="flex items-center gap-2.5">
            <span class="w-9 h-9 rounded-xl bg-[#E31B23] flex items-center justify-center font-headline font-black text-white text-sm shadow-md">K</span>
            <div class="leading-tight">
                <p class="font-headline font-bold text-white text-sm">@yield('header_titulo', 'Taxi KALUZ')</p>
                <p class="text-[11px] text-slate-300 font-body">@yield('header_sub', 'Conductor • Huancayo')</p>
            </div>
        </div>
        @yield('header_derecha')
    </header>

    {{-- Contenido principal mobile-first (viewport 390 px) --}}
    <main class="flex-1 overflow-y-auto p-4 max-w-md w-full mx-auto pb-24">
        @yield('content')
    </main>

    @yield('footer')

    <script>
        // Registro del Service Worker (PWA instalable)
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('{{ asset('sw.js') }}').catch(() => {});
            });
        }
    </script>
    @yield('scripts')
</body>
</html>
