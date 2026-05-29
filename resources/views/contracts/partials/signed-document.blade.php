{{-- Опциональный бумажный скан для архива агентства; не заменяет УКЭП. --}}
@php
    $canUploadScan = Auth::user()->isRealtor() || Auth::user()->isAdmin();
    $ecpFullySigned = $ecpFullySigned ?? false;
@endphp
<div class="card p-6 mb-6 border-slate-200 bg-slate-50/50">
    <h3 class="text-xl font-bold mb-2">
        Архив: бумажный скан
        @if($ecpFullySigned)
            <span class="text-sm font-normal text-gray-500">(необязательно)</span>
        @endif
    </h3>
    @if($ecpFullySigned)
        <p class="text-sm text-gray-600 mb-4">
            Договор подписан УКЭП — бумажный экземпляр не нужен.
            @if($canUploadScan)
                При необходимости прикрепите скан для внутреннего архива агентства.
            @endif
        </p>
    @elseif($contract->skan_dogovora)
        <p class="text-sm text-gray-600 mb-4">Дополнительный файл в архиве сделки.</p>
    @endif

    @if($contract->skan_dogovora)
        <div class="mb-4 p-4 rounded-lg border border-green-200 bg-green-50/80">
            <p class="text-sm text-green-900 font-medium mb-1">Скан в архиве</p>
            <a href="{{ $contract->skan_dogovora_url }}" target="_blank" rel="noopener" class="text-sm underline font-medium text-green-800">Открыть файл</a>
        </div>
    @endif

    @if($canUploadScan && $ecpFullySigned)
        <div class="{{ $contract->skan_dogovora ? 'pt-4 border-t border-slate-200' : '' }}">
            <form method="POST" action="{{ route('contracts.upload-scan', $contract) }}" enctype="multipart/form-data" class="space-y-3 max-w-xl">
                @csrf
                <input type="file" id="skan_dogovora" name="skan_dogovora" accept=".pdf,.jpg,.jpeg,.png,.webp" class="form-input" {{ $contract->skan_dogovora ? '' : 'required' }}>
                @error('skan_dogovora')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <button type="submit" class="btn">{{ $contract->skan_dogovora ? 'Заменить скан' : 'Сохранить в архив' }}</button>
            </form>
        </div>
    @endif
</div>
