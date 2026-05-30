@extends('layouts.app')

@section('title', 'Состояние системы')

@section('content')
<div class="mb-8">
    <a href="{{ route('admin.dashboard') }}" class="text-sm text-brand-700 hover:underline">← Админ-панель</a>
    <h1 class="text-3xl font-bold mt-2">Состояние системы</h1>
</div>

<div class="grid gap-4 max-w-2xl">
    @foreach($checks as $check)
        <div class="card p-5 flex gap-4 items-start border-l-4
            {{ $check['status'] === 'ok' ? 'border-green-500' : ($check['status'] === 'warn' ? 'border-amber-400' : 'border-red-500') }}">
            <span class="text-lg">
                @if($check['status'] === 'ok') ✓ @elseif($check['status'] === 'warn') ! @else ✕ @endif
            </span>
            <div>
                <p class="font-semibold">{{ $check['label'] }}</p>
                <p class="text-sm text-slate-600 mt-1">{{ $check['detail'] }}</p>
            </div>
        </div>
    @endforeach
</div>
@endsection
