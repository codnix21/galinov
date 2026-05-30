@php
    use App\Support\PropertyInfoRequestTypes;
    $canAsk = $canAsk ?? false;
    $infoRequests = $infoRequests ?? collect();
@endphp
<div class="card p-6" id="dop-informaciya">
    <h3 class="text-xl font-bold mb-2">Дополнительная информация у риэлтора</h3>
    <p class="text-sm text-gray-600 mb-4">Уточните документы, срок владения, обременения и другие детали по объекту. История переписки сохраняется.</p>

    @if($canAsk)
        <form method="POST" action="{{ route('properties.info-requests.store', $property) }}" class="space-y-3 mb-6 border-b border-slate-200 pb-6">
            @csrf
            <div>
                <label class="form-label" for="info_tip">Тип запроса *</label>
                <select id="info_tip" name="tip" required class="form-input select-native">
                    <option value="">Выберите</option>
                    @foreach(PropertyInfoRequestTypes::labels() as $value => $label)
                        <option value="{{ $value }}" {{ old('tip') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('tip')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label" for="info_tekst">Вопрос *</label>
                <textarea id="info_tekst" name="tekst" rows="3" required class="form-input" placeholder="Например: сколько лет в собственности, есть ли ипотека…">{{ old('tekst') }}</textarea>
                @error('tekst')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="btn-primary w-full">Отправить запрос риэлтору</button>
        </form>
    @elseif(!Auth::check())
        <p class="text-sm text-gray-600 mb-4"><a href="{{ route('login') }}" class="underline">Войдите</a>, чтобы задать вопрос риэлтору.</p>
    @endif

    @if($infoRequests->isNotEmpty())
        <h4 class="font-semibold mb-3">История запросов</h4>
        <div class="space-y-4">
            @foreach($infoRequests as $req)
                <div class="border border-slate-200 rounded-lg p-4 text-sm">
                    <div class="flex flex-wrap justify-between gap-2 mb-2">
                        <span class="font-medium">{{ $req->tipLabel() }}</span>
                        <span class="badge">{{ $req->statusLabel() }}</span>
                    </div>
                    <p class="text-xs text-gray-500 mb-3">{{ $req->sozdano_at?->format('d.m.Y H:i') }}</p>
                    <div class="space-y-2">
                        @foreach($req->messages as $msg)
                            <div class="p-2 rounded {{ $msg->isStaff() ? 'bg-brand-50 border border-brand-100' : 'bg-slate-50' }}">
                                <p class="text-xs font-medium text-gray-600 mb-1">
                                    {{ $msg->isStaff() ? 'Риэлтор' : 'Вы' }}
                                    · {{ $msg->sozdano_at?->format('d.m.Y H:i') }}
                                </p>
                                <p class="whitespace-pre-line">{{ $msg->tekst }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-sm text-gray-500">Запросов пока нет.</p>
    @endif
</div>
