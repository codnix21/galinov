<?php

namespace App\Http\Controllers;

use App\Models\RealtorClient;
use App\Models\RealtorTask;
use App\Services\AppNotifier;
use App\Support\RealtorCrm;
use App\Support\RealtorScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class RealtorTaskController extends Controller
{
    public function index(Request $request): View
    {
        $query = RealtorTask::query()->with(['client', 'property']);
        RealtorScope::forRealtor($query);

        if ($request->string('filter')->toString() === 'done') {
            $query->whereNotNull('vypolneno_at');
        } elseif ($request->string('filter')->toString() === 'open') {
            $query->whereNull('vypolneno_at');
        }

        $tasks = $query
            ->orderByRaw('vypolneno_at IS NOT NULL')
            ->orderBy('srok_do')
            ->paginate(20)
            ->withQueryString();

        $clients = RealtorClient::query()
            ->with('client');
        RealtorScope::forRealtor($clients);
        $clientOptions = $clients->get();

        return view('realtor.tasks.index', [
            'tasks' => $tasks,
            'clientOptions' => $clientOptions,
            'taskTypes' => RealtorCrm::taskTypes(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nazvanie' => 'required|string|max:255',
            'opisanie' => 'nullable|string|max:2000',
            'tip' => 'required|in:call,meeting,showing,other',
            'klient_id' => 'nullable|exists:polzovateli,id',
            'nedvizhimost_id' => 'nullable|exists:nedvizhimost,id',
            'srok_do' => 'nullable|date',
        ]);

        $rieltorId = RealtorScope::currentRealtorId();

        if (!empty($validated['klient_id']) && !RealtorScope::isAgencyView()) {
            $exists = RealtorClient::where('rieltor_id', $rieltorId)
                ->where('klient_id', $validated['klient_id'])
                ->exists();
            if (!$exists) {
                return back()->withErrors(['klient_id' => 'Сначала закрепите клиента в CRM']);
            }
        }

        $task = RealtorTask::create([
            'rieltor_id' => $rieltorId,
            'klient_id' => $validated['klient_id'] ?? null,
            'nedvizhimost_id' => $validated['nedvizhimost_id'] ?? null,
            'nazvanie' => $validated['nazvanie'],
            'opisanie' => $validated['opisanie'] ?? null,
            'tip' => $validated['tip'],
            'srok_do' => !empty($validated['srok_do']) ? Carbon::parse($validated['srok_do']) : null,
        ]);

        if ($task->realtor) {
            AppNotifier::reminder(
                $task->realtor,
                'Новая задача',
                $task->nazvanie,
                route('realtor.tasks.index'),
            );
        }
        if ($task->klient_id && $task->client) {
            AppNotifier::taskAssigned($task);
        }

        return back()->with('success', 'Задача создана');
    }

    public function complete(RealtorTask $task): RedirectResponse
    {
        RealtorScope::assertRealtorOwns((int) $task->rieltor_id);
        $task->update(['vypolneno_at' => now()]);

        return back()->with('success', 'Задача выполнена');
    }

    public function destroy(RealtorTask $task): RedirectResponse
    {
        RealtorScope::assertRealtorOwns((int) $task->rieltor_id);
        $task->delete();

        return back()->with('success', 'Задача удалена');
    }
}
