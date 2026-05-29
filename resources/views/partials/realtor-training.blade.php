{{-- Краткое обучение для риэлторов: звонки и объявления. --}}
<div class="card p-6 @if(!request()->routeIs('pages.help', 'pages.training')) mb-8 @endif border-brand-200/60 bg-gradient-to-br from-brand-50/40 to-white" id="realtor-training">
    <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Обучение для риэлторов</h2>
            <p class="text-sm text-slate-600 mt-1">Краткие рекомендации по основным темам работы в агентстве</p>
        </div>
        <span class="badge border-brand-200 bg-brand-100/80 text-brand-800">3 темы</span>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <article class="rounded-xl border border-slate-200/90 bg-white p-4 shadow-sm">
            <h3 class="font-semibold text-slate-900 mb-2 flex items-center gap-2">
                <span class="text-lg" aria-hidden="true">❄️</span>
                Холодные звонки
            </h3>
            <ul class="text-sm text-slate-600 space-y-1.5 list-disc list-inside">
                <li>Подготовьте скрипт: кто вы, зачем звоните, что предлагаете.</li>
                <li>Звоните в удобное время; фиксируйте результат в CRM или заметках.</li>
                <li>Не давите — цель первого контакта: интерес и согласие на следующий шаг.</li>
            </ul>
        </article>

        <article class="rounded-xl border border-slate-200/90 bg-white p-4 shadow-sm">
            <h3 class="font-semibold text-slate-900 mb-2 flex items-center gap-2">
                <span class="text-lg" aria-hidden="true">🔥</span>
                Горячие звонки
            </h3>
            <ul class="text-sm text-slate-600 space-y-1.5 list-disc list-inside">
                <li>Клиент уже заинтересован — уточните бюджет, сроки и критерии объекта.</li>
                <li>Предложите 2–3 подходящих объявления из каталога с конкретными адресами.</li>
                <li>Договоритесь о просмотре или повторном звонке в тот же день.</li>
            </ul>
        </article>

        <article class="rounded-xl border border-slate-200/90 bg-white p-4 shadow-sm">
            <h3 class="font-semibold text-slate-900 mb-2 flex items-center gap-2">
                <span class="text-lg" aria-hidden="true">📋</span>
                Работа с объявлениями
            </h3>
            <ul class="text-sm text-slate-600 space-y-1.5 list-disc list-inside">
                <li>Заполняйте все поля, добавляйте качественные фото — так выше доверие.</li>
                <li>Сначала черновик, затем публикация: объявление проходит модерацию.</li>
                <li>Следите за статусами: активно, продано, на модерации — обновляйте вовремя.</li>
            </ul>
        </article>
    </div>

    <p class="text-xs text-slate-500 mt-4 flex flex-wrap gap-x-3 gap-y-1">
        @unless(request()->routeIs('pages.training'))
            <a href="{{ route('pages.training') }}" class="underline hover:no-underline text-brand-700 font-medium">Полная программа обучения →</a>
        @endunless
        <span>
            Публикация и модерация —
            <a href="{{ route('pages.help') }}#help-listings" class="underline hover:no-underline text-brand-700">«Помощь»</a>
        </span>
    </p>
</div>
