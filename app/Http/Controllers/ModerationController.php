<?php

/**
 * Контроллер модерации объявлений.
 *
 * Сотрудники (риелторы и админы) видят очередь объявлений «на проверке»
 * и могут одобрить (опубликовать) или отклонить с указанием причины.
 */

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\PropertyStatus;
use App\Services\AppNotifier;
use App\Services\TextCensor;
use App\Support\PropertyDocumentRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Страницы модерации: список ожидающих, одобрение, отклонение.
 */
class ModerationController extends Controller
{
    /**
     * Список объявлений, ожидающих проверки модератором.
     */
    public function index(Request $request): View
    {
        $pending = PropertyStatus::where('kod', 'pending_review')->first();
        $qb = Property::query()->with(['user', 'realtor', 'images', 'cityRelation']);
        if ($pending) {
            $qb->where('status_obyavleniya_id', $pending->id);
        } else {
            $qb->whereRaw('1 = 0');
        }

        $q = trim((string) $request->input('q', ''));
        if ($q !== '') {
            $escaped = addcslashes($q, '%_\\');
            $qb->where(function ($w) use ($escaped) {
                $w->where('nazvanie', 'like', '%'.$escaped.'%')
                    ->orWhere('adres_ulitsy', 'like', '%'.$escaped.'%')
                    ->orWhereHas('user', function ($u) use ($escaped) {
                        $u->where('familia', 'like', '%'.$escaped.'%')
                            ->orWhere('imya', 'like', '%'.$escaped.'%')
                            ->orWhere('otchestvo', 'like', '%'.$escaped.'%')
                            ->orWhere('email_polzovatela', 'like', '%'.$escaped.'%');
                    })
                    ->orWhereHas('realtor', function ($r) use ($escaped) {
                        $r->where('familia', 'like', '%'.$escaped.'%')
                            ->orWhere('imya', 'like', '%'.$escaped.'%')
                            ->orWhere('email_polzovatela', 'like', '%'.$escaped.'%');
                    });
            });
        }

        $docs = (string) $request->input('docs', 'all');
        if ($docs === 'ready') {
            PropertyDocumentRules::applyStaffModerationReadyFilter($qb);
        } elseif ($docs === 'not_ready') {
            PropertyDocumentRules::applyStaffModerationNotReadyFilter($qb);
        }

        $sort = (string) $request->input('sort', 'newest');
        $dir = $request->input('dir', 'asc') === 'desc' ? 'desc' : 'asc';
        if ($sort === 'client') {
            $qb->leftJoin('polzovateli as mod_client', 'mod_client.id', '=', 'nedvizhimost.polzovatel_id')
                ->orderBy('mod_client.familia', $dir)
                ->orderBy('mod_client.imya', $dir)
                ->select('nedvizhimost.*');
        } elseif ($sort === 'price') {
            $qb->orderBy('tsena', $dir);
        } else {
            $qb->latest();
        }

        $properties = $qb->paginate(15)->withQueryString();

        $docReadiness = [];
        $moderationDocs = [];
        foreach ($properties as $p) {
            $docReadiness[$p->id] = PropertyDocumentRules::isReadyForStaffModeration($p);
            $moderationDocs[$p->id] = PropertyDocumentRules::moderationCoreDocumentViews($p);
        }

        return view('moderation.index', compact(
            'properties',
            'docReadiness',
            'moderationDocs',
            'q',
            'sort',
            'dir',
            'docs',
        ));
    }

    /**
     * Одобрить объявление: перевести в статус «активно» (видно в каталоге).
     */
    public function approve(Property $property): RedirectResponse
    {
        $this->assertPending($property);

        $user = Auth::user();
        if ($user->isRealtor() && !$user->isAdmin()) {
            $isOwn = (int) $property->polzovatel_id === (int) $user->id
                || (int) ($property->rieltor_id ?? 0) === (int) $user->id;
            if ($isOwn) {
                return redirect()->route('moderation.index')->withErrors(['error' => 'Нельзя одобрить собственное объявление']);
            }
        }

        if (TextCensor::propertyFieldErrors($property->nazvanie, $property->opisanie) !== []) {
            return redirect()->route('moderation.index')->withErrors([
                'error' => 'Нельзя одобрить: в названии или описании есть ненормативная лексика. Отклоните объявление и укажите причину.',
            ]);
        }

        if (!PropertyDocumentRules::isReadyForStaffModeration($property)) {
            $labels = PropertyDocumentRules::allTipLabels();
            $need = array_map(
                fn (string $tip) => $labels[$tip] ?? $tip,
                PropertyDocumentRules::moderationCoreTips($property),
            );

            return redirect()->route('moderation.index')->withErrors([
                'error' => 'Для одобрения нужны проверенные документы: '.implode('; ', $need),
            ]);
        }

        $active = PropertyStatus::where('kod', 'active')->firstOrFail();
        $property->update([
            'status_obyavleniya_id' => $active->id,
            'prichina_otkaza_mod' => null,
        ]);

        AppNotifier::propertyModerationApproved($property->fresh());

        return redirect()->route('moderation.index')->with('success', 'Объявление опубликовано');
    }

    /**
     * Отклонить объявление: вернуть автору в черновик с текстом причины.
     */
    public function reject(Request $request, Property $property): RedirectResponse
    {
        $this->assertPending($property);

        $user = Auth::user();
        if ($user->isRealtor() && !$user->isAdmin() && (int) $property->polzovatel_id === (int) $user->id) {
            return redirect()->route('moderation.index')->withErrors(['error' => 'Нельзя отклонить собственное объявление']);
        }

        $validated = $request->validate([
            'prichina_otkaza_mod' => 'required|string|min:5|max:2000',
        ], [
            'prichina_otkaza_mod.required' => 'Укажите причину отказа',
            'prichina_otkaza_mod.min' => 'Причина должна быть не короче 5 символов',
        ]);

        $reasonErrors = TextCensor::fieldError('prichina_otkaza_mod', $validated['prichina_otkaza_mod']);
        if ($reasonErrors !== []) {
            return redirect()
                ->back()
                ->withErrors($reasonErrors)
                ->withInput()
                ->with('moderation_reject_property_id', $property->id);
        }

        $draft = PropertyStatus::where('kod', 'draft')->firstOrFail();

        $property->update([
            'status_obyavleniya_id' => $draft->id,
            'prichina_otkaza_mod' => $validated['prichina_otkaza_mod'],
        ]);

        AppNotifier::propertyModerationRejected($property->fresh());

        return redirect()->route('moderation.index')->with('success', 'Объявление возвращено автору с указанием причины');
    }

    private function assertPending(Property $property): void
    {
        $status = $property->status_obyavleniya ?? $property->status;
        if ($status !== 'pending_review') {
            abort(404);
        }
    }
}
