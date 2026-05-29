@if(!empty($viewUrl))
    <a href="{{ $viewUrl }}" target="_blank" rel="noopener" class="text-xs text-brand-700 underline mt-1 inline-block">Просмотреть документ</a>
@elseif(!empty($egrnJsonOnly))
    <span class="text-xs text-slate-500 mt-1 inline-block">Подтверждено по кадастровому номеру</span>
@elseif(!empty($hasPathButMissing))
    <span class="text-xs text-amber-700 mt-1 inline-block">Файл не найден — загрузите документ снова</span>
@endif
