<form method="POST" action="{{ $action }}" class="mt-3 flex flex-wrap items-end gap-2">
    @csrf
    @method('PATCH')
    <div class="min-w-[180px]">
        <label class="text-xs text-slate-500 block mb-1">Риэлтор</label>
        <select name="naznachen_rieltor_id" class="form-input text-sm">
            <option value="">— не назначен —</option>
            @foreach($realtors as $r)
                <option value="{{ $r->id }}" {{ (int) ($assignedId ?? 0) === (int) $r->id ? 'selected' : '' }}>
                    {{ trim($r->familia.' '.$r->imya) }}
                </option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="btn text-sm">Назначить</button>
</form>
