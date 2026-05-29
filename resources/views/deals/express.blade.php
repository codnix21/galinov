@extends('layouts.app')

@section('title', 'Экспресс-сделка')

@section('content')
<div class="max-w-2xl mx-auto">
    <a href="{{ route('properties.show', $property) }}" class="text-sm hover:underline">← Назад</a>
    <h1 class="text-3xl font-bold mt-4 mb-2">Экспресс-сделка</h1>
    <p class="text-gray-600 mb-6">Выберите покупателя или зарегистрируйте нового — договор по объекту «{{ $property->nazvanie }}» создастся автоматически.</p>

    <div class="card p-4 mb-6 text-sm bg-slate-50">
        <p><strong>Цена:</strong> {{ number_format((float)$property->tsena, 0, ',', ' ') }} ₽</p>
        <p><strong>Адрес:</strong> {{ $property->gorod }}, {{ $property->adres_ulitsy }}</p>
    </div>

    <form method="POST" action="{{ route('deals.express.store', $property) }}" class="card p-6 space-y-4" id="express-deal-form">
        @csrf

        @include('partials.fio-search-select', [
            'id' => 'buyer_id',
            'name' => 'buyer_id',
            'label' => 'Покупатель из базы',
            'placeholder' => 'ФИО или email…',
            'required' => false,
            'searchUrl' => $clientsSearchUrl,
            'items' => $clientItems,
            'selected' => old('buyer_id'),
        ])
        <p class="text-xs text-gray-500 -mt-4">Начните вводить — появятся клиенты из системы.</p>

        <details class="rounded-xl border border-slate-200 bg-slate-50/50" id="express-new-buyer">
            <summary class="cursor-pointer px-4 py-3 text-sm font-medium text-slate-800">Новый клиент (если нет в базе)</summary>
            <div class="px-4 pb-4 pt-1 space-y-4 border-t border-slate-200">
                <div>
                    <label class="form-label">Email</label>
                    <input type="email" name="buyer_email" value="{{ old('buyer_email') }}" class="form-input express-new-field">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="form-label">Фамилия</label>
                        <input type="text" name="buyer_familia" value="{{ old('buyer_familia') }}" class="form-input express-new-field">
                    </div>
                    <div>
                        <label class="form-label">Имя</label>
                        <input type="text" name="buyer_imya" value="{{ old('buyer_imya') }}" class="form-input express-new-field">
                    </div>
                    <div>
                        <label class="form-label">Отчество</label>
                        <input type="text" name="buyer_otchestvo" value="{{ old('buyer_otchestvo') }}" class="form-input express-new-field">
                    </div>
                </div>
                <p class="text-xs text-gray-500">Для нового клиента укажите email и имя.</p>
            </div>
        </details>

        <button type="submit" class="btn-primary w-full">Создать договор</button>
    </form>
</div>

@push('scripts')
<script>
(function () {
    var form = document.getElementById('express-deal-form');
    if (!form) return;
    var buyerHidden = form.querySelector('input[name="buyer_id"]');
    var newFields = form.querySelectorAll('.express-new-field');
    function sync() {
        var hasBuyer = buyerHidden && buyerHidden.value.trim() !== '';
        newFields.forEach(function (el) {
            el.disabled = hasBuyer;
            el.closest('div')?.classList.toggle('opacity-50', hasBuyer);
        });
    }
    if (buyerHidden) {
        buyerHidden.addEventListener('change', sync);
        var searchInput = form.querySelector('#buyer_id_search');
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                if (!searchInput.value.trim()) {
                    buyerHidden.value = '';
                    sync();
                }
            });
        }
    }
    sync();
})();
</script>
@endpush
@endsection
