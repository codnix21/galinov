@extends('layouts.app')

@section('title', 'Задачи')

@section('content')
@include('partials.realtor-nav')

<div class="mb-6 flex flex-wrap justify-between gap-4">
    <h1 class="text-3xl font-bold">Задачи</h1>
    <div class="flex gap-2">
        <a href="{{ route('realtor.tasks.index', ['filter' => 'open']) }}" class="btn {{ request('filter', 'open') === 'open' ? 'btn-primary' : '' }}">Открытые</a>
        <a href="{{ route('realtor.tasks.index', ['filter' => 'done']) }}" class="btn {{ request('filter') === 'done' ? 'btn-primary' : '' }}">Выполненные</a>
        <a href="{{ route('realtor.tasks.index') }}" class="btn">Все</a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <div class="lg:col-span-2 space-y-3">
        @forelse($tasks as $task)
            <div class="card p-4 flex flex-wrap justify-between gap-3 {{ $task->isDone() ? 'opacity-60' : '' }}">
                <div>
                    <div class="font-bold">{{ $task->nazvanie }}</div>
                    <div class="text-sm text-gray-600">
                        {{ $taskTypes[$task->tip] ?? $task->tip }}
                        @if($task->client) · {{ trim($task->client->familia.' '.$task->client->imya) }} @endif
                        @if($task->srok_do) · до {{ $task->srok_do->format('d.m.Y H:i') }} @endif
                    </div>
                    @if($task->opisanie)<p class="text-sm mt-1">{{ $task->opisanie }}</p>@endif
                </div>
                @if(!$task->isDone())
                    <form method="POST" action="{{ route('realtor.tasks.complete', $task) }}">
                        @csrf
                        <button type="submit" class="btn text-sm">Выполнено</button>
                    </form>
                @endif
            </div>
        @empty
            <div class="card p-8 text-center text-gray-500">Задач нет</div>
        @endforelse
        {{ $tasks->links() }}
    </div>

    <div class="card p-6 h-fit">
        <h2 class="font-bold mb-4">Новая задача</h2>
        <form method="POST" action="{{ route('realtor.tasks.store') }}" class="space-y-3">
            @csrf
            <input type="text" name="nazvanie" class="form-input" placeholder="Название" required>
            <select name="tip" class="form-input">
                @foreach($taskTypes as $k => $label)
                    <option value="{{ $k }}">{{ $label }}</option>
                @endforeach
            </select>
            <select name="klient_id" class="form-input">
                <option value="">Без клиента</option>
                @foreach($clientOptions as $rc)
                    <option value="{{ $rc->klient_id }}">{{ trim($rc->client->familia.' '.$rc->client->imya) }}</option>
                @endforeach
            </select>
            <input type="datetime-local" name="srok_do" class="form-input">
            <textarea name="opisanie" class="form-input" rows="3" placeholder="Описание"></textarea>
            <button type="submit" class="btn-primary w-full">Добавить</button>
        </form>
    </div>
</div>
@endsection
