{{-- Настройки профиля: имя, email, пароль. --}}
@extends('layouts.app')

@section('title', 'Редактирование профиля')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-8">
        <h1 class="text-4xl font-bold mb-2">Редактирование профиля</h1>
        <p class="text-gray-600">Обновите информацию о вашем профиле</p>
    </div>

    @include('profile.partials.profile-readiness')

    <div class="space-y-6">
        <!-- Информация профиля -->
        <div class="card p-8">
            @include('profile.partials.update-profile-information-form')
        </div>

        <div class="card p-8">
            @include('profile.partials.personal-data-form')
        </div>

        <!-- Смена пароля -->
        <div class="card p-8">
            @include('profile.partials.update-password-form')
        </div>

        @include('profile.partials.telegram-settings')

        <div class="card p-8">
            <h2 class="text-xl font-bold mb-2">Персональные данные (152-ФЗ)</h2>
            <p class="text-gray-600 text-sm mb-4">Выгрузка ваших данных в ZIP (профиль, документы, договоры).</p>
            <a href="{{ route('profile.export-152fz') }}" class="btn">Скачать выгрузку</a>
        </div>

        <div class="card p-8">
            <h2 class="text-xl font-bold mb-2">Документы продавца</h2>
            <p class="text-gray-600 text-sm mb-4">Паспорт и ИНН — в профиле. ЕГРН и право собственности — в карточке объявления.</p>
            <a href="{{ route('profile.documents.index') }}" class="btn">Открыть документы продавца →</a>
        </div>

        <!-- Удаление аккаунта -->
        <div class="card p-8">
            @include('profile.partials.delete-user-form')
        </div>
    </div>
</div>
@endsection
