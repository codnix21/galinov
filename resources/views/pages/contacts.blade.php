{{-- Контакты. --}}
@extends('layouts.app')

@section('title', 'Контакты')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-8">
        <h1 class="text-4xl font-bold mb-2">Контакты</h1>
        <p class="text-gray-600">Свяжитесь с нами любым удобным способом</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
        <div class="card p-8">
            <h2 class="text-2xl font-bold mb-6">Контактная информация</h2>
            <div class="space-y-4">
                <div>
                    <h3 class="font-bold mb-2">Адрес офиса</h3>
                    <p class="text-gray-700">г. Москва, ул. Примерная, д. 1, офис 101</p>
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
                        <a href="mailto:info@realestate.ru" class="hover:underline">info@realestate.ru</a>
                    </p>
                </div>
                <div>
                    <h3 class="font-bold mb-2">Режим работы</h3>
                    <p class="text-gray-700">Пн-Пт: 9:00 - 20:00</p>
                    <p class="text-gray-700">Сб-Вс: 10:00 - 18:00</p>
                </div>
            </div>
        </div>

        <div class="card p-8">
            <h2 class="text-2xl font-bold mb-6">Отделы</h2>
            <div class="space-y-4">
                <div>
                    <h3 class="font-bold mb-2">Отдел продаж</h3>
                    <p class="text-gray-700">sales@realestate.ru</p>
                    <p class="text-gray-700">+7 (999) 123-45-68</p>
                </div>
                <div>
                    <h3 class="font-bold mb-2">Отдел аренды</h3>
                    <p class="text-gray-700">rent@realestate.ru</p>
                    <p class="text-gray-700">+7 (999) 123-45-69</p>
                </div>
                <div>
                    <h3 class="font-bold mb-2">Юридический отдел</h3>
                    <p class="text-gray-700">legal@realestate.ru</p>
                    <p class="text-gray-700">+7 (999) 123-45-70</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card p-8">
        <h2 class="text-2xl font-bold mb-6">Как нас найти</h2>
        <div class="bg-gray-100 h-64 rounded-lg flex items-center justify-center">
            <p class="text-gray-500">Карта будет здесь</p>
        </div>
        <p class="mt-4 text-gray-700">
            Наш офис расположен в центре города, в 5 минутах ходьбы от станции метро. 
            Для посетителей доступна бесплатная парковка.
        </p>
    </div>
</div>
@endsection













