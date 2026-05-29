{{-- Подвал сайта. --}}
<footer class="mt-16 border-t border-slate-200/90 bg-white/80 backdrop-blur-sm">
    <div class="container-custom py-10">
        <div class="mb-8 grid grid-cols-1 gap-8 md:grid-cols-4">
            <div>
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Навигация</h3>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('properties.index') }}" class="text-slate-600 transition-colors hover:text-brand-700">Объявления</a></li>
                    <li><a href="{{ route('pages.mortgage-calculator') }}" class="text-slate-600 transition-colors hover:text-brand-700">Ипотечный калькулятор</a></li>
                </ul>
            </div>
            <div>
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Информация</h3>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('pages.about-contacts') }}" class="text-slate-600 transition-colors hover:text-brand-700">О нас и Контакты</a></li>
                    <li><a href="{{ route('pages.help') }}" class="text-slate-600 transition-colors hover:text-brand-700">Помощь</a></li>
                </ul>
            </div>
            <div>
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Контакты</h3>
                <ul class="space-y-2 text-sm text-slate-600">
                    <li>+7 (999) 123-45-67</li>
                    <li>info@mail.ru</li>
                    <li>г. Иркутск, ул. ленина, д. 5а</li>
                </ul>
            </div>
            <div>
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Режим работы</h3>
                <ul class="space-y-2 text-sm text-slate-600">
                    <li>Пн-Пт: 10:00 - 20:00</li>
                    <li>Сб-Вс: 10:00 - 17:00</li>
                </ul>
            </div>
        </div>
        <div class="border-t border-slate-200/90 pt-6 text-center text-sm text-slate-500">
            <p>&copy; {{ date('Y') }} Агентство недвижимости. Все права защищены.</p>
        </div>
    </div>
</footer>

