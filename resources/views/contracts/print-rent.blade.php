{{-- Печатная форма договора аренды. --}}
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Договор найма жилого помещения № {{ $contract->id }}</title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; font-size: 12pt; line-height: 1.45; max-width: 210mm; margin: 0 auto; padding: 16mm 18mm; color: #111; }
        h1 { font-size: 13pt; text-align: center; font-weight: bold; margin: 0 0 1em; text-transform: uppercase; }
        .meta { text-align: center; margin-bottom: 1.5em; font-size: 11pt; color: #333; }
        .section { margin-top: 1.1em; }
        .section-title { font-weight: bold; margin-bottom: 0.35em; }
        p { margin: 0.4em 0; text-align: justify; }
        .signatures { margin-top: 2.5em; display: table; width: 100%; }
        .sign-cell { display: table-cell; width: 48%; vertical-align: top; padding-right: 2%; }
        .line { border-bottom: 1px solid #000; min-height: 2.2em; margin-bottom: 0.25em; }
        .hint { font-size: 10pt; color: #555; margin-top: 2em; padding: 0.6em; border: 1px dashed #999; }
        .no-print { margin-bottom: 1em; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 12mm; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button type="button" onclick="window.print()" style="padding:8px 16px;cursor:pointer;">Печать</button>
        <a href="{{ route('contracts.show', $contract) }}" style="margin-left:12px;">Вернуться к договору</a>
    </div>

    @php
        $owner = $contract->owner ?? $contract->property?->user;
        $buyer = $contract->buyer ?? $contract->client;
        $realtor = $contract->realtor;
        $prop = $contract->property;
        $tenantFio = $buyer ? trim(($buyer->familia ?? '') . ' ' . ($buyer->imya ?? '') . ' ' . ($buyer->otchestvo ?? '')) : '________________________________';
        $landlordFio = $owner ? trim(($owner->familia ?? '') . ' ' . ($owner->imya ?? '') . ' ' . ($owner->otchestvo ?? '')) : '________________________________';
        $addr = trim(($prop->gorod ?? '') . ', ' . ($prop->adres_ulitsy ?? ''));
        $realtorFio = trim(($realtor->familia ?? '') . ' ' . ($realtor->imya ?? '') . ' ' . ($realtor->otchestvo ?? ''));
    @endphp

    <h1>Договор найма жилого помещения<br>(типовая форма для заполнения и подписания)</h1>
    <p class="meta">г. _______________ &nbsp;&nbsp; «____» _____________ 20___ г.<br>
        <small>Вписать город и дату подписания от руки при встрече сторон.</small></p>

    <div class="section">
        <p class="section-title">1. Стороны</p>
        <p><strong>Наймодатель (арендодатель):</strong> {{ $landlordFio }}, паспорт: серия _______ № ____________, выдан _________________________</p>
        <p><strong>Наниматель (арендатор):</strong> {{ $tenantFio }}, паспорт: серия _______ № ____________, выдан _________________________</p>
        <p>Далее совместно именуются «Стороны», а по отдельности — «Сторона».</p>
    </div>

    <div class="section">
        <p class="section-title">2. Предмет договора</p>
        <p>Наймодатель передаёт, а Наниматель принимает во временное возмездное пользование жилое помещение (или часть):</p>
        <p><strong>Адрес:</strong> {{ $addr ?: '________________________________' }}</p>
        <p><strong>Краткое описание:</strong> {{ $prop->nazvanie ?? '—' }}@if($prop->ploshchad), площадь {{ $prop->ploshchad }} м²@endif @if($prop->komnaty), комнат: {{ $prop->komnaty }}@endif.</p>
    </div>

    <div class="section">
        <p class="section-title">3. Срок и плата</p>
        <p><strong>Срок найма:</strong> с {{ $contract->data_nachala->format('d.m.Y') }} @if($contract->data_okonchaniya) по {{ $contract->data_okonchaniya->format('d.m.Y') }} включительно @else по согласованию сторон (указать вручную) @endif.</p>
        <p><strong>Размер ежемесячной платы:</strong> {{ number_format($contract->tsena, 2, ',', ' ') }} ({{ number_format($contract->tsena, 2, ',', ' ') }}) рублей. Порядок и сроки внесения платы: _________________________________</p>
    </div>

    <div class="section">
        <p class="section-title">4. Права и обязанности (кратко)</p>
        <p>4.1. Наниматель обязуется использовать помещение по назначению, своевременно вносить плату, не нарушать права соседей.</p>
        <p>4.2. Наймодатель обязуется передать помещение в состоянии, пригодном для проживания, обеспечивать пользование без препятствий со своей стороны.</p>
        <p>4.3. Ответственность сторон, порядок расторжения и иные условия могут быть дополнены от руки или отдельным приложением по согласованию сторон.</p>
    </div>

    <div class="section">
        <p class="section-title">5. Подписи сторон</p>
        <p>Настоящий договор составлен в двух экземплярах, имеющих одинаковую юридическую силу, по одному для каждой из Сторон.</p>
    </div>

    <div class="signatures">
        <div class="sign-cell">
            <p><strong>Наймодатель:</strong></p>
            <div class="line"></div>
            <p style="font-size:10pt;">подпись / ФИО</p>
        </div>
        <div class="sign-cell">
            <p><strong>Наниматель:</strong></p>
            <div class="line"></div>
            <p style="font-size:10pt;">подпись / ФИО</p>
        </div>
    </div>

    <p style="margin-top:1.5em;font-size:10pt;"><strong>Посредник (риэлтор):</strong> {{ $realtorFio }} — действует на основании договора с одной из сторон (указать при необходимости).</p>

    <div class="hint no-print">
        Это упрощённый типовой текст для печати и личного подписания. Он не заменяет юридическую консультацию.
        После подписания отсканируйте или сфотографируйте договор и загрузите файл на странице договора в системе (раздел «Подписанный договор»).
    </div>
</body>
</html>
