@extends('layouts.app')

@section('title', 'Редактирование шаблона')

@section('content')
<div class="mb-8">
    <a href="{{ route('admin.contract-templates.index') }}" class="text-sm text-brand-700 hover:underline">← Шаблоны</a>
    <h1 class="text-3xl font-bold mt-2">{{ $template->nazvanie }}</h1>
    <p class="text-sm text-slate-600">{{ $template->kod }}</p>
</div>

<form method="POST" action="{{ route('admin.contract-templates.update', $template) }}" class="card p-6 max-w-3xl space-y-4">
    @csrf
    @method('PUT')
    <div>
        <label class="form-label">Название</label>
        <input type="text" name="nazvanie" value="{{ old('nazvanie', $template->nazvanie) }}" class="form-input" required>
    </div>
    <div>
        <label class="form-label">Введение</label>
        <textarea name="vvedenie" rows="4" class="form-input">{{ old('vvedenie', $template->vvedenie) }}</textarea>
    </div>
    <div>
        <label class="form-label">Предмет договора</label>
        <textarea name="predmet" rows="4" class="form-input">{{ old('predmet', $template->predmet) }}</textarea>
    </div>
    <div>
        <label class="form-label">Обязанности сторон</label>
        <textarea name="obyazannosti" rows="4" class="form-input">{{ old('obyazannosti', $template->obyazannosti) }}</textarea>
    </div>
    <div>
        <label class="form-label">Заключительные положения</label>
        <textarea name="zaklyuchenie" rows="4" class="form-input">{{ old('zaklyuchenie', $template->zaklyuchenie) }}</textarea>
    </div>
    <label class="flex items-center gap-2">
        <input type="checkbox" name="aktiven" value="1" {{ old('aktiven', $template->aktiven) ? 'checked' : '' }}>
        <span class="text-sm">Шаблон активен</span>
    </label>
    <button type="submit" class="btn-primary">Сохранить</button>
</form>
@endsection
