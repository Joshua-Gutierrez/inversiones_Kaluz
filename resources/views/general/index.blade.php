@extends('layouts.torre-control')

@section('titulo', 'Guía General de Estilos y Colores - Taxi Kaluz')
@section('encabezado', 'Sistema de Diseño y Estilos Generales (Brand Guidelines)')

@section('content')
<div class="space-y-8 max-w-7xl mx-auto">
    <!-- Header de Bienvenida del Sistema de Diseño -->
    <div class="bg-gradient-to-r from-[#08183D] via-[#081328] to-[#08183D] rounded-2xl p-6 text-white shadow-lg flex flex-col md:flex-row items-start md:items-center justify-between gap-4 border border-white/10">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="w-3 h-3 rounded-full bg-[#E31B23] animate-pulse"></span>
                <span class="text-xs uppercase font-headline tracking-widest text-[#E31B23] font-bold">Identidad Visual Kaluz</span>
            </div>
            <h2 class="text-2xl font-headline font-bold">Catálogo de Tokens, Colores y Componentes</h2>
            <p class="text-sm font-body text-slate-300 mt-1">Directorio unificado disponible para todos los módulos de la aplicación (Dev 1, Dev 2, Dev 3 y Dev 4).</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="px-3 py-1 bg-white/10 text-white rounded-lg text-xs font-label font-medium border border-white/10">Tailwind CSS v4</span>
            <span class="px-3 py-1 bg-[#E31B23] text-white rounded-lg text-xs font-label font-bold">Oficial Kaluz</span>
        </div>
    </div>

    <!-- Tablero Principal inspirado en el Board de Diseño -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 bg-[#EAF0F8] p-6 rounded-3xl border border-slate-200/80 shadow-inner">

        <!-- ================= COLUMNA IZQUIERDA: PALETAS DE COLOR (4 Cols) ================= -->
        <div class="lg:col-span-4 space-y-4">
            
            <!-- Primary Card -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm transition hover:shadow-md">
                <div class="flex items-center justify-between mb-3">
                    <span class="font-headline font-bold text-sm text-[#08183D]">Primary</span>
                    <span class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-slate-100 text-[#08183D]">#08183D</span>
                </div>
                <div class="h-16 rounded-xl bg-[#08183D] mb-3 flex items-center justify-center text-white font-headline text-xs font-bold tracking-wider shadow-inner">
                    AZUL MARINO KALUZ
                </div>
                <!-- Shade Ramp -->
                <div class="flex h-6 rounded-lg overflow-hidden border border-slate-200 shadow-xs">
                    <div class="flex-1 bg-[#040C1F]" title="#040C1F (950)"></div>
                    <div class="flex-1 bg-[#08183D]" title="#08183D (900)"></div>
                    <div class="flex-1 bg-[#0D255B]" title="#0D255B (800)"></div>
                    <div class="flex-1 bg-[#13347A]" title="#13347A (700)"></div>
                    <div class="flex-1 bg-[#1E4CAE]" title="#1E4CAE (600)"></div>
                    <div class="flex-1 bg-[#3066D6]" title="#3066D6 (500)"></div>
                    <div class="flex-1 bg-[#5C87E2]" title="#5C87E2 (400)"></div>
                    <div class="flex-1 bg-[#8CAAF0]" title="#8CAAF0 (300)"></div>
                    <div class="flex-1 bg-[#BDCEF7]" title="#BDCEF7 (200)"></div>
                    <div class="flex-1 bg-[#DEE7FB]" title="#DEE7FB (100)"></div>
                    <div class="flex-1 bg-[#EEF3FD]" title="#EEF3FD (50)"></div>
                </div>
            </div>

            <!-- Secondary Card -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm transition hover:shadow-md">
                <div class="flex items-center justify-between mb-3">
                    <span class="font-headline font-bold text-sm text-[#E31B23]">Secondary</span>
                    <span class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-red-50 text-[#E31B23]">#E31B23</span>
                </div>
                <div class="h-16 rounded-xl bg-[#E31B23] mb-3 flex items-center justify-center text-white font-headline text-xs font-bold tracking-wider shadow-inner">
                    ROJO KALUZ
                </div>
                <!-- Shade Ramp -->
                <div class="flex h-6 rounded-lg overflow-hidden border border-slate-200 shadow-xs">
                    <div class="flex-1 bg-[#4C0509]" title="#4C0509 (950)"></div>
                    <div class="flex-1 bg-[#7A0C12]" title="#7A0C12 (900)"></div>
                    <div class="flex-1 bg-[#A71118]" title="#A71118 (800)"></div>
                    <div class="flex-1 bg-[#C6161E]" title="#C6161E (700)"></div>
                    <div class="flex-1 bg-[#E31B23]" title="#E31B23 (600)"></div>
                    <div class="flex-1 bg-[#EB434A]" title="#EB434A (500)"></div>
                    <div class="flex-1 bg-[#F27378]" title="#F27378 (400)"></div>
                    <div class="flex-1 bg-[#F7A3A7]" title="#F7A3A7 (300)"></div>
                    <div class="flex-1 bg-[#FBD1D3]" title="#FBD1D3 (200)"></div>
                    <div class="flex-1 bg-[#FDE8E9]" title="#FDE8E9 (100)"></div>
                    <div class="flex-1 bg-[#FEF4F4]" title="#FEF4F4 (50)"></div>
                </div>
            </div>

            <!-- Tertiary Card -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm transition hover:shadow-md">
                <div class="flex items-center justify-between mb-3">
                    <span class="font-headline font-bold text-sm text-[#081328]">Tertiary</span>
                    <span class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-slate-100 text-[#081328]">#081328</span>
                </div>
                <div class="h-16 rounded-xl bg-[#081328] mb-3 flex items-center justify-center text-white font-headline text-xs font-bold tracking-wider shadow-inner">
                    MIDNIGHT OSCURO
                </div>
                <!-- Shade Ramp -->
                <div class="flex h-6 rounded-lg overflow-hidden border border-slate-200 shadow-xs">
                    <div class="flex-1 bg-[#02050A]"></div>
                    <div class="flex-1 bg-[#040914]"></div>
                    <div class="flex-1 bg-[#081328]"></div>
                    <div class="flex-1 bg-[#102244]"></div>
                    <div class="flex-1 bg-[#1B3666]"></div>
                    <div class="flex-1 bg-[#2C5296]"></div>
                    <div class="flex-1 bg-[#4A72BC]"></div>
                    <div class="flex-1 bg-[#7C9CD7]"></div>
                    <div class="flex-1 bg-[#B5C6EC]"></div>
                    <div class="flex-1 bg-[#E2EAF8]"></div>
                </div>
            </div>

            <!-- Neutral Card -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm transition hover:shadow-md">
                <div class="flex items-center justify-between mb-3">
                    <span class="font-headline font-bold text-sm text-[#64748B]">Neutral</span>
                    <span class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-slate-100 text-[#64748B]">#64748B</span>
                </div>
                <div class="h-16 rounded-xl bg-[#64748B] mb-3 flex items-center justify-center text-white font-headline text-xs font-bold tracking-wider shadow-inner">
                    SLATE GRIS NEUTRAL
                </div>
                <!-- Shade Ramp -->
                <div class="flex h-6 rounded-lg overflow-hidden border border-slate-200 shadow-xs">
                    <div class="flex-1 bg-[#0F172A]"></div>
                    <div class="flex-1 bg-[#1E293B]"></div>
                    <div class="flex-1 bg-[#334155]"></div>
                    <div class="flex-1 bg-[#475569]"></div>
                    <div class="flex-1 bg-[#64748B]"></div>
                    <div class="flex-1 bg-[#94A3B8]"></div>
                    <div class="flex-1 bg-[#CBD5E1]"></div>
                    <div class="flex-1 bg-[#E2E8F0]"></div>
                    <div class="flex-1 bg-[#F1F5F9]"></div>
                    <div class="flex-1 bg-[#F8FAFC]"></div>
                </div>
            </div>

        </div>

        <!-- ================= COLUMNA CENTRAL: TIPOGRAFÍAS (4 Cols) ================= -->
        <div class="lg:col-span-4 space-y-4">
            
            <!-- Headline Card (Montserrat) -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm flex flex-col justify-between transition hover:shadow-md h-[180px]">
                <div class="flex items-center justify-between">
                    <span class="font-body text-xs text-[#64748B] uppercase tracking-wider">Headline</span>
                    <span class="font-headline font-semibold text-xs text-[#08183D]">Montserrat</span>
                </div>
                <div class="my-auto">
                    <p class="font-headline text-6xl font-bold text-[#08183D] tracking-tight">Aa</p>
                </div>
                <p class="font-headline text-xs text-slate-400">Títulos H1-H6, Métricas principales, Encabezados</p>
            </div>

            <!-- Body Card (Inter) -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm flex flex-col justify-between transition hover:shadow-md min-h-[220px]">
                <div class="flex items-center justify-between">
                    <span class="font-body text-xs text-[#64748B] uppercase tracking-wider">Body</span>
                    <span class="font-body font-semibold text-xs text-[#08183D]">Inter</span>
                </div>
                <div class="my-3">
                    <p class="font-body text-6xl font-normal text-[#334155]">Aa</p>
                </div>
                <div class="space-y-2 mt-2">
                    <div class="h-1.5 w-3/4 bg-[#08183D] rounded-full"></div>
                    <div class="h-1.5 w-full bg-[#E31B23] rounded-full"></div>
                    <div class="h-1.5 w-1/2 bg-[#081328] rounded-full"></div>
                </div>
                <p class="font-body text-xs text-slate-400 mt-3">Párrafos, tablas de datos, modales y textos descriptivos</p>
            </div>

            <!-- Label Card (Inter) -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm flex flex-col justify-between transition hover:shadow-md h-[180px]">
                <div class="flex items-center justify-between">
                    <span class="font-body text-xs text-[#64748B] uppercase tracking-wider">Label</span>
                    <span class="font-body font-semibold text-xs text-[#08183D]">Inter</span>
                </div>
                <div class="my-auto">
                    <p class="font-label text-5xl font-medium text-[#475569]">Aa</p>
                </div>
                <p class="font-label text-xs text-slate-400">Inputs, botones, chips, badges de estado y micro-textos</p>
            </div>

        </div>

        <!-- ================= COLUMNA DERECHA: COMPONENTES Y ACCIONES (4 Cols) ================= -->
        <div class="lg:col-span-4 space-y-4">
            
            <!-- Botones Primarios y Secundarios -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-4">
                <span class="font-body text-xs text-[#64748B] uppercase tracking-wider block">Variantes de Botones</span>
                
                <div class="grid grid-cols-2 gap-3">
                    <x-general.button variant="primary">
                        Primary
                    </x-general.button>
                    <x-general.button variant="secondary">
                        Secondary
                    </x-general.button>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <x-general.button variant="inverted">
                        Inverted
                    </x-general.button>
                    <x-general.button variant="outlined">
                        Outlined
                    </x-general.button>
                </div>

                <x-general.button variant="accent" class="w-full">
                    <span>⚡ Acción Kaluz Red (#E31B23)</span>
                </x-general.button>
            </div>

            <!-- Search Bar Component -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm">
                <span class="font-body text-xs text-[#64748B] uppercase tracking-wider block mb-3">Campo de Búsqueda</span>
                <x-general.search placeholder="Search..." />
            </div>

            <!-- Floating Pill Navigation Dock -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm flex flex-col items-center justify-center">
                <span class="font-body text-xs text-[#64748B] uppercase tracking-wider block mb-3 self-start">Dock de Navegación Flotante</span>
                <x-general.dock active="home" />
            </div>

            <!-- Icon Buttons & Badges -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm space-y-4">
                <span class="font-body text-xs text-[#64748B] uppercase tracking-wider block">Botones de Ícono y Badges</span>
                
                <div class="flex items-center gap-3">
                    <!-- Dark square icon button -->
                    <x-general.icon-button variant="primary" title="Editar">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                    </x-general.icon-button>

                    <!-- Red square icon button -->
                    <x-general.icon-button variant="accent" title="Jerarquía / Flota">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    </x-general.icon-button>

                    <!-- Dark square tag icon -->
                    <x-general.icon-button variant="inverted" title="Etiqueta">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                    </x-general.icon-button>

                    <!-- Red delete icon -->
                    <x-general.icon-button variant="accent" title="Eliminar">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </x-general.icon-button>
                </div>

                <div class="flex flex-wrap items-center gap-2 pt-2">
                    <x-general.badge variant="navy">
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                        Label Primario
                    </x-general.badge>

                    <x-general.badge variant="red">
                        S/ 1.00 Bloqueo
                    </x-general.badge>

                    <x-general.badge variant="neutral">
                        Combi Turística
                    </x-general.badge>
                </div>
            </div>

        </div>

    </div>

    <!-- Guía Rápida para Desarrolladores -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
        <h3 class="font-headline text-lg font-bold text-[#08183D] mb-2">¿Cómo usar estos estilos en cualquier vista Blade?</h3>
        <p class="font-body text-sm text-[#64748B] mb-4">Todos los componentes residen en la carpeta general <code class="bg-slate-100 px-2 py-0.5 rounded text-xs text-[#08183D] font-mono">resources/views/components/general/</code> y las clases utilitarias en <code class="bg-slate-100 px-2 py-0.5 rounded text-xs text-[#08183D] font-mono">resources/css/app.css</code>.</p>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs font-mono">
            <div class="bg-slate-900 text-slate-200 p-4 rounded-xl overflow-x-auto">
                <span class="text-amber-400 block mb-2 font-bold">// Botones Reutilizables</span>
                <code>&lt;x-general.button variant="primary"&gt;Guardar&lt;/x-general.button&gt;</code><br>
                <code>&lt;x-general.button variant="accent"&gt;Confirmar&lt;/x-general.button&gt;</code><br>
                <code>&lt;x-general.button variant="secondary"&gt;Cancelar&lt;/x-general.button&gt;</code>
            </div>

            <div class="bg-slate-900 text-slate-200 p-4 rounded-xl overflow-x-auto">
                <span class="text-amber-400 block mb-2 font-bold">// Tarjetas y Búsquedas</span>
                <code>&lt;x-general.card title="Viajes Hoy" subtitle="Actualizado"&gt;</code><br>
                <code>    &lt;p&gt;Contenido...&lt;/p&gt;</code><br>
                <code>&lt;/x-general.card&gt;</code><br>
                <code>&lt;x-general.search placeholder="Buscar conductor..." /&gt;</code>
            </div>

            <div class="bg-slate-900 text-slate-200 p-4 rounded-xl overflow-x-auto">
                <span class="text-amber-400 block mb-2 font-bold">// Colores y Fuentes</span>
                <code>class="font-headline text-[#08183D]"</code><br>
                <code>class="font-body text-[#64748B]"</code><br>
                <code>class="bg-[#08183D] text-white"</code><br>
                <code>class="bg-[#E31B23] text-white"</code>
            </div>
        </div>
    </div>
</div>
@endsection
