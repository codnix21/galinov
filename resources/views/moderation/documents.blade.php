@extends('layouts.app')

@section('title', 'Проверка документов')

@section('content')
<div class="mb-8 flex flex-wrap justify-between gap-4">
    <div>
        <h1 class="text-2xl sm:text-4xl font-bold mb-2">Проверка документов</h1>
        <p class="text-gray-600 text-sm">ЕГРН, ЕГРЮЛ, право собственности · автопроверка {{ \App\Services\DocumentVerificationService::PROVIDER }}</p>
    </div>
    <a href="{{ route('moderation.index') }}" class="btn">Модерация объявлений</a>
</div>

@forelse($documents as $doc)
    <div class="card p-5 sm:p-6 mb-4">
        <p class="font-bold">{{ $tipLabels[$doc->tip] ?? $doc->tip }}</p>
        <p class="text-sm text-gray-600">
            {{ trim(($doc->user->familia ?? '').' '.($doc->user->imya ?? '')) }}
            · {{ $doc->user->email_polzovatela ?? '' }}
        </p>
        @if($doc->property)
            <p class="text-sm mt-1">
                <a href="{{ route('properties.show', $doc->property) }}" class="underline">Объект #{{ $doc->property->id }}</a>
                — {{ $doc->property->nazvanie }}
            </p>
        @endif
        @if($doc->vneshniy_id)
            <p class="text-xs text-gray-500 mt-1">{{ app(\App\Services\DocumentVerificationService::class)->externalSummary($doc) }}</p>
        @endif
        <p class="mt-2 text-sm"><span class="badge">{{ $doc->statusLabel() }}</span></p>
        @if($doc->view_url)
            <a href="{{ $doc->view_url }}" target="_blank" rel="noopener" class="text-sm underline mt-2 inline-block">Открыть файл</a>
        @elseif(\App\Support\DocumentStorage::isJsonRegistryFile($doc->put_fajla))
            <span class="text-sm text-gray-500 mt-2 inline-block">Подтверждено по кадастровому номеру (без скана)</span>
        @endif
        @if(in_array($doc->status, ['pending', 'checking', 'rejected'], true))
            <div class="mt-4 flex flex-wrap gap-2">
                <form method="POST" action="{{ route('moderation.documents.recheck', $doc) }}" class="inline">
                    @csrf
                    <button type="submit" class="btn text-sm">Повторить автопроверку</button>
                </form>
                <form method="POST" action="{{ route('moderation.documents.verify', $doc) }}" class="inline">
                    @csrf
                    <input type="hidden" name="action" value="verify">
                    <button type="submit" class="btn-primary text-sm">Подтвердить вручную</button>
                </form>
                <form method="POST" action="{{ route('moderation.documents.verify', $doc) }}" class="inline">
                    @csrf
                    <input type="hidden" name="action" value="reject">
                    <button type="submit" class="btn text-sm">Отклонить</button>
                </form>
            </div>
        @endif
    </div>
@empty
    <div class="card p-8 text-center text-gray-600">Нет документов.</div>
@endforelse

<div class="mt-6">{{ $documents->links() }}</div>
@endsection
