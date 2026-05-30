@extends('layouts.app')

@section('title', 'Шаблоны ответов')

@section('content')
@include('partials.realtor-nav')

<h1 class="text-3xl font-bold mb-6">Шаблоны ответов</h1>

<div class="card p-6 mb-8 max-w-2xl">
    <h2 class="font-bold mb-4">Новый шаблон</h2>
    <form method="POST" action="{{ route('realtor.templates.store') }}" class="space-y-4">
        @csrf
        <div>
            <label class="form-label">Название</label>
            <input type="text" name="nazvanie" value="{{ old('nazvanie') }}" class="form-input" required maxlength="120">
        </div>
        <div>
            <label class="form-label">Код (латиница)</label>
            <input type="text" name="kod" value="{{ old('kod') }}" class="form-input" required maxlength="64" pattern="[a-z0-9_]+">
        </div>
        <div>
            <label class="form-label">Контекст</label>
            <select name="kontekst" class="form-input" required>
                <option value="inquiry" {{ old('kontekst') === 'inquiry' ? 'selected' : '' }}>Заявки по объектам</option>
                <option value="selection" {{ old('kontekst') === 'selection' ? 'selected' : '' }}>Заявки на подбор</option>
                <option value="info" {{ old('kontekst', 'info') === 'info' ? 'selected' : '' }}>Доп. информация</option>
            </select>
        </div>
        <div>
            <label class="form-label">Текст</label>
            <textarea name="tekst" rows="5" class="form-input" required maxlength="10000">{{ old('tekst') }}</textarea>
        </div>
        <button type="submit" class="btn-primary">Сохранить</button>
    </form>
</div>

<div class="space-y-4">
    @forelse($templates as $tpl)
        <div class="card p-5">
            <div class="flex flex-wrap justify-between gap-2">
                <div>
                    <p class="font-bold">{{ $tpl->nazvanie }} <span class="text-xs text-slate-500">({{ $tpl->kod }})</span></p>
                    <p class="text-xs text-slate-500 mb-2">{{ $tpl->kontekst }}@if($tpl->rieltor_id) · личный@else · общий@endif</p>
                    <pre class="text-sm whitespace-pre-wrap text-slate-700 bg-slate-50 p-3 rounded-lg">{{ $tpl->tekst }}</pre>
                </div>
                @if(!$tpl->rieltor_id || Auth::user()->isAdmin() || (int) $tpl->rieltor_id === (int) Auth::id())
                    <form method="POST" action="{{ route('realtor.templates.destroy', $tpl) }}" onsubmit="return confirm('Удалить шаблон?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 text-sm hover:underline">Удалить</button>
                    </form>
                @endif
            </div>
        </div>
    @empty
        <p class="text-slate-600">Шаблонов пока нет.</p>
    @endforelse
</div>
@endsection
