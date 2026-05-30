@extends('layouts.app')

@section('title', 'Воронка клиентов')

@section('content')
@include('partials.realtor-nav')

<h1 class="text-3xl font-bold mb-2">Воронка CRM</h1>
<p class="text-sm text-slate-600 mb-6">Перетащите карточку в другую колонку — статус сохранится автоматически.</p>

<div id="kanban-board" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
    @foreach($columns as $col)
        <div class="card p-4 bg-slate-50 min-h-[240px]">
            <h2 class="font-bold text-sm uppercase tracking-wide text-slate-600 mb-3">{{ $col['title'] }}</h2>
            <div class="kanban-column space-y-3 min-h-[120px]" data-status="{{ $col['kod'] }}">
                @forelse($col['clients'] as $rc)
                    @php $c = $rc->client; @endphp
                    <div class="kanban-card bg-white border border-slate-200 rounded-lg p-3 text-sm cursor-grab active:cursor-grabbing shadow-sm"
                         data-client-id="{{ $rc->id }}">
                        <a href="{{ route('realtor.clients.show', $rc) }}" class="font-semibold hover:underline block">
                            {{ $c ? trim($c->familia.' '.$c->imya) : 'Клиент #'.$rc->klient_id }}
                        </a>
                        @if($c?->telefon)
                            <p class="text-xs text-slate-500 mt-1">{{ $c->telefon }}</p>
                        @endif
                    </div>
                @empty
                    <p class="text-xs text-slate-400 kanban-empty">Пусто</p>
                @endforelse
            </div>
        </div>
    @endforeach
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    const columns = document.querySelectorAll('.kanban-column');

    columns.forEach(col => {
        new Sortable(col, {
            group: 'kanban',
            animation: 150,
            ghostClass: 'opacity-50',
            draggable: '.kanban-card',
            onEnd: async (evt) => {
                const card = evt.item;
                const clientId = card.dataset.clientId;
                const newStatus = evt.to.dataset.status;
                const oldStatus = evt.from.dataset.status;
                if (!clientId || !newStatus || newStatus === oldStatus) return;

                col.querySelectorAll('.kanban-empty').forEach(el => el.remove());
                evt.from.querySelectorAll('.kanban-empty').forEach(el => el.remove());

                try {
                    const res = await fetch(`{{ url('/realtor/kanban/clients') }}/${clientId}`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token,
                        },
                        body: JSON.stringify({ status: newStatus }),
                    });
                    if (!res.ok) {
                        throw new Error('Ошибка сохранения');
                    }
                } catch (e) {
                    evt.from.insertBefore(card, evt.from.children[evt.oldIndex] || null);
                    alert('Не удалось обновить статус. Обновите страницу.');
                }
            },
        });
    });
});
</script>
@endpush
