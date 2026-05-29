{{-- Название сайта в шапке: на телефоне — две строки, на десктопе — одна. --}}
<a href="{{ $href ?? url('/') }}" class="site-brand group">
    <span class="hidden lg:inline text-xl font-bold tracking-tight text-slate-900 transition-colors group-hover:text-brand-700 xl:text-2xl">
        Агентство недвижимости
    </span>
    <span class="lg:hidden leading-tight">
        <span class="block text-[0.9375rem] font-bold tracking-tight text-slate-900 transition-colors group-hover:text-brand-700 sm:text-lg">Агентство</span>
        <span class="block text-[0.9375rem] font-bold tracking-tight text-brand-700 sm:text-lg">недвижимости</span>
    </span>
</a>
