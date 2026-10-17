@extends('layouts.torre-control')

@section('titulo', 'Padrón de Socios y Candado S/ 1.00 - Taxi Kaluz')
@section('encabezado', '👨‍💻 Zona Dev 2: Padrón de 300 Socios y Candado Financiero (S/ 1.00)')

@section('content')
    <div class="space-y-6">
        <!-- Barra de búsqueda rápida con componente general -->
        <div class="flex flex-col md:flex-row items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="w-full md:w-96">
                <x-general.search placeholder="Buscar socio por nombre, DNI o placa..." />
            </div>
            <div class="flex items-center gap-2">
                <x-general.button variant="primary" size="sm">
                    <span>+ Registrar Nuevo Socio</span>
                </x-general.button>
                <x-general.button variant="secondary" size="sm">
                    <span>Descargar Reporte Excel</span>
                </x-general.button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <x-tarjeta-kaluz titulo="Socios Habilitados" subtitulo="Al día con S/ 1.00 y SOAT">
                <p class="font-headline text-3xl font-bold text-emerald-600">248</p>
                <p class="font-body text-xs text-[#64748B] mt-1">Con acceso libre a la PWA móvil</p>
            </x-tarjeta-kaluz>
            <x-tarjeta-kaluz titulo="Candado S/ 1.00 Activo" subtitulo="Pendientes de validar Yape">
                <p class="font-headline text-3xl font-bold text-[#E31B23]">39</p>
                <p class="font-body text-xs text-[#E31B23] mt-1 font-semibold">Bloqueo automático de servicios</p>
            </x-tarjeta-kaluz>
            <x-tarjeta-kaluz titulo="Permiso / SOAT por Vencer" subtitulo="Alerta preventiva">
                <p class="font-headline text-3xl font-bold text-amber-600">13</p>
                <p class="font-body text-xs text-[#64748B] mt-1">Notificación enviada por WhatsApp</p>
            </x-tarjeta-kaluz>
        </div>

        <x-tarjeta-kaluz titulo="Padrón General de Conductores" subtitulo="Control en tiempo real de comisiones de S/ 1.00 y validación de comprobantes Yape">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm font-body">
                    <thead>
                        <tr class="border-b border-slate-200 text-[#64748B] uppercase font-label text-xs">
                            <th class="py-3 px-4">Socio Conductor</th>
                            <th class="py-3 px-4">Placa / Unidad</th>
                            <th class="py-3 px-4">Deuda Comisión</th>
                            <th class="py-3 px-4">Estado Semáforo</th>
                            <th class="py-3 px-4 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="py-3 px-4 font-semibold text-[#08183D]">Juan Pérez Huamán</td>
                            <td class="py-3 px-4 text-[#64748B]">W1K-452 • Toyota Yaris</td>
                            <td class="py-3 px-4 font-medium text-emerald-600">S/ 0.00</td>
                            <td class="py-3 px-4"><x-badge-semaforo estado="habilitado" /></td>
                            <td class="py-3 px-4 text-right">
                                <x-general.button variant="outlined" size="sm">Historial</x-general.button>
                            </td>
                        </tr>
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="py-3 px-4 font-semibold text-[#08183D]">Carlos Quispe Rojas</td>
                            <td class="py-3 px-4 text-[#64748B]">W3T-891 • Hyundai Accent</td>
                            <td class="py-3 px-4 font-bold text-[#E31B23]">S/ 1.00</td>
                            <td class="py-3 px-4"><x-badge-semaforo estado="bloqueado" /></td>
                            <td class="py-3 px-4 text-right">
                                <x-general.button variant="accent" size="sm">Validar Yape</x-general.button>
                            </td>
                        </tr>
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="py-3 px-4 font-semibold text-[#08183D]">Manuel Canchanya Ortiz</td>
                            <td class="py-3 px-4 text-[#64748B]">W2B-310 • Nissan Sentra</td>
                            <td class="py-3 px-4 font-medium text-amber-600">S/ 0.00</td>
                            <td class="py-3 px-4"><x-badge-semaforo estado="alerta" /></td>
                            <td class="py-3 px-4 text-right">
                                <x-general.button variant="secondary" size="sm">Avisar SOAT</x-general.button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </x-tarjeta-kaluz>
    </div>
@endsection
