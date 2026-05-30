@php
    $capturedFilters = $capturedFilters ?? \App\Support\PropertyCatalogSimilar::captureFilters(request());
    $user = auth()->user();
@endphp
<div class="space-y-8">
    <div class="card p-10 text-center border-amber-200 bg-amber-50/50">
        <p class="text-xl font-semibold text-gray-800 mb-2">По заданным параметрам объекты не найдены</p>
        <p class="text-sm text-gray-600">Измените фильтры или посмотрите похожие варианты ниже. Риэлтор агентства может подобрать объекты вручную.</p>
        <a href="{{ route('properties.index') }}" class="btn mt-6 inline-block">Сбросить все фильтры</a>
    </div>

    @if(isset($similarProperties) && $similarProperties->count() > 0)
        <div>
            <h2 class="text-2xl font-bold mb-4">Похожие варианты</h2>
            <p class="text-sm text-gray-600 mb-6">Критерии поиска немного расширены (цена, комнаты, площадь).</p>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($similarProperties as $property)
                    @include('properties.partials.property-catalog-card', ['property' => $property])
                @endforeach
            </div>
        </div>
    @endif

    <div class="card p-8" id="zayavka-rieltoru">
        <h2 class="text-xl font-bold mb-2">Оставить заявку риэлтору</h2>
        <p class="text-sm text-gray-600 mb-6">Опишите пожелания — риэлтор подберёт объекты по вашим критериям и свяжется с вами.</p>

        <form method="POST" action="{{ route('properties.selection-request.store') }}" class="space-y-4 max-w-xl">
            @csrf
            <input type="hidden" name="istochnik" value="catalog">
            @foreach($capturedFilters as $key => $value)
                @if(is_scalar($value) && $value !== '')
                    <input type="hidden" name="filters[{{ $key }}]" value="{{ $value }}">
                @endif
            @endforeach

            <div>
                <label class="form-label" for="sel_imya">Ваше имя *</label>
                <input type="text" id="sel_imya" name="imya" value="{{ old('imya', $user ? trim($user->imya.' '.$user->familia) : '') }}" required class="form-input" maxlength="120">
                @error('imya')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label" for="sel_telefon">Телефон</label>
                    <input type="text" id="sel_telefon" name="telefon" value="{{ old('telefon', $user->telefon ?? '') }}" class="form-input">
                </div>
                <div>
                    <label class="form-label" for="sel_email">Email</label>
                    <input type="email" id="sel_email" name="email" value="{{ old('email', $user->email_polzovatela ?? '') }}" class="form-input">
                </div>
            </div>
            <div>
                <label class="form-label" for="sel_kommentariy">Комментарий</label>
                <textarea id="sel_kommentariy" name="kommentariy" rows="4" class="form-input" placeholder="Например: дом с гаражом, участок от 10 соток…">{{ old('kommentariy') }}</textarea>
            </div>
            @if(count($capturedFilters) > 0)
                <p class="text-xs text-gray-500">К заявке приложены текущие параметры поиска с этой страницы.</p>
            @endif
            <button type="submit" class="btn-primary">Отправить заявку риэлтору</button>
        </form>
    </div>
</div>
