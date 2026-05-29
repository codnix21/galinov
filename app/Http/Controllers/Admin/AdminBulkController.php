<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ContractStatus;
use App\Models\Property;
use App\Models\PropertyStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminBulkController extends Controller
{
    public function properties(Request $request): RedirectResponse
    {
        $this->assertAdmin();

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:nedvizhimost,id'],
            'status_kod' => ['required', 'string', 'exists:statusy_obyavleniy,kod'],
            'confirm_bulk' => ['accepted'],
        ], [
            'ids.required' => 'Выберите хотя бы одно объявление.',
            'status_kod.exists' => 'Неизвестный статус.',
            'confirm_bulk.accepted' => 'Подтвердите массовое изменение.',
        ]);

        $statusId = PropertyStatus::idFor($validated['status_kod']);
        $count = 0;

        DB::transaction(function () use ($validated, $statusId, &$count) {
            $properties = Property::whereIn('id', $validated['ids'])->lockForUpdate()->get();
            foreach ($properties as $property) {
                $property->update(['status_obyavleniya_id' => $statusId]);
                $count++;
            }
        });

        return back()->with('success', "Статус обновлён у {$count} объявл.");
    }

    public function contracts(Request $request): RedirectResponse
    {
        $this->assertAdmin();

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:dogovory,id'],
            'status_kod' => ['required', 'string', 'exists:statusy_dogovorov,kod'],
            'confirm_bulk' => ['accepted'],
        ], [
            'ids.required' => 'Выберите хотя бы один договор.',
            'status_kod.exists' => 'Неизвестный статус.',
            'confirm_bulk.accepted' => 'Подтвердите массовое изменение.',
        ]);

        $statusId = ContractStatus::idFor($validated['status_kod']);
        $count = 0;

        DB::transaction(function () use ($validated, $statusId, &$count) {
            $contracts = Contract::whereIn('id', $validated['ids'])->lockForUpdate()->get();
            foreach ($contracts as $contract) {
                $contract->update(['status_dogovora_id' => $statusId]);
                $count++;
            }
        });

        return back()->with('success', "Статус обновлён у {$count} договоров.");
    }

    private function assertAdmin(): void
    {
        if (!Auth::user()?->isAdmin()) {
            abort(403);
        }
    }
}
