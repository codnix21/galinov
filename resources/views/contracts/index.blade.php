{{-- Список договоров пользователя. --}}
@extends('layouts.app')

@section('title', 'Мои договоры')

@section('content')
<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-4xl font-bold mb-2">
                @if(Auth::user()->isRealtor() || Auth::user()->isAdmin())
                    Все договоры
                @else
                    Мои договоры
                @endif
            </h1>
            <p class="text-gray-600">
                @if(Auth::user()->isRealtor() || Auth::user()->isAdmin())
                    Полный список договоров в системе
                @else
                    Договоры купли-продажи и аренды
                @endif
            </p>
            @if(Auth::user()->isClient())
                <p class="text-sm text-gray-500 mt-1">
                    Новая сделка — через «Купить онлайн» или «Экспресс-сделка» на странице объявления.
                    Откройте договор в списке ниже — там же бланк «Договор найма» для печати (арендодатель и арендатор видят один и тот же документ).
                </p>
            @else
                <p class="text-sm text-gray-500 mt-1">Для аренды: на странице договора — «Открыть бланк для печати», после подписания на встрече — скан в архив.</p>
            @endif
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('contracts.pending') }}" class="btn">
                На подтверждение
            </a>
            @if(Auth::user()->isRealtor() || Auth::user()->isAdmin())
                <a href="{{ route('contracts.create') }}" class="btn-primary">
                    + Создать договор
                </a>
            @else
                <a href="{{ route('properties.index') }}" class="btn-primary">
                    Каталог объявлений
                </a>
            @endif
        </div>
    </div>
</div>

<form method="GET" action="{{ route('contracts.index') }}" class="card mb-6 flex flex-wrap items-end gap-4 p-4">
    <div class="min-w-[200px] flex-1">
        <label for="contracts-q" class="form-label">Поиск</label>
        <input type="search" id="contracts-q" name="q" value="{{ $search ?? '' }}" class="form-input"
               placeholder="№ договора, объект, ФИО, email…">
    </div>
    <div class="w-full sm:w-48">
        <label for="contracts-sort" class="form-label">Сортировка</label>
        <select id="contracts-sort" name="sort" class="form-input">
            <option value="newest" @selected(($sort ?? 'newest') === 'newest')>По дате создания</option>
            <option value="date_start" @selected(($sort ?? '') === 'date_start')>По дате начала</option>
            <option value="price" @selected(($sort ?? '') === 'price')>По сумме</option>
            <option value="id" @selected(($sort ?? '') === 'id')>По номеру</option>
        </select>
    </div>
    <div class="w-full sm:w-36">
        <label for="contracts-dir" class="form-label">Порядок</label>
        <select id="contracts-dir" name="dir" class="form-input">
            <option value="desc" @selected(($dir ?? 'desc') === 'desc')>По убыванию</option>
            <option value="asc" @selected(($dir ?? '') === 'asc')>По возрастанию</option>
        </select>
    </div>
    <button type="submit" class="btn-primary">Применить</button>
    @if(($search ?? '') !== '' || ($sort ?? 'newest') !== 'newest' || ($dir ?? 'desc') !== 'desc')
        <a href="{{ route('contracts.index') }}" class="btn">Сбросить</a>
    @endif
</form>

@if($contracts->count() > 0)
    <div class="space-y-4">
        @foreach($contracts as $contract)
            <div class="card p-6">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="mb-3 flex items-center gap-2 flex-wrap">
                            <span class="badge {{ $contract->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : ($contract->status === 'active' ? 'bg-green-100 text-green-800' : ($contract->status === 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800')) }}">
                                {{ $contract->status_name }}
                            </span>
                            <span class="badge">{{ $contract->type_name }}</span>
                            @if($contract->skan_dogovora)
                                <span class="badge bg-green-100 text-green-800">Скан подписан</span>
                            @endif
                        </div>
                        <h3 class="text-xl font-bold mb-2">
                            <a href="{{ route('contracts.show', $contract) }}" class="hover:underline">
                                Договор №{{ $contract->id }}
                            </a>
                        </h3>
                        <p class="text-gray-600 mb-2">
                            <strong>Объект:</strong> 
                            <a href="{{ route('properties.show', $contract->property) }}" class="text-blue-600 hover:underline">
                                {{ $contract->property->nazvanie }}
                            </a>
                        </p>
                        <div class="flex items-center gap-6 text-sm text-gray-600 mb-2">
                            <span><strong>{{ ($contract->tip ?? $contract->type) === 'rent' ? 'Платёж (мес.)' : 'Цена' }}:</strong> <span class="font-medium text-lg text-gray-900">{{ number_format($contract->tsena, 0, ',', ' ') }} ₽</span></span>
                            <span><strong>Дата начала:</strong> <span class="font-medium">{{ $contract->data_nachala->format('d.m.Y') }}</span></span>
                            @if(($contract->tip ?? $contract->type) === 'rent' && $contract->data_okonchaniya)
                                <span><strong>До:</strong> <span class="font-medium">{{ $contract->data_okonchaniya->format('d.m.Y') }}</span></span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-600">
                            <strong>Владелец:</strong>
                            {{ $contract->owner ? trim($contract->owner->familia . ' ' . $contract->owner->imya) : '—' }}
                        </p>
                        <p class="text-sm text-gray-600">
                            <strong>Покупатель:</strong>
                            {{ $contract->buyer ? trim($contract->buyer->familia . ' ' . $contract->buyer->imya) : ($contract->client ? trim($contract->client->familia . ' ' . $contract->client->imya) : '—') }}
                        </p>
                        <p class="text-sm text-gray-600">
                            <strong>Риэлтор:</strong> {{ trim($contract->realtor->familia . ' ' . $contract->realtor->imya . ' ' . $contract->realtor->otchestvo) }}
                        </p>
                        @if($contract->primechaniya)
                            <p class="text-sm text-gray-600 mt-2">
                                <strong>Примечания:</strong> {{ Str::limit($contract->primechaniya, 100) }}
                            </p>
                        @endif
                    </div>
                    <div class="flex items-center gap-3 ml-6">
                        <a href="{{ route('contracts.pdf', $contract) }}" class="btn" target="_blank" rel="noopener" title="Скачать PDF">
                            PDF
                        </a>
                        @if($contract->skan_dogovora)
                            <a href="{{ $contract->skan_dogovora_url }}" class="btn" target="_blank" rel="noopener" title="Скан с подписями">
                                Скан
                            </a>
                        @endif
                        <a href="{{ route('contracts.show', $contract) }}" class="btn">
                            Просмотр
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-8">
        {{ $contracts->links() }}
    </div>
@else
    <div class="card p-12 text-center">
        <p class="text-xl text-gray-600 mb-4">У вас пока нет договоров</p>
        @if(Auth::user()->isRealtor() || Auth::user()->isAdmin())
            <a href="{{ route('contracts.create') }}" class="btn-primary inline-block">
                Создать первый договор
            </a>
        @else
            <p class="text-sm text-gray-500 mb-4">Оформите сделку на карточке объявления — «Купить онлайн» или «Экспресс-сделка».</p>
            <a href="{{ route('properties.index') }}" class="btn-primary inline-block">
                Перейти в каталог
            </a>
        @endif
    </div>
@endif
@endsection


