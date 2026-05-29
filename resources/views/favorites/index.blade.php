{{-- Избранные объявления пользователя. --}}
@extends('layouts.app')

@section('title', 'Избранное')

@section('content')
<div class="mb-8">
    <div class="flex items-center justify-between mb-4 flex-wrap gap-4">
        <div>
            <h1 class="text-4xl font-bold mb-2">Избранное</h1>
            <p class="text-gray-600">Ваши сохраненные объявления: {{ $favorites->total() }}</p>
        </div>
        @if(Auth::user()->isRealtor() || Auth::user()->isAdmin())
            @if($favorites->total() > 0)
                <a href="{{ route('realtor.collections.create') }}" class="btn-primary">Создать подборку для клиента</a>
            @endif
        @endif
    </div>
</div>

@if($favorites->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($favorites as $favorite)
            @php
                $property = $favorite->property;
            @endphp
            <div class="card p-6 group relative">
                <!-- Кнопка удаления из избранного -->
                <div class="absolute top-4 right-4 z-10">
                    <form action="{{ route('favorites.destroy', $property) }}" method="POST" class="favorite-form">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-yellow-500 hover:text-yellow-600 text-2xl" title="Удалить из избранного">
                            ★
                        </button>
                    </form>
                </div>
                
                <div class="cursor-pointer" onclick="window.location='{{ route('properties.show', $property) }}'">
                    <!-- Блок для фотографий -->
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
                    <h3 class="text-xl font-bold mb-3 group-hover:underline">
                        {{ $property->nazvanie }}
                    </h3>
                    <p class="text-gray-600 text-sm mb-4 line-clamp-2 min-h-[2.5rem]">
                        {{ $property->opisanie }}
                    </p>
                    <div class="space-y-2 mb-4 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Город:</span>
                            <span class="font-medium">{{ $property->gorod ?? 'Не указан' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Адрес:</span>
                            <span class="font-medium">{{ Str::limit($property->adres_ulitsy ?? '', 25) }}</span>
                        </div>
                        @if($property->user)
                        <div class="flex items-center gap-2 pt-2 border-t border-gray-200">
                            @if($property->user->avatar_polzovatela)
                                <img src="{{ $property->user->avatar_url }}" alt="{{ trim($property->user->familia . ' ' . $property->user->imya . ' ' . $property->user->otchestvo) }}" class="w-8 h-8 rounded-full object-cover">
                            @else
                                <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 font-bold text-xs">
                                    {{ mb_substr($property->user->imya ?? '', 0, 1) }}
                                </div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-xs truncate">{{ trim($property->user->familia . ' ' . $property->user->imya . ' ' . $property->user->otchestvo) }}</div>
                                @if($property->user->telefon)
                                    <div class="text-xs text-gray-600">{{ $property->user->telefon }}</div>
                                @endif
                            </div>
                        </div>
                        @endif
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
                        @if($property->etazh)
                            <div class="flex justify-between">
                                <span class="text-gray-600">Этаж:</span>
                                <span class="font-medium">{{ $property->etazh }}{{ $property->vsego_etazhey ? '/' . $property->vsego_etazhey : '' }}</span>
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
        @endforeach
    </div>

    <!-- Пагинация -->
    <div class="mt-12">
        {{ $favorites->links() }}
    </div>
@else
    <div class="card p-12 text-center">
        <p class="text-xl text-gray-600 mb-4">У вас пока нет избранных объявлений</p>
        <p class="text-sm text-gray-500 mb-6">Добавляйте объявления в избранное, нажимая на звездочку ☆</p>
        <a href="{{ route('properties.index') }}" class="btn-primary inline-block">
            Перейти к объявлениям
        </a>
    </div>
@endif

<script>
// Обработка форм избранного без перезагрузки страницы
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('.favorite-form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const url = this.action;
            const method = formData.get('_method') || 'POST';

            fetch(url, {
                method: method === 'DELETE' ? 'DELETE' : 'POST',
                headers: {
                    'X-CSRF-TOKEN': formData.get('_token'),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Перезагружаем страницу для обновления списка
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // В случае ошибки перезагружаем страницу
                window.location.reload();
            });
        });
    });
});
</script>
@endsection

