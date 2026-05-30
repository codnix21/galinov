@extends('layouts.app')

@section('title', 'Шаблоны договоров')

@section('content')
<div class="mb-8">
    <a href="{{ route('admin.dashboard') }}" class="text-sm text-brand-700 hover:underline">← Админ-панель</a>
    <h1 class="text-3xl font-bold mt-2">Шаблоны договоров</h1>
    <p class="text-sm text-slate-600 mt-1">Тексты разделов для PDF и печатных форм.</p>
</div>

<div class="space-y-4">
    @foreach($templates as $tpl)
        <div class="card p-5 flex flex-wrap justify-between items-center gap-3">
            <div>
                <p class="font-bold">{{ $tpl->nazvanie }}</p>
                <p class="text-sm text-slate-600">{{ $tpl->kod }} · {{ $tpl->tip_dogovora === 'rent' ? 'Аренда' : 'Продажа' }}
                    @if($tpl->aktiven)<span class="text-green-700">· активен</span>@else<span class="text-red-600">· выключен</span>@endif
                </p>
            </div>
            <a href="{{ route('admin.contract-templates.edit', $tpl) }}" class="btn">Редактировать</a>
        </div>
    @endforeach
</div>
@endsection
