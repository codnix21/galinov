@php
    /** @var \App\Models\Contract $contract */
    /** @var array $ecpStatuses */
    /** @var bool $canSignEcp */
    /** @var bool $ecpFullySigned */
    $viewerPartyRole = $viewerPartyRole ?? null;
@endphp
<div class="card p-6 mb-6 border-2 border-indigo-200 bg-indigo-50/40">
    <h3 class="text-xl font-bold mb-2">Электронная подпись (УКЭП)</h3>

    @if($ecpFullySigned)
        <p class="text-sm text-green-800 font-medium mb-4">
            ✓ Договор подписан всеми сторонами в электронном виде. Скачайте PDF — в нём отметки всех собственников, покупателя и риэлтора.
        </p>
    @else
        <p class="text-sm text-gray-600 mb-4">
            Каждый собственник (продавец) и риэлтор подписывают автоматически при оформлении сделки после оплаты.
            @if($canSignEcp)
                Вам нужно нажать кнопку ниже — без вашей подписи сделка не завершена в системе.
            @else
                Ожидается подпись покупателя.
            @endif
        </p>
    @endif

    <ul class="space-y-3 mb-4">
        @foreach($ecpStatuses as $st)
            <li class="flex flex-wrap items-start gap-2 text-sm p-3 rounded-xl {{ $st['signed'] ? 'bg-green-50 border border-green-200' : 'bg-white border border-amber-200' }}">
                <span class="{{ $st['signed'] ? 'text-green-700' : 'text-amber-700' }} font-bold text-lg">{{ $st['signed'] ? '✓' : '○' }}</span>
                <div class="flex-1 min-w-0">
                    <strong>{{ $st['label'] }}</strong>
                    @if($st['signed'])
                        <p class="text-gray-600">{{ $st['fio'] }}</p>
                        <p class="text-xs text-gray-500">№ {{ $st['nomera'] }} · {{ $st['at']?->format('d.m.Y H:i') }}
                            @if($st['auto']) · <span class="text-brand-700">автоподпись агентства</span>@endif
                        </p>
                    @else
                        <p class="text-amber-700">Ожидает подписи</p>
                    @endif
                </div>
            </li>
        @endforeach
    </ul>

    @if($canSignEcp)
        <form method="POST" action="{{ route('contracts.sign-ecp', $contract) }}" class="p-4 bg-white rounded-xl border border-indigo-300 mb-4">
            @csrf
            <p class="text-sm font-medium mb-3">Вы — покупатель. Подпишите договор усиленной электронной подписью.</p>
            <label class="flex items-start gap-2 text-sm mb-4">
                <input type="checkbox" name="accept_ecp" value="1" required class="mt-1">
                <span>Ознакомлен(а) с текстом договора и согласен(на) на подписание УКЭП.</span>
            </label>
            <button type="submit" class="btn-primary w-full sm:w-auto text-base py-3">
                Подписать договор (ЭЦП)
            </button>
        </form>
    @elseif($viewerPartyRole === 'buyer' && $contract->ecp_podpis_pokupatel_at && !$ecpFullySigned)
        <p class="text-sm text-green-800 mb-4">✓ Ваша подпись сохранена. Ожидаем остальных сторон.</p>
    @endif

    @if($ecpFullySigned || $contract->ecp_podpis_vladelets_at)
        <a href="{{ route('contracts.pdf', $contract) }}" target="_blank" rel="noopener" class="btn-primary inline-flex">
            {{ $ecpFullySigned ? 'Скачать договор (PDF с подписями УКЭП)' : 'Скачать PDF для ознакомления' }}
        </a>
    @endif
</div>
