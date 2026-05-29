{{-- Ожидает: $istoriyaZhurnala (Collection) — только блоки с реальными изменениями полей --}}
@php
    use App\Support\AuditJournalDisplay;
@endphp
@if(isset($istoriyaZhurnala) && $istoriyaZhurnala->isNotEmpty())
@php
    $sozdanieBezTablitsy = ['sozdano', 'dogovor_sozdan'];
@endphp
<div class="card p-8 mb-6">
    <h2 class="text-2xl font-bold mb-2">История изменений</h2>
    <p class="text-sm text-gray-600 mb-6">Показаны только изменённые поля. Статусы и типы отображаются понятными названиями.</p>
    <div class="space-y-4">
        @foreach($istoriyaZhurnala as $zapis)
            @php
                $det = is_array($zapis->detalizatsiya) ? $zapis->detalizatsiya : [];
                $izmeneniyaRaw = collect($det)->filter(function ($st) {
                    if (!isset($st['polya'])) {
                        return false;
                    }
                    $b = (string) ($st['bilo'] ?? '');
                    $s = (string) ($st['stalo'] ?? '');
                    return $b !== $s;
                })->map(fn ($st) => [
                    'polya' => $st['polya'],
                    'bilo' => $st['bilo'] ?? null,
                    'stalo' => $st['stalo'] ?? null,
                ])->values()->all();
                $tablitsa = AuditJournalDisplay::podgotovitStrokiTablitsy($izmeneniyaRaw);
                $pokazatTablitsu = !in_array($zapis->deystvie, $sozdanieBezTablitsy, true) && count($tablitsa) > 0;
            @endphp
            @if($pokazatTablitsu)
                <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                    <div class="flex flex-wrap items-baseline justify-between gap-2 mb-2">
                        <span class="font-semibold">{{ AuditJournalDisplay::nadpisDeystviya($zapis->deystvie) }}</span>
                        <span class="text-sm text-gray-600">{{ $zapis->sozdano_at?->format('d.m.Y H:i:s') }}</span>
                    </div>
                    <div class="text-sm text-gray-700 mb-2">
                        @if($zapis->obyekt_type === \App\Models\Contract::class)
                            <span class="badge">Договор по этому объекту</span>
                        @elseif(isset($property) && (int) ($zapis->obyekt_id ?? 0) === (int) $property->id)
                            <span class="badge">Это объявление</span>
                        @else
                            <span class="badge">Объявление</span>
                        @endif
                    </div>
                    <div class="text-sm mb-2">
                        <span class="text-gray-600">Кто внёс запись:</span>
                        @if($zapis->polzovatel)
                            {{ trim($zapis->polzovatel->familia.' '.$zapis->polzovatel->imya.' '.$zapis->polzovatel->otchestvo) }}
                            <span class="text-gray-500">({{ $zapis->polzovatel->email_polzovatela }})</span>
                        @else
                            <span class="text-gray-500">система (автоматическая запись)</span>
                        @endif
                    </div>
                    @if($zapis->kommentariy)
                        <p class="text-sm text-gray-700 mb-2">{{ $zapis->kommentariy }}</p>
                    @endif
                    <div class="mt-3 overflow-x-auto">
                        <table class="w-full text-sm border-collapse">
                            <thead>
                                <tr class="border-b border-gray-300 text-left">
                                    <th class="py-1 pr-2">Что изменилось</th>
                                    <th class="py-1 pr-2">Было</th>
                                    <th class="py-1">Стало</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tablitsa as $st)
                                    <tr class="border-b border-gray-100 align-top">
                                        <td class="py-1 pr-2 font-medium">{{ $st['nadpis_polya'] }}</td>
                                        <td class="py-1 pr-2 text-gray-600 whitespace-pre-wrap break-words max-w-xs">{{ $st['bilo'] !== '' ? $st['bilo'] : '—' }}</td>
                                        <td class="py-1 whitespace-pre-wrap break-words max-w-xs">{{ $st['stalo'] !== '' ? $st['stalo'] : '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @elseif(in_array($zapis->deystvie, $sozdanieBezTablitsy, true))
                <div class="border border-gray-200 rounded-lg p-3 bg-white text-sm text-gray-700">
                    <span class="font-medium">{{ AuditJournalDisplay::nadpisDeystviya($zapis->deystvie) }}</span>
                    <span class="text-gray-500"> — {{ $zapis->sozdano_at?->format('d.m.Y H:i:s') }}</span>
                </div>
            @endif
        @endforeach
    </div>
</div>
@endif
