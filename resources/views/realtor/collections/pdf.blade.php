<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>{{ $collection->nazvanie }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        .meta { color: #64748b; margin-bottom: 16px; }
        .card { border: 1px solid #e2e8f0; padding: 10px; margin-bottom: 12px; page-break-inside: avoid; }
        .price { font-weight: bold; font-size: 13px; }
        img { max-width: 100%; max-height: 120px; }
    </style>
</head>
<body>
    <h1>{{ $collection->nazvanie }}</h1>
    <p class="meta">
        Агентство недвижимости · {{ now()->format('d.m.Y') }}
        @if($collection->client)
            · Клиент: {{ trim($collection->client->familia.' '.$collection->client->imya) }}
        @endif
    </p>
    @if($collection->kommentariy)
        <p>{{ $collection->kommentariy }}</p>
    @endif

    @foreach($collection->items as $item)
        @php $p = $item->property; @endphp
        @if(!$p) @continue @endif
        <div class="card">
            <strong>{{ $p->nazvanie }}</strong><br>
            {{ $p->gorod ?? '—' }}, {{ $p->adres_ulitsy ?? '' }}<br>
            <span class="price">{{ number_format((float) $p->tsena, 0, ',', ' ') }} ₽</span>
            @if($p->ploshchad) · {{ $p->ploshchad }} м² @endif
        </div>
    @endforeach
</body>
</html>
