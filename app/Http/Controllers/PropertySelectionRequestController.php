<?php

namespace App\Http\Controllers;

use App\Models\PropertySelectionRequest;
use App\Models\User;
use App\Services\AppNotifier;
use App\Models\City;
use App\Support\PropertyCatalogSimilar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PropertySelectionRequestController extends Controller
{
    public function create(Request $request): View
    {
        $cities = City::query()->orderBy('nazvanie')->get(['id', 'nazvanie']);

        return view('properties.selection-request', [
            'cities' => $cities,
            'oldFilters' => old('filters', []),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'imya' => ['required', 'string', 'max:120'],
            'telefon' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'kommentariy' => ['nullable', 'string', 'max:2000'],
            'filters' => ['nullable', 'array'],
        ]);

        $user = Auth::user();
        $filters = is_array($validated['filters'] ?? null)
            ? array_filter($validated['filters'], fn ($v) => is_scalar($v) && $v !== '')
            : PropertyCatalogSimilar::captureFilters($request);

        $selectionRequest = PropertySelectionRequest::create([
            'polzovatel_id' => $user?->getKey(),
            'imya' => $validated['imya'],
            'telefon' => $validated['telefon'] ?? $user?->telefon,
            'email' => $validated['email'] ?? $user?->email_polzovatela,
            'kommentariy' => $validated['kommentariy'] ?? null,
            'filtry' => $filters,
            'status' => 'new',
            'istochnik' => $request->input('istochnik', 'catalog'),
        ]);

        AppNotifier::propertySelectionRequest($selectionRequest);

        $isForm = ($request->input('istochnik') === 'form');

        if ($isForm) {
            return redirect()
                ->route('cabinet.index')
                ->with('success', 'Заявка на подбор принята. Риэлтор свяжется с вами и подготовит варианты.');
        }

        $redirectParams = array_filter($filters, fn ($v) => is_scalar($v) && $v !== '');

        return redirect()
            ->route('properties.index', $redirectParams)
            ->with('success', 'Заявка риэлтору отправлена. Мы подберём варианты и свяжемся с вами.')
            ->withFragment('zayavka-rieltoru');
    }

    public function index(Request $request): View
    {
        if (!$request->user()->isStaff()) {
            abort(403);
        }

        $requests = PropertySelectionRequest::with(['user', 'assignedRealtor'])
            ->orderByRaw(\App\Models\RequestStatus::fieldOrderSql('selection', ['new', 'processed']))
            ->orderByDesc('sozdano_at')
            ->paginate(20);

        $realtors = User::whereHas('roleRelation', fn ($q) => $q->whereIn('kod', ['realtor', 'admin']))->orderBy('familia')->get();

        return view('realtor.selection-requests', compact('requests', 'realtors'));
    }

    public function process(Request $request, PropertySelectionRequest $selectionRequest): RedirectResponse
    {
        if (!$request->user()->isStaff()) {
            abort(403);
        }

        $selectionRequest->update(['status' => 'processed']);

        return back()->with('success', 'Заявка на подбор отмечена обработанной.');
    }
}
