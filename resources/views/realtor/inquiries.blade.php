@extends('layouts.app')

@section('title', 'Заявки по объектам')

@section('content')
@include('partials.realtor-nav')

<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <div>
        <h1 class="text-3xl font-bold mb-2">Заявки клиентов</h1>
        <p class="text-sm text-slate-600">SLA: ответ в течение {{ $slaHours }} ч. @if($overdueCount > 0)<span class="text-red-700 font-medium">Просрочено: {{ $overdueCount }}</span>@endif</p>
    </div>
    <a href="{{ route('realtor.templates.index') }}?kontekst=inquiry" class="btn text-sm">Шаблоны ответов</a>
</div>

@php
    $inquiryTemplates = \App\Models\ResponseTemplate::query()
        ->where('kontekst', 'inquiry')
        ->where(fn ($q) => $q->whereNull('rieltor_id')->orWhere('rieltor_id', auth()->id()))
        ->get();
@endphp

@forelse($inquiries as $inq)
    @php $overdue = \App\Support\InquirySla::isOverdue($inq); @endphp
    <div class="card p-5 mb-4 {{ $inq->status === 'new' ? ($overdue ? 'border-2 border-red-400 bg-red-50/30' : 'border-amber-300 border-2') : '' }}">
        <div class="flex flex-wrap justify-between gap-2">
            <div class="flex-1 min-w-[200px]">
                @if($overdue)
                    <span class="text-xs font-bold text-red-700 uppercase">Просрочено</span>
                @endif
                <p class="font-bold">
                    <a href="{{ route('properties.show', $inq->property) }}" class="hover:underline">{{ $inq->property?->nazvanie }}</a>
                </p>
                <p class="text-sm text-gray-600">{{ $inq->imya }} · {{ $inq->telefon ?? $inq->email ?? '—' }}</p>
                @if($inq->kommentariy)<p class="text-sm mt-2">{{ $inq->kommentariy }}</p>@endif
                <p class="text-xs text-gray-500 mt-1">
                    {{ $inq->sozdano_at?->format('d.m.Y H:i') }}
                    @if($inq->status === 'new' && ($deadline = \App\Support\InquirySla::deadlineAt($inq)))
                        · до {{ $deadline->format('d.m.Y H:i') }}
                    @endif
                </p>
                @if($inq->assignedRealtor)
                    <p class="text-xs text-brand-800 mt-1">Назначен: {{ trim($inq->assignedRealtor->familia.' '.$inq->assignedRealtor->imya) }}</p>
                @endif
                @include('partials.lead-assign-form', [
                    'action' => route('realtor.inquiries.assign', $inq),
                    'realtors' => $realtors,
                    'assignedId' => $inq->naznachen_rieltor_id,
                ])
            </div>
            <div class="flex flex-col gap-2 items-end">
                @if($inq->status === 'new')
                    <form method="POST" action="{{ route('realtor.inquiries.process', $inq) }}">
                        @csrf
                        <button type="submit" class="btn-primary text-sm">Отметить обработанной</button>
                    </form>
                @endif
                @if($inquiryTemplates->isNotEmpty())
                    <details class="text-sm">
                        <summary class="cursor-pointer text-brand-700">Вставить шаблон</summary>
                        <ul class="mt-2 space-y-1 text-left max-w-xs">
                            @foreach($inquiryTemplates as $tpl)
                                <li>
                                    <button type="button" class="text-left hover:underline copy-template" data-text="{{ e($tpl->tekst) }}">{{ $tpl->nazvanie }}</button>
                                </li>
                            @endforeach
                        </ul>
                    </details>
                @endif
            </div>
        </div>
    </div>
@empty
    <div class="card p-8 text-center text-gray-600">Новых заявок нет.</div>
@endforelse

<div class="mt-6">{{ $inquiries->links() }}</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.copy-template').forEach(btn => {
    btn.addEventListener('click', () => {
        navigator.clipboard.writeText(btn.dataset.text).then(() => alert('Текст скопирован в буфер обмена'));
    });
});
</script>
@endpush
