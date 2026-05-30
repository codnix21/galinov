@if($canLeaveReview ?? false)
    <div class="card p-6 mb-6">
        <h3 class="text-lg font-bold mb-3">Отзыв о сделке</h3>
        <p class="text-sm text-gray-600 mb-4">Оцените опыт по договору №{{ $contract->id }} (1–5).</p>
        <form method="POST" action="{{ route('contracts.reviews.store', $contract) }}" class="space-y-4 max-w-md">
            @csrf
            <div>
                <label class="form-label">Оценка</label>
                <select name="ocenka" class="form-input" required>
                    @for($i = 5; $i >= 1; $i--)
                        <option value="{{ $i }}" {{ old('ocenka') == $i ? 'selected' : '' }}>{{ $i }}</option>
                    @endfor
                </select>
            </div>
            <div>
                <label class="form-label">Комментарий</label>
                <textarea name="tekst" rows="3" class="form-input" maxlength="2000" placeholder="Необязательно">{{ old('tekst') }}</textarea>
            </div>
            <button type="submit" class="btn-primary">Отправить отзыв</button>
        </form>
    </div>
@elseif($userReview ?? null)
    <div class="card p-6 mb-6 bg-slate-50">
        <h3 class="text-lg font-bold mb-2">Ваш отзыв</h3>
        <p class="text-sm">Оценка: <strong>{{ $userReview->ocenka }}/5</strong></p>
        @if($userReview->tekst)
            <p class="text-sm mt-2 text-gray-700">{{ $userReview->tekst }}</p>
        @endif
    </div>
@endif

@if(($contract->reviews ?? collect())->isNotEmpty())
    <div class="card p-6 mb-6">
        <h3 class="text-lg font-bold mb-3">Отзывы сторон</h3>
        <ul class="space-y-3 text-sm">
            @foreach($contract->reviews as $rev)
                <li class="border-b border-slate-100 pb-2 last:border-0">
                    <span class="font-medium">{{ $rev->user?->familia }} {{ $rev->user?->imya }}</span>
                    · {{ $rev->ocenka }}/5
                    @if($rev->tekst)<p class="text-gray-600 mt-1">{{ $rev->tekst }}</p>@endif
                </li>
            @endforeach
        </ul>
    </div>
@endif
