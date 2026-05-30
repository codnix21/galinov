<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\PropertyInfoRequest;
use App\Models\PropertyInfoRequestMessage;
use App\Models\PropertyStatus;
use App\Services\AppNotifier;
use App\Support\PropertyInfoRequestTypes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PropertyInfoRequestController extends Controller
{
    public function store(Request $request, Property $property): RedirectResponse
    {
        $activeId = PropertyStatus::idFor('active');
        if ($activeId === null || (int) $property->status_obyavleniya_id !== (int) $activeId) {
            return back()->with('error', 'Запрос можно оставить только по активному объявлению.');
        }

        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        if ((int) $user->id === (int) ($property->polzovatel_id ?? 0)) {
            return back()->with('error', 'Нельзя запросить информацию по своему объявлению.');
        }

        $validated = $request->validate([
            'tip' => ['required', 'in:'.implode(',', PropertyInfoRequestTypes::keys())],
            'tekst' => ['required', 'string', 'max:2000'],
        ]);

        $infoRequest = DB::transaction(function () use ($property, $user, $validated) {
            $infoRequest = PropertyInfoRequest::create([
                'nedvizhimost_id' => $property->id,
                'polzovatel_id' => $user->id,
                'tip' => $validated['tip'],
                'status' => 'open',
            ]);

            PropertyInfoRequestMessage::create([
                'zapros_id' => $infoRequest->id,
                'polzovatel_id' => $user->id,
                'ot_kogo' => 'client',
                'tekst' => $validated['tekst'],
                'sozdano_at' => now(),
            ]);

            return $infoRequest;
        });

        AppNotifier::propertyInfoRequestCreated($infoRequest);

        return back()
            ->with('success', 'Запрос отправлен риэлтору. Ответ появится в истории ниже.')
            ->withFragment('dop-informaciya');
    }

    public function reply(Request $request, PropertyInfoRequest $infoRequest): RedirectResponse
    {
        $user = $request->user();
        if (!$user || !$user->isStaff()) {
            abort(403);
        }

        $validated = $request->validate([
            'tekst' => ['required', 'string', 'max:5000'],
        ]);

        PropertyInfoRequestMessage::create([
            'zapros_id' => $infoRequest->id,
            'polzovatel_id' => $user->id,
            'ot_kogo' => 'staff',
            'tekst' => $validated['tekst'],
            'sozdano_at' => now(),
        ]);

        $infoRequest->update(['status' => 'answered']);
        $infoRequest->load(['property', 'client']);
        AppNotifier::propertyInfoRequestAnswered($infoRequest);

        return back()->with('success', 'Ответ отправлен клиенту.');
    }

    public function index(Request $request): View
    {
        if (!$request->user()->isStaff()) {
            abort(403);
        }

        $requests = PropertyInfoRequest::with(['property', 'client', 'messages'])
            ->orderByRaw(\App\Models\RequestStatus::fieldOrderSql('info', ['open', 'answered', 'closed']))
            ->orderByDesc('obnovleno_at')
            ->paginate(20);

        return view('realtor.info-requests', compact('requests'));
    }

    public function close(Request $request, PropertyInfoRequest $infoRequest): RedirectResponse
    {
        if (!$request->user()->isStaff()) {
            abort(403);
        }

        $infoRequest->update(['status' => 'closed']);

        return back()->with('success', 'Запрос закрыт.');
    }
}
