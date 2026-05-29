{{-- Редактирование пользователя. --}}
@extends('layouts.app')

@section('title', 'Редактировать пользователя')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-8">
        <h1 class="text-4xl font-bold mb-2">Редактировать пользователя</h1>
        <p class="text-gray-600">Внесите изменения в данные пользователя</p>
    </div>

    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="card p-8">
        @csrf
        @method('PUT')

        <div class="mb-6">
            <label for="familia" class="form-label">Фамилия *</label>
            <input type="text" id="familia" name="familia" value="{{ old('familia', $user->familia ?? explode(' ', $user->name)[0] ?? '') }}" required class="form-input">
            @error('familia')
                <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label for="imya" class="form-label">Имя *</label>
            <input type="text" id="imya" name="imya" value="{{ old('imya', $user->imya ?? explode(' ', $user->name)[1] ?? '') }}" required class="form-input">
            @error('imya')
                <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label for="otchestvo" class="form-label">Отчество</label>
            <input type="text" id="otchestvo" name="otchestvo" value="{{ old('otchestvo', $user->otchestvo ?? explode(' ', $user->name)[2] ?? '') }}" class="form-input">
            @error('otchestvo')
                <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label for="email_polzovatela" class="form-label">Email *</label>
            <input type="email" id="email_polzovatela" name="email_polzovatela" value="{{ old('email_polzovatela', $user->email_polzovatela ?? $user->email) }}" required class="form-input">
            @error('email_polzovatela')
                <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label for="telefon" class="form-label">Телефон</label>
            <input type="tel" id="telefon" name="telefon" value="{{ old('telefon', $user->telefon ?? $user->phone) }}" placeholder="+7 (999) 123-45-67" class="form-input">
            @error('telefon')
                <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label for="rol" class="form-label">Роль *</label>
            @if((int) Auth::user()->getKey() === (int) $user->getKey())
                <select id="rol" name="rol" required class="form-input" disabled>
                    <option value="{{ $user->rol ?? $user->role }}" selected>
                        @if(($user->rol ?? $user->role) === 'admin') Администратор
                        @elseif(($user->rol ?? $user->role) === 'realtor') Риэлтор
                        @elseif(($user->rol ?? $user->role) === 'client') Клиент
                        @else Гость
                        @endif
                    </option>
                </select>
                <input type="hidden" name="rol" value="{{ $user->rol ?? $user->role }}">
                <p class="mt-2 text-sm text-gray-600">Вы не можете изменить свою роль</p>
            @else
                <select id="rol" name="rol" required class="form-input">
                    <option value="client" {{ old('rol', $user->rol ?? $user->role) == 'client' ? 'selected' : '' }}>Клиент</option>
                    <option value="realtor" {{ old('rol', $user->rol ?? $user->role) == 'realtor' ? 'selected' : '' }}>Риэлтор</option>
                    <option value="admin" {{ old('rol', $user->rol ?? $user->role) == 'admin' ? 'selected' : '' }}>Администратор</option>
                </select>
            @endif
            @error('rol')
                <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label for="parol" class="form-label">Новый пароль (оставьте пустым, чтобы не менять)</label>
            <input type="password" id="parol" name="parol" class="form-input">
            @error('parol')
                <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-8">
            <label for="parol_confirmation" class="form-label">Подтвердите новый пароль</label>
            <input type="password" id="parol_confirmation" name="parol_confirmation" class="form-input">
        </div>

        <div class="divider pt-6 flex items-center justify-end gap-4">
            <a href="{{ route('admin.users') }}" class="btn">
                Отмена
            </a>
            <button type="submit" class="btn-primary">
                Сохранить изменения
            </button>
        </div>
    </form>
</div>
@endsection

