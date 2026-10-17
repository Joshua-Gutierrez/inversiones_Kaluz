@props([
    'placeholder' => 'Search',
    'name' => 'q',
    'id' => 'general-search-input',
    'value' => '',
])

<div {{ $attributes->merge(['class' => 'relative flex items-center bg-[#F8FAFC] border border-slate-200 rounded-xl px-3.5 py-2 w-full focus-within:border-[#08183D] focus-within:ring-2 focus-within:ring-[#08183D]/10 transition-all']) }}>
    <svg class="w-4 h-4 text-[#64748B] shrink-0 mr-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
    </svg>
    <input 
        type="text" 
        name="{{ $name }}" 
        id="{{ $id }}"
        placeholder="{{ $placeholder }}" 
        value="{{ $value }}"
        class="w-full bg-transparent border-none outline-none font-body text-sm text-[#08183D] placeholder-[#94A3B8]"
    />
</div>
