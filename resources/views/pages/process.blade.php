@extends('layouts.app')

@section('title', 'Процессы агентства (Lean)')

@section('content')
<div class="max-w-5xl mx-auto">
    <h1 class="text-3xl sm:text-4xl font-bold mb-2">Предметная область агентства</h1>
    <p class="text-gray-600 mb-8">Полная карта функций и поток ценности по методам бережливого производства (Lean).</p>

    <section class="card p-6 mb-8">
        <h2 class="text-xl font-bold mb-4">Поток ценности</h2>
        <div class="grid grid-cols-1 sm:grid-cols-5 gap-3">
            @foreach($valueStream as $stage)
                <div class="text-center p-4 rounded-xl bg-slate-50 border border-slate-200">
                    <div class="text-2xl font-bold text-brand-700">{{ $stage['step'] }}</div>
                    <div class="font-semibold text-sm mt-1">{{ $stage['title'] }}</div>
                    <div class="text-xs text-gray-600 mt-1">{{ $stage['desc'] }}</div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="card p-6 mb-8">
        <h2 class="text-xl font-bold mb-4">Принципы Lean в системе</h2>
        <ul class="space-y-3">
            @foreach($principles as $p)
                <li><strong>{{ $p['name'] }}:</strong> {{ $p['text'] }}</li>
            @endforeach
        </ul>
    </section>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <section class="card p-6">
            <h3 class="font-bold text-lg mb-3">Гость / покупатель</h3>
            <ul class="text-sm space-y-1 text-gray-700">
                <li>Каталог, поиск, карта, панорама района</li>
                <li>Заявка на объект, избранное, ипотека</li>
                <li>Онлайн-покупка → договор → тестовая оплата → PDF</li>
            </ul>
        </section>
        <section class="card p-6">
            <h3 class="font-bold text-lg mb-3">Продавец (клиент)</h3>
            <ul class="text-sm space-y-1 text-gray-700">
                <li>Объявление → документы ЕГРН → модерация</li>
                <li>Экспресс-сделка, договоры, подтверждение сторон</li>
            </ul>
        </section>
        <section class="card p-6">
            <h3 class="font-bold text-lg mb-3">Риэлтор</h3>
            <ul class="text-sm space-y-1 text-gray-700">
                <li>CRM: клиенты, задачи, показы, подборки</li>
                <li>Модерация, заявки, договоры, обучение</li>
                <li>Email-уведомления, напоминания, рабочее место</li>
            </ul>
        </section>
        <section class="card p-6">
            <h3 class="font-bold text-lg mb-3">Администратор</h3>
            <ul class="text-sm space-y-1 text-gray-700">
                <li>Пользователи, все объекты и договоры</li>
                <li>Отчёты PDF/CSV/XLSX, журнал аудита</li>
            </ul>
        </section>
    </div>

    <section class="card p-6 bg-slate-50">
        <h3 class="font-bold mb-2">Автоматизация</h3>
        <p class="text-sm text-gray-700">Автозаполнение договоров, проверка документов через реестр, тестовый платёж, DaData, карты, email-уведомления, расписание напоминаний — всё связано в один поток без дублирования данных.</p>
        @auth
            <a href="{{ route('cabinet.index') }}" class="btn-primary mt-4 inline-block">В личный кабинет →</a>
        @endauth
    </section>
</div>
@endsection
