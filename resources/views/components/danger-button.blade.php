{{-- Красная кнопка опасного действия. --}}
<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center rounded-xl border border-transparent bg-red-600 px-5 py-2.5 text-sm font-medium text-white shadow-md shadow-red-600/20 transition-all duration-200 hover:bg-red-500 hover:shadow-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500/40 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-50 active:scale-[0.98] disabled:opacity-40']) }}>
    {{ $slot }}
</button>
