<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\PropertyInquiry;
use App\Models\PropertyStatus;
use App\Services\AppNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PropertyInquiryController extends Controller
{
    public function store(Request $request, Property $property): RedirectResponse
    {
        $activeId = PropertyStatus::idFor('active');
        if ($activeId === null || (int) $property->status_obyavleniya_id !== (int) $activeId) {
            return back()->with('error', 'Заявку можно оставить только по активному объявлению.');
        }

        $validated = $request->validate([
            'imya' => ['required', 'string', 'max:120'],
            'telefon' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'kommentariy' => ['nullable', 'string', 'max:2000'],
        ]);

        $inquiry = PropertyInquiry::create([
            'nedvizhimost_id' => $property->id,
            'polzovatel_id' => Auth::user()?->getKey(),
            'imya' => $validated['imya'],
            'telefon' => $validated['telefon'] ?? null,
            'email' => $validated['email'] ?? null,
            'kommentariy' => $validated['kommentariy'] ?? null,
            'status' => 'new',
        ]);

        AppNotifier::propertyInquiry($inquiry);

        return back()->with('success', 'Заявка отправлена. Риэлтор свяжется с вами — обычно в течение рабочего дня.');
    }

    public function index(Request $request): View
    {
        if (!$request->user()->isStaff()) {
            abort(403);
        }

        $inquiries = PropertyInquiry::with(['property', 'user'])
            ->orderByRaw("FIELD(status, 'new', 'processed')")
            ->orderByDesc('sozdano_at')
            ->paginate(20);

        return view('realtor.inquiries', compact('inquiries'));
    }

    public function process(Request $request, PropertyInquiry $inquiry): RedirectResponse
    {
        if (!$request->user()->isStaff()) {
            abort(403);
        }

        $inquiry->update(['status' => 'processed']);
        AppNotifier::propertyInquiryProcessed($inquiry->fresh());

        return back()->with('success', 'Заявка отмечена обработанной.');
    }
}
