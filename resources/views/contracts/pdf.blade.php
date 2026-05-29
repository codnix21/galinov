{{-- PDF договора (купля-продажа / аренда) с обязанностями сторон. --}}
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Договор №{{ $contract->id }}</title>
    @include('contracts.partials.pdf-styles')
</head>
<body>
    @php
        $isRent = ($contract->tip ?? $contract->type) === 'rent';
        $ownerParty = $contract->owner ?? $contract->resolvedOwner();
        $buyerParty = $contract->buyer ?? $contract->client;
        $notesSection = $contract->primechaniya ? 5 : 4;
        $signSection = $contract->primechaniya ? 6 : 5;
    @endphp

    <div class="header">
        <h1>ДОГОВОР {{ $isRent ? 'АРЕНДЫ' : 'КУПЛИ-ПРОДАЖИ' }} НЕДВИЖИМОСТИ</h1>
        <div class="contract-number">№ {{ $contract->id }} от {{ $contract->data_nachala->format('d.m.Y') }}</div>
    </div>

    <div class="section">
        <div class="section-title">1. Предмет договора</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Тип:</div>
                <div class="info-value">{{ $contract->type_name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Статус:</div>
                <div class="info-value">
                    <span class="status-badge status-{{ $contract->status }}">{{ $contract->status_name }}</span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">{{ $isRent ? 'Арендная плата (мес.):' : 'Цена договора:' }}</div>
                <div class="info-value"><strong>{{ number_format($contract->tsena, 2, ',', ' ') }} ₽</strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">Дата начала:</div>
                <div class="info-value">{{ $contract->data_nachala->format('d.m.Y') }}</div>
            </div>
            @if($isRent && $contract->data_okonchaniya)
            <div class="info-row">
                <div class="info-label">Дата окончания аренды:</div>
                <div class="info-value">{{ $contract->data_okonchaniya->format('d.m.Y') }}</div>
            </div>
            @endif
        </div>
        <p style="margin-top: 8px; text-align: justify;">
            {{ $isRent ? 'Арендодатель' : 'Продавец' }} обязуется передать, а
            {{ $isRent ? 'Арендатор' : 'Покупатель' }} принять объект недвижимости, указанный в разделе 2,
            на условиях настоящего договора.
        </p>
    </div>

    <div class="section">
        <div class="section-title">2. Объект недвижимости</div>
        <div class="property-details">
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Название:</div>
                    <div class="info-value"><strong>{{ $contract->property->nazvanie }}</strong></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Адрес:</div>
                    <div class="info-value">{{ $contract->property->gorod }}, {{ $contract->property->adres_ulitsy }}</div>
                </div>
                @if($contract->property->tip_nazvanie ?? null)
                <div class="info-row">
                    <div class="info-label">Тип:</div>
                    <div class="info-value">{{ $contract->property->tip_nazvanie }}</div>
                </div>
                @endif
                @if($contract->property->ploshchad)
                <div class="info-row">
                    <div class="info-label">Площадь:</div>
                    <div class="info-value">{{ $contract->property->ploshchad }} м²</div>
                </div>
                @endif
                @if($contract->property->komnaty)
                <div class="info-row">
                    <div class="info-label">Комнат:</div>
                    <div class="info-value">{{ $contract->property->komnaty }}</div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="section parties">
        <div class="section-title">3. Стороны договора</div>

        <div class="party">
            <div class="party-title">Сторона 1 — {{ $isRent ? 'арендодатель (владелец)' : 'продавец (владелец)' }}:</div>
            <div class="info-grid">
                @if($ownerParty)
                <div class="info-row">
                    <div class="info-label">ФИО:</div>
                    <div class="info-value">{{ trim($ownerParty->familia . ' ' . $ownerParty->imya . ' ' . ($ownerParty->otchestvo ?? '')) }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Email:</div>
                    <div class="info-value">{{ $ownerParty->email_polzovatela }}</div>
                </div>
                @if($ownerParty->telefon)
                <div class="info-row">
                    <div class="info-label">Телефон:</div>
                    <div class="info-value">{{ $ownerParty->telefon }}</div>
                </div>
                @endif
                @else
                <div class="info-value">Не указан</div>
                @endif
            </div>
        </div>

        <div class="party">
            <div class="party-title">Сторона 2 — {{ $isRent ? 'арендатор' : 'покупатель' }}:</div>
            <div class="info-grid">
                @if($buyerParty)
                <div class="info-row">
                    <div class="info-label">ФИО:</div>
                    <div class="info-value">{{ trim($buyerParty->familia . ' ' . $buyerParty->imya . ' ' . ($buyerParty->otchestvo ?? '')) }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Email:</div>
                    <div class="info-value">{{ $buyerParty->email_polzovatela }}</div>
                </div>
                @if($buyerParty->telefon)
                <div class="info-row">
                    <div class="info-label">Телефон:</div>
                    <div class="info-value">{{ $buyerParty->telefon }}</div>
                </div>
                @endif
                @else
                <div class="info-value">Не указан</div>
                @endif
            </div>
        </div>

        @if($contract->realtor)
        <div class="party">
            <div class="party-title">Риэлтор, ведущий сделку:</div>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">ФИО:</div>
                    <div class="info-value">{{ trim($contract->realtor->familia . ' ' . $contract->realtor->imya . ' ' . ($contract->realtor->otchestvo ?? '')) }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Email:</div>
                    <div class="info-value">{{ $contract->realtor->email_polzovatela }}</div>
                </div>
                @if($contract->realtor->telefon)
                <div class="info-row">
                    <div class="info-label">Телефон:</div>
                    <div class="info-value">{{ $contract->realtor->telefon }}</div>
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>

    @include('contracts.partials.pdf-obligations', ['sectionNum' => 4])

    @if($contract->primechaniya)
    <div class="section">
        <div class="section-title">{{ $notesSection }}. Дополнительные условия</div>
        <div class="notes">{{ $contract->primechaniya }}</div>
    </div>
    @endif

    @include('contracts.partials.pdf-ecp-signatures', ['signSection' => $signSection])

    <div class="footer">
        <p>Документ сформирован {{ date('d.m.Y H:i') }} | Договор № {{ $contract->id }} | {{ $contract->status_name }}</p>
        <p style="margin-top: 6px;">После подписания всеми сторонами риэлтор или администратор загружает скан в систему — он доступен всем участникам сделки.</p>
    </div>
</body>
</html>
