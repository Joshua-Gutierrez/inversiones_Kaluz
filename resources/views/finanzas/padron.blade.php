@extends('layouts.torre-control')

@section('titulo', 'Padrón de Socios y Candado S/ 1.00 - Taxi Kaluz')
@section('encabezado', '👨‍💻 Zona Dev 2: Padrón de 300 Socios y Candado Financiero (S/ 1.00)')

@section('content')
    <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-tarjeta-kaluz titulo="Socios Habilitados" subtitulo="Al día con S/ 1.00 y SOAT">
                <p class="text-3xl font-extrabold text-emerald-600">248</p>
            </x-tarjeta-kaluz>
            <x-tarjeta-kaluz titulo="Candado S/ 1.00 Activo" subtitulo="Pendientes de validar Yape">
                <p class="text-3xl font-extrabold text-red-600">39</p>
            </x-tarjeta-kaluz>
            <x-tarjeta-kaluz titulo="Permiso / SOAT por Vencer" subtitulo="Alerta preventiva">
                <p class="text-3xl font-extrabold text-amber-600">13</p>
            </x-tarjeta-kaluz>
        </div>

        <x-tarjeta-kaluz titulo="Padrón General de Conductores" subtitulo="Control en tiempo real de comisiones de S/ 1.00 y validación de comprobantes Yape">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-slate-500 uppercase text-xs">
                            <th class="py-3 px-4">Socio Conductor</th>
                            <th class="py-3 px-4">Placa / Unidad</th>
                            <th class="py-3 px-4">Deuda Comisión</th>
                            <th class="py-3 px-4">Estado Semáforo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr>
                            <td class="py-3 px-4 font-semibold text-slate-800">Juan Pérez Huamán</td>
                            <td class="py-3 px-4 text-slate-600">W1K-452 • Toyota Yaris</td>
                            <td class="py-3 px-4 font-medium text-emerald-600">S/ 0.00</td>
                            <td class="py-3 px-4"><x-badge-semaforo estado="habilitado" /></td>
                        </tr>
                        <tr>
                            <td class="py-3 px-4 font-semibold text-slate-800">Carlos Quispe Rojas</td>
                            <td class="py-3 px-4 text-slate-600">W3T-891 • Hyundai Accent</td>
                            <td class="py-3 px-4 font-bold text-red-600">S/ 1.00</td>
                            <td class="py-3 px-4"><x-badge-semaforo estado="bloqueado" /></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </x-tarjeta-kaluz>
    </div>
@endsection
