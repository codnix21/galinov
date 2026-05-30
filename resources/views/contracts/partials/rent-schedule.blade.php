@php
    $isRent = ($contract->tip ?? $contract->type) === 'rent';
    $isActive = ($contract->status ?? '') === 'active';
@endphp
@if($isRent && $isActive)
    <div class="card p-6 mb-6">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <h3 class="text-lg font-bold">График арендных платежей</h3>
            @if(Auth::user()->isStaff() && ($contract->rentPayments ?? collect())->isEmpty())
                <form method="POST" action="{{ route('contracts.rent-schedule.generate', $contract) }}">
                    @csrf
                    <button type="submit" class="btn text-sm">Сформировать график</button>
                </form>
            @endif
        </div>
        @if(($contract->rentPayments ?? collect())->isEmpty())
            <p class="text-sm text-gray-600">Платежи ещё не сформированы. После активации договора график создаётся автоматически или по кнопке риэлтора.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b">
                            <th class="py-2 pr-4">Дата</th>
                            <th class="py-2 pr-4">Сумма</th>
                            <th class="py-2 pr-4">Статус</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($contract->rentPayments->sortBy('poryadok') as $payment)
                            <tr class="border-b border-slate-100">
                                <td class="py-2">{{ $payment->data_platezha?->format('d.m.Y') }}</td>
                                <td class="py-2">{{ number_format($payment->summa, 0, ',', ' ') }} ₽</td>
                                <td class="py-2">
                                    @if($payment->isPaid())
                                        <span class="text-green-700 font-medium">Оплачен</span>
                                        @if($payment->oplacheno_at)
                                            <span class="text-gray-500 text-xs">({{ $payment->oplacheno_at->format('d.m.Y') }})</span>
                                        @endif
                                    @else
                                        <span class="text-amber-700">Ожидает</span>
                                    @endif
                                </td>
                                <td class="py-2 text-right">
                                    @if(!$payment->isPaid())
                                        <form method="POST" action="{{ route('rent-payments.paid', $payment) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="text-brand-700 hover:underline text-xs">Отметить оплату</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endif
