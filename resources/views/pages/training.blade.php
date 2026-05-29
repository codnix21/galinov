{{-- Полная страница обучения для риэлторов. --}}
@extends('layouts.app')

@section('title', 'Обучение риэлторов')

@section('content')
<div class="max-w-5xl mx-auto" id="training-page">
    <div class="mb-8">
        <h1 class="text-4xl font-bold mb-2">Обучение риэлторов</h1>
        <p class="text-gray-600">Программа для новых и действующих сотрудников: звонки, объявления, работа в системе агентства.</p>
    </div>

    <nav class="card p-4 mb-8 sticky top-20 z-30 bg-white/95 backdrop-blur-sm" aria-label="Содержание программы">
        <p class="text-sm font-medium text-slate-700 mb-2">Содержание</p>
        <div class="flex flex-wrap gap-2 text-sm">
            <a href="#topic-cold" class="btn py-1.5 px-3">Холодные звонки</a>
            <a href="#topic-hot" class="btn py-1.5 px-3">Горячие звонки</a>
            <a href="#topic-listings" class="btn py-1.5 px-3">Объявления</a>
            <a href="#topic-materials" class="btn py-1.5 px-3">Материалы</a>
            <a href="#topic-system" class="btn py-1.5 px-3">В системе</a>
        </div>
    </nav>

    @include('partials.realtor-training')

    {{-- Холодные звонки --}}
    <section id="topic-cold" class="card p-8 mb-8 scroll-mt-28">
        <h2 class="text-2xl font-bold mb-2 flex items-center gap-2">
            <span aria-hidden="true">❄️</span> Холодные звонки
        </h2>
        <p class="text-gray-600 mb-6">Первый контакт с человеком, который ещё не обращался в агентство. Задача — вызвать интерес и договориться о следующем шаге, а не закрыть сделку с первого раза.</p>

        <div class="grid lg:grid-cols-2 gap-8">
            <div>
                <h3 class="text-lg font-semibold mb-3">Алгоритм звонка</h3>
                <ol class="list-decimal list-inside space-y-2 text-gray-700">
                    <li><strong>Приветствие</strong> — представьтесь, назовите агентство.</li>
                    <li><strong>Причина звонка</strong> — откуда контакт (база, рекомендация, отклик на объявление).</li>
                    <li><strong>Ценность</strong> — один конкретный повод: актуальное предложение, бесплатная консультация, оценка объекта.</li>
                    <li><strong>Вопрос</strong> — «Удобно ли сейчас говорить 2–3 минуты?»</li>
                    <li><strong>Следующий шаг</strong> — звонок в другое время, отправка подборки, встреча в офисе.</li>
                    <li><strong>Фиксация</strong> — запишите итог в заметки или CRM сразу после разговора.</li>
                </ol>
            </div>
            <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-5">
                <h3 class="text-lg font-semibold mb-3">Пример скрипта</h3>
                <blockquote class="text-sm text-slate-700 space-y-3 border-l-4 border-brand-400 pl-4 italic">
                    <p>«Добрый день! Меня зовут [имя], агентство недвижимости [название]. Вы оставляли заявку на сайте / мы помогаем с [арендой / покупкой] в [район]. Сейчас есть несколько вариантов в вашем бюджете. Удобно ли обсудить за пару минут?»</p>
                    <p>«Если сейчас неудобно — когда перезвонить? Могу прислать подборку на email или в мессенджер.»</p>
                </blockquote>
            </div>
        </div>

        <h3 class="text-lg font-semibold mt-8 mb-3">Чек-лист перед звонком</h3>
        <ul class="grid sm:grid-cols-2 gap-2 text-sm text-gray-700">
            <li class="flex items-center gap-2"><span class="text-brand-600">☐</span> Проверены имя и источник лида</li>
            <li class="flex items-center gap-2"><span class="text-brand-600">☐</span> Подготовлены 1–2 релевантных объявления из каталога</li>
            <li class="flex items-center gap-2"><span class="text-brand-600">☐</span> Выбрано удобное время (не раннее утро / поздний вечер)</li>
            <li class="flex items-center gap-2"><span class="text-brand-600">☐</span> Запланирован следующий шаг в календаре</li>
        </ul>
    </section>

    {{-- Горячие звонки --}}
    <section id="topic-hot" class="card p-8 mb-8 scroll-mt-28">
        <h2 class="text-2xl font-bold mb-2 flex items-center gap-2">
            <span aria-hidden="true">🔥</span> Горячие звонки
        </h2>
        <p class="text-gray-600 mb-6">Клиент уже проявил интерес: оставил заявку, позвонил по объявлению, пришёл с рекомендацией. Здесь важна скорость и конкретика.</p>

        <div class="grid lg:grid-cols-2 gap-8">
            <div>
                <h3 class="text-lg font-semibold mb-3">Что выяснить за 5 минут</h3>
                <ul class="list-disc list-inside space-y-2 text-gray-700">
                    <li>Цель: покупка, аренда, продажа своего объекта?</li>
                    <li>Бюджет и способ оплаты (наличные, ипотека).</li>
                    <li>Район, тип жилья, срок заселения / сделки.</li>
                    <li>Кто принимает решение (супруг, родители, поручитель).</li>
                    <li>Был ли уже просмотр похожих объектов.</li>
                </ul>
            </div>
            <div class="rounded-xl border border-amber-200/80 bg-amber-50/50 p-5">
                <h3 class="text-lg font-semibold mb-3">Правило «в тот же день»</h3>
                <p class="text-sm text-gray-700 mb-3">Горячий лид остывает за 24–48 часов. По возможности:</p>
                <ul class="text-sm text-gray-700 space-y-1.5 list-disc list-inside">
                    <li>перезвоните в течение 15–30 минут после заявки;</li>
                    <li>отправьте 2–3 ссылки на объявления из <a href="{{ route('properties.index') }}" class="underline text-brand-700">каталога</a>;</li>
                    <li>назначьте просмотр или повторный звонок с точной датой и временем.</li>
                </ul>
            </div>
        </div>

        <h3 class="text-lg font-semibold mt-8 mb-3">Пример завершения разговора</h3>
        <p class="text-gray-700 text-sm border-l-4 border-brand-400 pl-4 italic">
            «Отправлю вам три варианта: двухкомнатная на [улица], студия у метро [станция] и дом в [район]. Посмотрите сегодня вечером — завтра в 11:00 созвонимся и обсудим, какой объект посмотреть вживую. Удобно?»
        </p>
    </section>

    {{-- Объявления --}}
    <section id="topic-listings" class="card p-8 mb-8 scroll-mt-28">
        <h2 class="text-2xl font-bold mb-2 flex items-center gap-2">
            <span aria-hidden="true">📋</span> Работа с объявлениями
        </h2>
        <p class="text-gray-600 mb-6">Качественная карточка в системе — основа доверия клиента и быстрого прохождения модерации.</p>

        <div class="space-y-6">
            <div>
                <h3 class="text-lg font-semibold mb-3">Жизненный цикл объявления</h3>
                <div class="flex flex-wrap items-center gap-2 text-sm">
                    <span class="badge">Черновик</span>
                    <span class="text-slate-400">→</span>
                    <span class="badge border-amber-300 bg-amber-50 text-amber-900">На модерации</span>
                    <span class="text-slate-400">→</span>
                    <span class="badge border-brand-300 bg-brand-50 text-brand-900">Активно</span>
                    <span class="text-slate-400">→</span>
                    <span class="badge">Продано / Сдано</span>
                </div>
                <p class="text-sm text-gray-600 mt-3">
                    <a href="{{ route('properties.create') }}" class="underline text-brand-700">Создать объявление</a>,
                    черновики — <a href="{{ route('properties.drafts') }}" class="underline text-brand-700">«Мои черновики»</a>,
                    очередь проверки — <a href="{{ route('moderation.index') }}" class="underline text-brand-700">«Модерация»</a>.
                </p>
            </div>

            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-lg font-semibold mb-3">Обязательно в карточке</h3>
                    <ul class="list-disc list-inside text-gray-700 space-y-1.5 text-sm">
                        <li>Честное название без «кричащих» обещаний</li>
                        <li>Полный адрес или район (по правилам площадки)</li>
                        <li>Цена, площадь, этаж, тип операции</li>
                        <li>Описание без запрещённых слов (см. справку)</li>
                        <li>От 3–5 чётких фотографий, первое — лучший ракурс</li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-3">Частые причины отклонения</h3>
                    <ul class="list-disc list-inside text-gray-700 space-y-1.5 text-sm">
                        <li>Неполные или противоречивые данные</li>
                        <li>Размытые / чужие фото без прав</li>
                        <li>Мат и слова из словаря цензуры</li>
                        <li>Дублирование уже активного объявления</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- Материалы для скачивания --}}
    <section id="topic-materials" class="card p-8 mb-8 scroll-mt-28">
        <h2 class="text-2xl font-bold mb-4">Материалы для скачивания</h2>
        <p class="text-gray-600 mb-6">Краткие памятки в формате PDF — можно распечатать или сохранить на телефон.</p>

        <div class="grid sm:grid-cols-3 gap-4">
            <a href="{{ route('pages.training.pdf', 'cold') }}" class="group card p-5 hover:border-brand-300 transition-colors no-underline">
                <span class="text-2xl block mb-2" aria-hidden="true">📄</span>
                <span class="font-semibold text-slate-900 group-hover:text-brand-700">Холодные звонки</span>
                <span class="block text-sm text-slate-500 mt-1">PDF, 1 стр.</span>
            </a>
            <a href="{{ route('pages.training.pdf', 'hot') }}" class="group card p-5 hover:border-brand-300 transition-colors no-underline">
                <span class="text-2xl block mb-2" aria-hidden="true">📄</span>
                <span class="font-semibold text-slate-900 group-hover:text-brand-700">Горячие звонки</span>
                <span class="block text-sm text-slate-500 mt-1">PDF, 1 стр.</span>
            </a>
            <a href="{{ route('pages.training.pdf', 'listings') }}" class="group card p-5 hover:border-brand-300 transition-colors no-underline">
                <span class="text-2xl block mb-2" aria-hidden="true">📄</span>
                <span class="font-semibold text-slate-900 group-hover:text-brand-700">Работа с объявлениями</span>
                <span class="block text-sm text-slate-500 mt-1">PDF, 1 стр.</span>
            </a>
        </div>

        <div class="mt-8 rounded-xl border border-slate-200 bg-slate-50/80 p-5">
            <h3 class="font-semibold mb-2">Видео-разборы (внутренние)</h3>
            <p class="text-sm text-gray-600 mb-4">Записи встреч с наставником. Ссылки обновляются администратором — при отсутствии доступа обратитесь в офис.</p>
            <ul class="space-y-2 text-sm">
                <li class="flex items-start gap-2 text-gray-700">
                    <span class="badge shrink-0">15 мин</span>
                    <span><strong>Разбор холодного звонка</strong> — типичные ошибки и удачные формулировки (материал готовится).</span>
                </li>
                <li class="flex items-start gap-2 text-gray-700">
                    <span class="badge shrink-0">12 мин</span>
                    <span><strong>Горячий лид за 30 минут</strong> — от заявки до назначения просмотра (материал готовится).</span>
                </li>
                <li class="flex items-start gap-2 text-gray-700">
                    <span class="badge shrink-0">10 мин</span>
                    <span><strong>Объявление с первого раза на модерации</strong> — чек-лист полей и фото (материал готовится).</span>
                </li>
            </ul>
        </div>
    </section>

    {{-- Работа в системе --}}
    <section id="topic-system" class="card p-8 scroll-mt-28">
        <h2 class="text-2xl font-bold mb-4">Работа в системе агентства</h2>
        <ul class="list-disc list-inside space-y-2 text-gray-700">
            <li><a href="{{ route('cabinet.index') }}" class="underline text-brand-700">Личный кабинет</a> — ваши объявления и статистика.</li>
            <li><a href="{{ route('moderation.index') }}" class="underline text-brand-700">Модерация</a> — проверка чужих объявлений (свои одобрять нельзя).</li>
            <li><a href="{{ route('contracts.pending') }}" class="underline text-brand-700">Договоры на подтверждение</a> — модерация всех договоров, ожидающих решения риэлтора.</li>
            <li><a href="{{ route('pages.help') }}" class="underline text-brand-700">Справка</a> — правила площадки, цензура, роли.</li>
        </ul>
    </section>
</div>
@endsection
