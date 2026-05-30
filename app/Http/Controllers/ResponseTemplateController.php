<?php

namespace App\Http\Controllers;

use App\Models\ResponseTemplate;
use App\Support\RealtorScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ResponseTemplateController extends Controller
{
    public function index(): View
    {
        if (!Auth::user()->isStaff()) {
            abort(403);
        }

        $rieltorId = RealtorScope::currentRealtorId();
        $templates = ResponseTemplate::query()
            ->where(fn ($q) => $q->whereNull('rieltor_id')->orWhere('rieltor_id', $rieltorId))
            ->orderBy('nazvanie')
            ->get();

        return view('realtor.templates.index', compact('templates'));
    }

    public function store(Request $request): RedirectResponse
    {
        if (!Auth::user()->isStaff()) {
            abort(403);
        }

        $validated = $request->validate([
            'nazvanie' => 'required|string|max:120',
            'kod' => 'required|string|max:64',
            'tekst' => 'required|string|max:10000',
            'kontekst' => 'required|in:info,inquiry,selection',
        ]);

        ResponseTemplate::create([
            'rieltor_id' => RealtorScope::currentRealtorId(),
            'kod' => $validated['kod'],
            'nazvanie' => $validated['nazvanie'],
            'tekst' => $validated['tekst'],
            'kontekst' => $validated['kontekst'],
        ]);

        return back()->with('success', 'Шаблон сохранён.');
    }

    public function destroy(ResponseTemplate $template): RedirectResponse
    {
        if (!Auth::user()->isStaff()) {
            abort(403);
        }

        if ($template->rieltor_id && (int) $template->rieltor_id !== (int) RealtorScope::currentRealtorId() && !Auth::user()->isAdmin()) {
            abort(403);
        }

        $template->delete();

        return back()->with('success', 'Шаблон удалён.');
    }
}
