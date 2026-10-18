<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Taxi Kaluz</title>

    <!-- Google Fonts: Montserrat (Headline) & Inter (Body/Label) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#EAF0F8] font-body flex items-center justify-center min-h-screen p-4 antialiased selection:bg-[#E31B23] selection:text-white">
    <div class="w-full max-w-md">
        <!-- Brand Header -->
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-[#08183D] text-white shadow-lg mb-3 border border-white/10">
                <span class="w-4 h-4 rounded-full bg-[#E31B23]"></span>
            </div>
            <h1 class="text-3xl font-headline font-black text-[#08183D] tracking-tight">TAXI KALUZ</h1>
            <p class="font-body text-[#64748B] text-sm mt-1">Torre de Control & Gestión Operativa</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white p-8 rounded-2xl shadow-xl border border-slate-200/80 transition-all">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-6">
                <div>
                    <h2 class="font-headline font-bold text-lg text-[#08183D]">Acceso al Sistema</h2>
                    <p class="font-body text-xs text-[#64748B]">Autenticación validada con Supabase</p>
                </div>
                <span class="text-[10px] font-label font-bold px-2 py-0.5 rounded-full bg-slate-100 text-[#08183D] border border-slate-200">
                    v2.0
                </span>
            </div>

            @if ($errors->has('error'))
                <div class="mb-5 p-3.5 bg-red-50 border border-red-200 text-[#E31B23] rounded-xl text-xs font-body flex items-center gap-2">
                    <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                    <span>{{ $errors->first('error') }}</span>
                </div>
            @endif

            <form method="POST" action="/login" class="space-y-4">
                @csrf
                <div>
                    <label class="block font-label text-xs font-semibold text-[#08183D] uppercase tracking-wider mb-1.5">Usuario</label>
                    <div class="relative flex items-center">
                        <input 
                            type="text" 
                            name="usuario" 
                            id="login-usuario"
                            class="w-full px-4 py-3 bg-[#F8FAFC] border border-slate-200 rounded-xl font-body text-sm text-[#08183D] focus:outline-none focus:border-[#08183D] focus:ring-2 focus:ring-[#08183D]/10 transition" 
                            value="{{ old('usuario') }}"
                            placeholder="Ingresa tu usuario"
                            required
                        >
                    </div>
                </div>

                <div>
                    <label class="block font-label text-xs font-semibold text-[#08183D] uppercase tracking-wider mb-1.5">Contraseña</label>
                    <div class="relative flex items-center">
                        <input 
                            type="password" 
                            name="password" 
                            id="login-password"
                            class="w-full px-4 py-3 bg-[#F8FAFC] border border-slate-200 rounded-xl font-body text-sm text-[#08183D] focus:outline-none focus:border-[#08183D] focus:ring-2 focus:ring-[#08183D]/10 transition" 
                            placeholder="••••••••"
                            required
                        >
                    </div>
                </div>

                <div class="pt-2">
                    <button 
                        type="submit" 
                        class="w-full bg-[#08183D] hover:bg-[#0D255B] text-white font-headline font-bold py-3 px-4 rounded-xl shadow-md hover:shadow-lg transition-all duration-200 flex items-center justify-center gap-2"
                    >
                        <span>Ingresar al Sistema</span>
                        <svg class="w-4 h-4 text-[#E31B23]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </button>
                </div>
            </form>

            <div class="mt-6 pt-5 border-t border-slate-100 flex items-center justify-between text-xs font-body text-[#64748B]">
                <a href="{{ route('general.index') }}" class="text-[#08183D] hover:underline font-semibold flex items-center gap-1">
                    <span>🎨</span> Guía de Estilos
                </a>
                <span>Taxi Kaluz • 2026</span>
            </div>
        </div>
    </div>
</body>
</html>
