<div class="card p-6 group relative">
    @auth
        <div class="absolute top-3 right-3 z-20" onclick="event.stopPropagation()">
            @if(isset($property->is_favorite) && $property->is_favorite)
                <form action="{{ route('favorites.destroy', $property) }}" method="POST" class="favorite-form">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="favorite-btn favorite-btn--active" title="Убрать из избранного" aria-label="Убрать из избранного">★</button>
                </form>
            @else
                <form action="{{ route('favorites.store', $property) }}" method="POST" class="favorite-form">
                    @csrf
                    <button type="submit" class="favorite-btn favorite-btn--inactive" title="В избранное" aria-label="Добавить в избранное">☆</button>
                </form>
            @endif
        </div>
    @endauth

    <div class="cursor-pointer" onclick="window.location='{{ route('properties.show', $property) }}'">
        <div class="mb-4 h-48 bg-gray-100 rounded-lg overflow-hidden flex items-center justify-center">
            @if($property->images && $property->images->count() > 0)
                <img src="{{ $property->images->first()->public_url }}" alt="{{ $property->nazvanie }}" class="w-full h-full object-cover">
            @else
                <div class="text-gray-400 text-sm">Нет фотографии</div>
            @endif
        </div>

        <div class="flex items-center justify-between mb-3">
            <div class="flex gap-2 flex-wrap">
                <span class="badge">{{ $property->type_name }}</span>
                <span class="badge">{{ $property->operation_name }}</span>
            </div>
        </div>
        <h3 class="text-xl font-bold mb-3 group-hover:underline">{{ $property->nazvanie }}</h3>
        <p class="text-gray-600 text-sm mb-4 line-clamp-2 min-h-[2.5rem]">{{ $property->opisanie }}</p>
        <div class="space-y-2 mb-4 text-sm">
            <div class="flex justify-between">
                <span class="text-gray-600">Город:</span>
                <span class="font-medium">{{ $property->gorod ?? 'Не указан' }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Адрес:</span>
                <span class="font-medium">{{ Str::limit($property->adres_ulitsy ?? '', 25) }}</span>
            </div>
            @if($property->ploshchad)
                <div class="flex justify-between">
                    <span class="text-gray-600">Площадь:</span>
                    <span class="font-medium">{{ $property->ploshchad }} м²</span>
                </div>
            @endif
            @if($property->komnaty)
                <div class="flex justify-between">
                    <span class="text-gray-600">Комнат:</span>
                    <span class="font-medium">{{ $property->komnaty }}</span>
                </div>
            @endif
        </div>
        <div class="divider pt-4 mt-4">
            <div class="flex items-center justify-between">
                <span class="text-2xl font-bold">{{ number_format($property->tsena, 0, ',', ' ') }} ₽</span>
                <span class="text-xs text-gray-500">→</span>
            </div>
        </div>
    </div>
</div>
