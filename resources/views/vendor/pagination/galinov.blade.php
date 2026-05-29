@if ($paginator->total() > 0)
    <nav role="navigation" aria-label="Страницы результатов" class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-gray-600">
            @if ($paginator->firstItem())
                Показано с <span class="font-medium text-gray-900">{{ $paginator->firstItem() }}</span>
                по <span class="font-medium text-gray-900">{{ $paginator->lastItem() }}</span>
                из <span class="font-medium text-gray-900">{{ $paginator->total() }}</span> результатов
            @else
                Найдено: <span class="font-medium text-gray-900">{{ $paginator->total() }}</span>
            @endif
        </p>

        @if ($paginator->hasPages())
            <div class="flex flex-wrap items-center gap-2">
                @if ($paginator->onFirstPage())
                    <span class="btn opacity-50 cursor-not-allowed" aria-disabled="true">← Назад</span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn">← Назад</a>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="px-2 text-gray-500">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="btn-primary min-w-[2.5rem] justify-center pointer-events-none">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn min-w-[2.5rem] justify-center" aria-label="Страница {{ $page }}">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn">Вперёд →</a>
                @else
                    <span class="btn opacity-50 cursor-not-allowed" aria-disabled="true">Вперёд →</span>
                @endif
            </div>
        @endif
    </nav>
@endif
