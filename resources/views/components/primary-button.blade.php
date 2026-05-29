{{-- Зелёная основная кнопка. --}}
<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center rounded-xl border border-transparent bg-brand-600 px-5 py-2.5 text-sm font-medium text-white shadow-md shadow-brand-600/20 transition-all duration-200 hover:bg-brand-700 hover:shadow-lg hover:shadow-brand-600/25 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/50 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-50 active:scale-[0.98] disabled:opacity-40']) }}>
    {{ $slot }}
</button>
