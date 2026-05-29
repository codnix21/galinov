{{-- Создание пользователя администратором. --}}
@extends('layouts.app')

@section('title', 'Создать пользователя')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-8">
        <h1 class="text-4xl font-bold mb-2">Создать пользователя</h1>
        <p class="text-gray-600">Заполните форму для создания нового пользователя</p>
    </div>

    <form method="POST" action="{{ route('admin.users.store') }}" class="card p-8">
        @csrf

        <div class="mb-6">
            <label for="familia" class="form-label">Фамилия *</label>
            <input type="text" id="familia" name="familia" value="{{ old('familia') }}" required class="form-input">
            @error('familia')
                <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label for="imya" class="form-label">Имя *</label>
            <input type="text" id="imya" name="imya" value="{{ old('imya') }}" required class="form-input">
            @error('imya')
                <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label for="otchestvo" class="form-label">Отчество</label>
            <input type="text" id="otchestvo" name="otchestvo" value="{{ old('otchestvo') }}" class="form-input">
            @error('otchestvo')
                <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label for="email_polzovatela" class="form-label">Email *</label>
            <input type="email" id="email_polzovatela" name="email_polzovatela" value="{{ old('email_polzovatela') }}" required class="form-input">
            @error('email_polzovatela')
                <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label for="telefon" class="form-label">Телефон</label>
            <input type="tel" id="telefon" name="telefon" value="{{ old('telefon') }}" placeholder="+7 (999) 123-45-67" class="form-input">
            @error('telefon')
                <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label for="rol" class="form-label">Роль *</label>
            <select id="rol" name="rol" required class="form-input">
                <option value="client" {{ old('rol') == 'client' ? 'selected' : '' }}>Клиент</option>
                <option value="realtor" {{ old('rol') == 'realtor' ? 'selected' : '' }}>Риэлтор</option>
                <option value="admin" {{ old('rol') == 'admin' ? 'selected' : '' }}>Администратор</option>
            </select>
            @error('rol')
                <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label for="parol" class="form-label">Пароль *</label>
            <input type="password" id="parol" name="parol" required class="form-input">
            @error('parol')
                <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-8">
            <label for="parol_confirmation" class="form-label">Подтвердите пароль *</label>
            <input type="password" id="parol_confirmation" name="parol_confirmation" required class="form-input">
        </div>

        <div class="divider pt-6 flex items-center justify-end gap-4">
            <a href="{{ route('admin.users') }}" class="btn">
                Отмена
            </a>
            <button type="submit" class="btn-primary">
                Создать пользователя
            </button>
        </div>
    </form>
</div>
@endsection

