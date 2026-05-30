<?php

namespace App\Http\Controllers;

use App\Models\SavedSearch;
use App\Services\SavedSearchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SavedSearchController extends Controller
{
    public function index(): View
    {
        $searches = SavedSearch::where('polzovatel_id', Auth::id())
            ->latest()
            ->paginate(15);

        return view('saved-searches.index', compact('searches'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nazvanie' => 'required|string|max:120',
            'uvedomleniya' => 'nullable|boolean',
        ]);

        $filters = SavedSearchService::normalizeFilters($request->except(['_token', 'nazvanie', 'uvedomleniya']));

        SavedSearchService::store(
            Auth::user(),
            $validated['nazvanie'],
            $filters,
            $request->boolean('uvedomleniya', true),
        );

        return redirect()->route('saved-searches.index')->with('success', 'Поиск сохранён.');
    }

    public function destroy(SavedSearch $savedSearch): RedirectResponse
    {
        if ((int) $savedSearch->polzovatel_id !== (int) Auth::id()) {
            abort(403);
        }

        $savedSearch->delete();

        return back()->with('success', 'Сохранённый поиск удалён.');
    }

    public function toggle(SavedSearch $savedSearch): RedirectResponse
    {
        if ((int) $savedSearch->polzovatel_id !== (int) Auth::id()) {
            abort(403);
        }

        $savedSearch->update(['uvedomleniya' => !$savedSearch->uvedomleniya]);

        return back()->with('success', 'Уведомления обновлены.');
    }
}
