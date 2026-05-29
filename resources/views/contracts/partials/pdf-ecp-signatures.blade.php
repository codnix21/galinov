@php
    use App\Services\ContractEcpService;
    $ecp = app(ContractEcpService::class);
    $statuses = $ecp->signatureStatuses($contract);
@endphp
<div class="section">
    <div class="section-title">{{ $signSection ?? '5' }}. Подписи сторон (усиленная квалифицированная ЭП)</div>
    <p style="margin-bottom: 10px; font-size: 10px;">
        Документ подписан в системе «Агентство недвижимости» с применением {{ \App\Services\ContractEcpService::PROVIDER }}.
        Подпись собственника и риэлтора — автоматически при оформлении договора.
    </p>
</div>
<div class="signature-section">
    @foreach($statuses as $st)
        <div class="signature-block">
            @if($st['signed'])
                <div class="ecp-signed">
                    <div class="ecp-stamp">✓ ПОДПИСАНО УКЭП</div>
                    <strong>{{ $st['label'] }}</strong><br>
                    {{ $st['fio'] }}<br>
                    @if($st['auto'])<em>Автоподпись агентства</em><br>@endif
                    № сертификата: {{ $st['nomera'] }}<br>
                    Дата: {{ $st['at']?->format('d.m.Y H:i') }} МСК
                </div>
            @else
                <div class="ecp-pending">
                    {{ $st['label'] }}<br>
                    Ожидает подписи покупателя
                </div>
            @endif
        </div>
    @endforeach
</div>
