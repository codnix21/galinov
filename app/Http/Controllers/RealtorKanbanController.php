<?php

namespace App\Http\Controllers;

use App\Models\RealtorClient;
use App\Models\RequestStatus;
use App\Support\RealtorScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RealtorKanbanController extends Controller
{
    /** @var list<string> */
    public const COLUMNS = ['new', 'in_progress', 'deal', 'lost'];

    public function index(): View
    {
        if (!request()->user()->isStaff()) {
            abort(403);
        }

        $columns = [];
        foreach (self::COLUMNS as $kod) {
            $q = RealtorClient::query()->with(['client']);
            RealtorScope::forRealtor($q);
            $statusId = RequestStatus::idFor('crm', $kod);
            if ($statusId !== null) {
                $q->where('status_zayavki_id', $statusId);
            }
            $columns[$kod] = [
                'kod' => $kod,
                'title' => RequestStatus::query()->where('gruppa', 'crm')->where('kod', $kod)->value('nazvanie') ?? $kod,
                'clients' => $q->orderByDesc('obnovleno_at')->limit(30)->get(),
            ];
        }

        return view('realtor.kanban', compact('columns'));
    }

    public function updateStatus(Request $request, RealtorClient $realtorClient): RedirectResponse|JsonResponse
    {
        if (!request()->user()->isStaff()) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => 'required|in:'.implode(',', self::COLUMNS),
        ]);

        $statusId = RequestStatus::idFor('crm', $validated['status']);
        if ($statusId === null) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Неизвестный статус.'], 422);
            }

            return back()->with('error', 'Неизвестный статус.');
        }

        $realtorClient->update([
            'status_zayavki_id' => $statusId,
            'status' => $validated['status'],
        ]);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'status' => $validated['status']]);
        }

        return redirect()->route('realtor.kanban')->with('success', 'Статус клиента обновлён.');
    }
}
