@extends('layouts.app')

@section('content')
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white p-6 rounded-lg shadow border-l-4 border-blue-500">
            <h3 class="text-gray-500 text-sm font-bold uppercase">Viajes Hoy</h3>
            <p class="text-3xl font-bold text-gray-800 mt-2">142</p>
        </div>
    </div>
    <div class="bg-white p-6 rounded-lg shadow">
        <h3 class="text-lg font-semibold mb-4 text-gray-700">Estado de Flota</h3>
        <p class="text-gray-500 text-sm">Cargando datos...</p>
    </div>
@endsection
