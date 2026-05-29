{{-- Список пользователей в админке. --}}
@extends('layouts.app')

@section('title', 'Управление пользователями')

@section('content')
<div class="mb-8">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-4xl font-bold mb-2">Управление пользователями</h1>
            <p class="text-gray-600">Всего пользователей: {{ $users->total() }}</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.dashboard') }}" class="btn">
                ← Назад
            </a>
            <a href="{{ route('admin.users.create') }}" class="btn-primary">
                + Создать пользователя
            </a>
        </div>
    </div>
    
    <!-- Поиск -->
    <div class="card p-4 mb-6">
        <form method="GET" action="{{ route('admin.users') }}" class="flex gap-3">
            <input type="text" name="search" value="{{ request('search') }}" class="form-input flex-1" placeholder="Поиск по имени, email, телефону...">
            <button type="submit" class="btn-primary">Поиск</button>
            @if(request('search'))
                <a href="{{ route('admin.users') }}" class="btn">Сбросить</a>
            @endif
        </form>
    </div>
</div>

@if($users->count() > 0)
    <div class="card overflow-hidden">
        <table class="w-full">
            <thead class="border-b border-slate-200 bg-slate-50">
                <tr>
                    <th class="px-6 py-4 text-left text-sm font-bold">ID</th>
                    <th class="px-6 py-4 text-left text-sm font-bold">Имя</th>
                    <th class="px-6 py-4 text-left text-sm font-bold">Email</th>
                    <th class="px-6 py-4 text-left text-sm font-bold">Роль</th>
                    <th class="px-6 py-4 text-left text-sm font-bold">Статус</th>
                    <th class="px-6 py-4 text-left text-sm font-bold">Объявлений</th>
                    <th class="px-6 py-4 text-left text-sm font-bold">Дата регистрации</th>
                    <th class="px-6 py-4 text-left text-sm font-bold">Действия</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                    <tr class="border-b border-gray-200 hover:bg-gray-50 transition-colors {{ $user->isBlocked() ? 'bg-red-50' : '' }}">
                        <td class="px-6 py-4">{{ $user->id }}</td>
                        <td class="px-6 py-4 font-medium">{{ trim($user->familia . ' ' . $user->imya . ' ' . $user->otchestvo) }}</td>
                        <td class="px-6 py-4">{{ $user->email_polzovatela }}</td>
                        <td class="px-6 py-4">
                            <span class="badge">
                                @if($user->rol === 'admin') Админ
                                @elseif($user->rol === 'realtor') Риэлтор
                                @elseif($user->rol === 'client') Клиент
                                @else Гость
                                @endif
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            @if($user->isBlocked())
                                <span class="badge bg-red-500 text-white">Заблокирован</span>
                            @else
                                <span class="badge bg-green-500 text-white">Активен</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">{{ $user->properties_count }}</td>
                        <td class="px-6 py-4">{{ $user->sozdano_at ? $user->sozdano_at->format('d.m.Y') : ($user->created_at ? $user->created_at->format('d.m.Y') : 'Не указана') }}</td>
                        <td class="px-6 py-4">
                            @php $isCurrentUser = (int) Auth::user()->getKey() === (int) $user->getKey(); @endphp
                            <div class="flex items-center gap-2 flex-wrap">
                                <a href="{{ route('admin.users.edit', $user) }}" class="text-sm underline hover:no-underline">Редактировать</a>
                                @if(!$isCurrentUser)
                                    @if($user->isBlocked())
                                        <form method="POST" action="{{ route('admin.users.unblock', $user) }}" class="inline js-confirm-action" data-confirm="Разблокировать этого пользователя?">
                                            @csrf
                                            <button type="submit" class="text-green-600 hover:underline text-sm font-medium">Разблокировать</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.users.block', $user) }}" class="inline js-confirm-action" data-confirm="Заблокировать этого пользователя? Вход в систему будет запрещён до разблокировки.">
                                            @csrf
                                            <button type="submit" class="text-orange-600 hover:underline text-sm font-medium">Заблокировать</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('admin.users.delete', $user) }}" class="inline delete-form" data-type="пользователя" data-name="{{ trim($user->familia . ' ' . $user->imya . ' ' . $user->otchestvo) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline text-sm font-medium">Удалить</button>
                                    </form>
                                @else
                                    <span class="text-gray-500 text-sm" title="Нельзя заблокировать или удалить свою учётную запись">Блокировка и удаление недоступны для вашего аккаунта</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-8">
        {{ $users->links() }}
    </div>
@else
    <div class="card p-12 text-center">
        <p class="text-xl text-gray-600">Пользователи не найдены</p>
    </div>
@endif
@endsection


