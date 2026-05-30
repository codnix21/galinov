<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContractTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminContractTemplateController extends Controller
{
    public function index(): View
    {
        if (!Auth::user()->isAdmin()) {
            abort(403);
        }

        return view('admin.contract-templates.index', [
            'templates' => ContractTemplate::orderBy('tip_dogovora')->orderBy('nazvanie')->get(),
        ]);
    }

    public function edit(ContractTemplate $template): View
    {
        if (!Auth::user()->isAdmin()) {
            abort(403);
        }

        return view('admin.contract-templates.edit', compact('template'));
    }

    public function update(Request $request, ContractTemplate $template): RedirectResponse
    {
        if (!Auth::user()->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'nazvanie' => 'required|string|max:120',
            'vvedenie' => 'nullable|string|max:50000',
            'predmet' => 'nullable|string|max:50000',
            'obyazannosti' => 'nullable|string|max:50000',
            'zaklyuchenie' => 'nullable|string|max:50000',
            'aktiven' => 'nullable|boolean',
        ]);

        $template->update([
            'nazvanie' => $validated['nazvanie'],
            'vvedenie' => $validated['vvedenie'],
            'predmet' => $validated['predmet'],
            'obyazannosti' => $validated['obyazannosti'],
            'zaklyuchenie' => $validated['zaklyuchenie'],
            'aktiven' => $request->boolean('aktiven'),
        ]);

        return redirect()->route('admin.contract-templates.index')->with('success', 'Шаблон обновлён.');
    }
}
