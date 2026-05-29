{{-- Список черновиков пользователя. --}}
@extends('layouts.app')

@section('title', 'Мои черновики')

@section('content')
<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-4xl font-bold mb-2">Мои черновики</h1>
            <p class="text-gray-600">Список всех ваших неопубликованных объявлений</p>
        </div>
        <a href="{{ route('properties.create') }}" class="btn-primary">
            + Создать объявление
        </a>
    </div>
</div>

@if($drafts->count() > 0)
    <div class="space-y-4">
        @foreach($drafts as $draft)
            <div class="card p-6">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="mb-3 flex items-center gap-2 flex-wrap">
                            <span class="badge bg-gray-100 text-gray-800">Черновик</span>
                            <span class="badge">{{ $draft->type_name }}</span>
                        </div>
                        <h3 class="text-xl font-bold mb-2">
                            {{ $draft->nazvanie }}
                        </h3>
                        <p class="text-gray-600 mb-3">{{ $draft->gorod ?? '' }}, {{ $draft->adres_ulitsy ?? '' }}</p>
                        <div class="flex items-center gap-6 text-sm text-gray-600">
                            @if($draft->ploshchad)
                                <span>Площадь: <span class="font-medium">{{ $draft->ploshchad }} м²</span></span>
                            @endif
                            @if($draft->komnaty)
                                <span>Комнат: <span class="font-medium">{{ $draft->komnaty }}</span></span>
                            @endif
                            <span>Цена: <span class="font-medium">{{ number_format($draft->tsena, 0, ',', ' ') }} ₽</span></span>
                        </div>
                        <p class="text-sm text-gray-500 mt-2">
                            Создано: {{ $draft->sozdano_at ? $draft->sozdano_at->format('d.m.Y H:i') : ($draft->created_at ? $draft->created_at->format('d.m.Y H:i') : 'Не указана') }}
                        </p>
                    </div>
                    <div class="flex items-center gap-3 ml-6">
                        <a href="{{ route('properties.edit', $draft) }}" class="btn">
                            Редактировать
                        </a>
                        <a href="{{ route('properties.documents', $draft) }}" class="btn">
                            Документы
                        </a>
                        <form action="{{ route('properties.publish', $draft) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="btn-primary" onclick="return confirm('Отправить объявление на проверку модератору? После одобрения оно появится в каталоге.')">
                                Отправить на модерацию
                            </button>
                        </form>
                        <form action="{{ route('properties.destroy', $draft) }}" method="POST" class="inline" onsubmit="return confirm('Удалить этот черновик?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn hover:bg-red-600 hover:border-red-600 hover:text-white">
                                Удалить
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-8">
        {{ $drafts->links() }}
    </div>
@else
    <div class="card p-12 text-center">
        <p class="text-xl text-gray-600 mb-4">У вас пока нет черновиков</p>
        <a href="{{ route('properties.create') }}" class="btn-primary inline-block">
            Создать первое объявление
        </a>
    </div>
@endif
@endsection



