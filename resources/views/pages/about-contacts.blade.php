{{-- О компании и контакты. --}}
@extends('layouts.app')

@section('title', 'О нас и Контакты')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-8">
        <h1 class="text-4xl font-bold mb-2">О нас и Контакты</h1>
        <p class="text-gray-600">Узнайте больше о нашем агентстве и свяжитесь с нами</p>
    </div>

    <!-- О нас -->
    <div class="card p-8 mb-8">
        <h2 class="text-2xl font-bold mb-4">О нас</h2>
        
        <div class="mb-6">
            <h3 class="text-xl font-bold mb-3">Наша миссия</h3>
            <p class="text-gray-700 leading-relaxed mb-4">
                Мы — профессиональное агентство недвижимости, которое помогает людям найти свой идеальный дом. 
                Наша цель — сделать процесс покупки и продажи недвижимости максимально простым, прозрачным и комфортным для всех наших клиентов.
            </p>
            <p class="text-gray-700 leading-relaxed">
                Мы работаем с 2010 года и за это время помогли тысячам семей обрести свой дом. 
                Наша команда состоит из опытных риэлторов, которые знают рынок недвижимости изнутри и готовы помочь вам на каждом этапе сделки.
            </p>
        </div>

        <div class="mb-6">
            <h3 class="text-xl font-bold mb-3">Наши преимущества</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="text-lg font-bold mb-2">Опыт и профессионализм</h4>
                    <p class="text-gray-700">Более 10 лет на рынке недвижимости. Наши специалисты имеют многолетний опыт работы.</p>
                </div>
                <div>
                    <h4 class="text-lg font-bold mb-2">Прозрачность сделок</h4>
                    <p class="text-gray-700">Честность и открытость — наши главные принципы. Мы всегда действуем в интересах клиента.</p>
                </div>
                <div>
                    <h4 class="text-lg font-bold mb-2">Широкий выбор</h4>
                    <p class="text-gray-700">Большая база объектов недвижимости различных типов и ценовых категорий.</p>
                </div>
                <div>
                    <h4 class="text-lg font-bold mb-2">Индивидуальный подход</h4>
                    <p class="text-gray-700">Каждый клиент уникален, и мы подбираем решение именно для ваших потребностей.</p>
                </div>
            </div>
        </div>

        <div>
            <h3 class="text-xl font-bold mb-3">Наша команда</h3>
            <p class="text-gray-700 leading-relaxed mb-4">
                Наша команда состоит из квалифицированных специалистов, которые постоянно повышают свой профессиональный уровень. 
                Мы регулярно проходим обучение и следим за изменениями на рынке недвижимости.
            </p>
            <p class="text-gray-700 leading-relaxed">
                Каждый наш сотрудник — это не просто риэлтор, а ваш персональный консультант, который поможет вам найти идеальное решение 
                и сопроводит вас на всех этапах сделки от поиска объекта до получения ключей.
            </p>
        </div>
    </div>

    <!-- Контакты -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
        <div class="card p-8">
            <h2 class="text-2xl font-bold mb-6">Контактная информация</h2>
            <div class="space-y-4">
                <div>
                    <h3 class="font-bold mb-2">Адрес офиса</h3>
                    <p class="text-gray-700">г. Иркутск, ул. Ленина, д. 5а</p>
                </div>
                <div>
                    <h3 class="font-bold mb-2">Телефон</h3>
                    <p class="text-gray-700">
                        <a href="tel:+79991234567" class="hover:underline">+7 (999) 123-45-67</a>
                    </p>
                </div>
                <div>
                    <h3 class="font-bold mb-2">Email</h3>
                    <p class="text-gray-700">
                        <a href="mailto:info@mail.ru" class="hover:underline">info@mail.ru</a>
                    </p>
                </div>
                <div>
                    <h3 class="font-bold mb-2">Режим работы</h3>
                    <p class="text-gray-700">Пн-Пт: 10:00 - 20:00</p>
                    <p class="text-gray-700">Сб-Вс: 10:00 - 17:00</p>
                </div>
            </div>
        </div>

        <div class="card p-8">
            <h2 class="text-2xl font-bold mb-6">Отделы</h2>
            <div class="space-y-4">
                <div>
                    <h3 class="font-bold mb-2">Отдел продаж</h3>
                    <p class="text-gray-700">sales@mail.ru</p>
                    <p class="text-gray-700">+7 (999) 123-45-68</p>
                </div>
                <div>
                    <h3 class="font-bold mb-2">Отдел аренды</h3>
                    <p class="text-gray-700">rent@mail.ru</p>
                    <p class="text-gray-700">+7 (999) 123-45-69</p>
                </div>
                <div>
                    <h3 class="font-bold mb-2">Юридический отдел</h3>
                    <p class="text-gray-700">legal@mail.ru</p>
                    <p class="text-gray-700">+7 (999) 123-45-70</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card p-8">
        <h2 class="text-2xl font-bold mb-6">Как нас найти</h2>
        @php
            $officeAddress = 'Иркутск, улица Ленина, 5А, Россия';
            // Фиксированная точка офиса для стабильного красного маркера.
            // При необходимости подправьте координаты вручную под вход в офис.
            $officeLon = '104.280400';
            $officeLat = '52.290300';
            $officeCenter = $officeLon . ',' . $officeLat;
            $officePoint = $officeCenter . ',pm2rdm';
            $officeMapUrl = 'https://yandex.ru/map-widget/v1/?ll=' . rawurlencode($officeCenter)
                . '&z=17&l=map&pt=' . rawurlencode($officePoint);
        @endphp
        <div class="rounded-lg overflow-hidden border border-gray-200">
            <iframe
                src="{{ $officeMapUrl }}"
                width="100%"
                height="320"
                frameborder="0"
                allowfullscreen="true"
                style="border:0;"
            ></iframe>
        </div>
        <p class="mt-4 text-gray-700">
            Наш офис расположен по адресу: г. Иркутск, ул. Ленина, д. 5а.
            Встроенная карта показывает точку офиса.
        </p>
        <p class="text-gray-700">
            Мы находимся в центре города, в 10 минутах ходьбы от скульптуры «Бабр». 
            Для посетителей доступна бесплатная парковка.
        </p>
    </div>
</div>
@endsection






