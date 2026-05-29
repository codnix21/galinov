{{-- Договоры, ожидающие подтверждения сторон. --}}
@extends('layouts.app')

@section('title', 'Договоры на подтверждение')

@section('content')
@php use App\Support\ContractApproval; @endphp
<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-4xl font-bold mb-2">Договоры на подтверждение</h1>
            <p class="text-gray-600">
                @if(Auth::user()->isRealtor() || Auth::user()->isAdmin())
                    Договоры в статусе «ожидает подтверждения». Подтвердите те, где требуется ваша сторона.
                @else
                    Договоры, где вы участник сделки. Подтвердите, если от вас ещё ждут решения.
                @endif
            </p>
        </div>
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

@include('partials.help-hint', [
    'title' => 'Как работает согласование',
    'points' => [
        'Если договор создал риэлтор — подтверждают владелец и покупатель.',
        'Если договор создал клиент — подтверждают риэлтор и вторая сторона (не создатель).',
        'После подтверждения всех нужных сторон договор станет активным.',
    ],
])

@if(session('success'))
    <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800">
        {{ session('success') }}
    </div>
@endif

@if($contracts->count() > 0)
    <div class="space-y-4">
        @foreach($contracts as $contract)
            @php
                $needsMe = $contract->needs_my_approval ?? ContractApproval::userCanApprove(Auth::user(), $contract);
                $waiting = ContractApproval::pendingSummary($contract);
            @endphp
            <div class="card p-6 {{ $needsMe ? 'ring-2 ring-amber-300' : '' }}">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="mb-3 flex items-center gap-2 flex-wrap">
                            <span class="badge bg-yellow-100 text-yellow-800">Ожидает подтверждения</span>
                            @if($needsMe)
                                <span class="badge bg-amber-200 text-amber-900">Нужно ваше решение</span>
                            @endif
                            <span class="badge">{{ $contract->type_name }}</span>
                        </div>
                        <h3 class="text-xl font-bold mb-2">Договор №{{ $contract->id }}</h3>
                        <p class="text-gray-600 mb-2">
                            <strong>Объект:</strong>
                            <a href="{{ route('properties.show', $contract->property) }}" class="text-blue-600 hover:underline">
                                {{ $contract->property->nazvanie }}
                            </a>
                        </p>
                        <div class="flex items-center gap-6 text-sm text-gray-600 mb-2 flex-wrap">
                            <span><strong>{{ ($contract->tip ?? $contract->type) === 'rent' ? 'Аренда (мес.)' : 'Цена' }}:</strong> {{ number_format($contract->tsena, 0, ',', ' ') }} ₽</span>
                            <span><strong>Начало:</strong> {{ $contract->data_nachala->format('d.m.Y') }}</span>
                        </div>
                        <p class="text-sm text-gray-600 mb-1">
                            <strong>Владелец:</strong>
                            @if($contract->owner)
                                {{ trim($contract->owner->familia . ' ' . $contract->owner->imya) }}
                                @if(ContractApproval::needsOwnerApproval($contract) && !ContractApproval::isOwnerApproved($contract)) (ожидает) @endif
                            @else — @endif
                        </p>
                        <p class="text-sm text-gray-600 mb-1">
                            <strong>Покупатель:</strong>
                            @if($contract->buyer)
                                {{ trim($contract->buyer->familia . ' ' . $contract->buyer->imya) }}
                                @if(ContractApproval::needsBuyerApproval($contract) && !ContractApproval::isBuyerApproved($contract)) (ожидает) @endif
                            @else — @endif
                        </p>
                        <p class="text-sm text-gray-600 mb-2">
                            <strong>Риэлтор:</strong>
                            @if($contract->realtor)
                                {{ trim($contract->realtor->familia . ' ' . $contract->realtor->imya) }}
                                @if(ContractApproval::needsRealtorApproval($contract) && !ContractApproval::isRealtorApproved($contract)) (ожидает) @endif
                            @endif
                        </p>
                        @if($waiting !== '—')
                            <p class="text-xs text-gray-500">Ожидаем: {{ $waiting }}</p>
                        @endif
                    </div>
                    <div class="flex flex-col items-end gap-3 ml-6">
                        <a href="{{ route('contracts.show', $contract) }}" class="btn">Просмотр</a>
                        @if($needsMe)
                            <form action="{{ route('contracts.approve', $contract) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="btn-primary" onclick="return confirm('Подтвердить договор?')">Подтвердить</button>
                            </form>
                            <form action="{{ route('contracts.reject', $contract) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="btn bg-red-600 hover:bg-red-700 text-white" onclick="return confirm('Отклонить договор?')">Отклонить</button>
                            </form>
                        @endif
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
        <p class="text-xl text-gray-600 mb-4">Нет договоров, ожидающих подтверждения</p>
        @if(Auth::user()->isRealtor() || Auth::user()->isAdmin())
            <a href="{{ route('contracts.create') }}" class="btn-primary inline-block">Создать договор</a>
        @else
            <a href="{{ route('properties.index') }}" class="btn-primary inline-block">Каталог объявлений</a>
        @endif
    </div>
@endif
@endsection
