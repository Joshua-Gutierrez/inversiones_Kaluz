@props([
    'active' => 'home'
])

<div {{ $attributes->merge(['class' => 'inline-flex items-center gap-3 bg-[#EAF0F8] border border-slate-200/60 rounded-2xl p-2 shadow-sm']) }}>
    <a href="/dashboard" class="w-10 h-10 rounded-xl flex items-center justify-center transition-all {{ $active === 'home' ? 'bg-[#08183D] text-white shadow-sm' : 'text-[#64748B] hover:text-[#08183D]' }}" title="Inicio">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
        </svg>
    </a>
    
    <a href="/general" class="w-10 h-10 rounded-xl flex items-center justify-center transition-all {{ $active === 'search' ? 'bg-[#08183D] text-white shadow-sm' : 'text-[#64748B] hover:text-[#08183D]' }}" title="Buscar / Explorar">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
        </svg>
    </a>
    
    <a href="#perfil" class="w-10 h-10 rounded-xl flex items-center justify-center transition-all {{ $active === 'profile' ? 'bg-[#08183D] text-white shadow-sm' : 'text-[#64748B] hover:text-[#08183D]' }}" title="Perfil">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
        </svg>
    </a>
</div>
