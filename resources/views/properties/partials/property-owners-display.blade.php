@php
    $property->loadMissing(['owners.user']);
    \App\Services\PropertyOwnersService::ensureDefaultOwner($property);
    $property->load('owners.user');
    $owners = $property->owners;
@endphp
@if($owners->isNotEmpty())
    <div class="mb-8" id="sobstvenniki">
        <h2 class="text-2xl font-bold mb-4">Собственники</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-600 border-b">
                        <th class="pb-2 pr-4">ФИО</th>
                        <th class="pb-2 pr-4">Доля</th>
                        <th class="pb-2">Роль в договоре</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($owners as $owner)
                        <tr class="border-b border-gray-100">
                            <td class="py-3 pr-4 font-medium">{{ $owner->fio() }}</td>
                            <td class="py-3 pr-4">{{ number_format((float) $owner->dolya_procent, 2, ',', ' ') }} %</td>
                            <td class="py-3">
                                @if($owner->osnovnoy)
                                    <span class="badge">Основной продавец</span>
                                @else
                                    <span class="text-gray-500">Со‑продавец</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="text-xs text-gray-500 mt-2">При оформлении договора все собственники включаются как продавцы.</p>
    </div>
@endif
