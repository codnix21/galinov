<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\UserDocument;
use App\Services\DocumentVerificationService;
use App\Support\DocumentDataFields;
use App\Support\PropertyDocumentRules;
use App\Support\PropertyListingAuthor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Документы на объект недвижимости — загружает владелец объявления перед публикацией.
 */
class PropertyDocumentController extends Controller
{
    public function show(Request $request, Property $property): View|RedirectResponse
    {
        if (!$this->canView($request, $property)) {
            abort(403);
        }

        $property->loadMissing('user.personalData');

        $docStatus = PropertyDocumentRules::statusForProperty($property);
        $required = PropertyDocumentRules::requiredForType(
            $property->tip ?? 'apartment',
            $property->operatsiya ?? 'sale',
        );
        $labels = PropertyDocumentRules::allTipLabels();
        $currentStepTip = PropertyDocumentRules::currentStepTip($property);

        $documents = UserDocument::where('nedvizhimost_id', $property->id)
            ->orderByDesc('sozdano_at')
            ->get()
            ->groupBy('tip');

        $profileVerified = UserDocument::query()
            ->whereNull('nedvizhimost_id')
            ->where('polzovatel_id', $property->polzovatel_id)
            ->whereIn('tip', ['passport', 'inn'])
            ->whereStatusKod('verified')
            ->pluck('tip')
            ->all();

        $profilePassportDocument = UserDocument::query()
            ->whereNull('nedvizhimost_id')
            ->where('polzovatel_id', $property->polzovatel_id)
            ->where('tip', 'passport')
            ->whereStatusKod('verified')
            ->orderByDesc('sozdano_at')
            ->first();

        $isOwner = $this->isOwner($request, $property);
        $canEditDocuments = $isOwner && PropertyDocumentRules::ownerCanEditDocuments($property);
        $verifiedCount = count($docStatus['verified']);
        $totalRequired = count($required);

        return view('properties.documents', [
            'property' => $property,
            'docStatus' => $docStatus,
            'required' => $required,
            'labels' => $labels,
            'documents' => $documents,
            'profilePassportVerified' => in_array('passport', $profileVerified, true),
            'profileInnVerified' => in_array('inn', $profileVerified, true),
            'profilePassportDocument' => $profilePassportDocument,
            'ready' => PropertyDocumentRules::isReadyForPublication($property),
            'canUpload' => $isOwner,
            'canEditDocuments' => $canEditDocuments,
            'isOwner' => $isOwner,
            'wasModerationRejected' => $canEditDocuments && !empty($property->prichina_otkaza_mod),
            'requirementsSummary' => PropertyDocumentRules::requirementsSummary(
                $property->tip ?? 'apartment',
                $property->operatsiya ?? 'sale',
            ),
            'currentStepTip' => $currentStepTip,
            'verifiedCount' => $verifiedCount,
            'totalRequired' => $totalRequired,
            'rosreestrMapUrl' => app(DocumentVerificationService::class)
                ->publicMapUrl($property->kadastrovy_nomer),
        ]);
    }

    public function store(Request $request, Property $property): RedirectResponse
    {
        if (!$this->isOwner($request, $property)) {
            abort(403, 'Документы загружает владелец объявления.');
        }

        $allowedTips = PropertyDocumentRules::requiredForType(
            $property->tip ?? 'apartment',
            $property->operatsiya ?? 'sale',
        );

        $tip = $request->string('tip')->toString();
        if (!in_array($tip, $allowedTips, true)) {
            abort(422);
        }

        $validated = $request->validate(array_merge([
            'tip' => ['required', 'string', 'in:' . implode(',', $allowedTips)],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:15360'],
        ], DocumentDataFields::validationRules($tip)), [], DocumentDataFields::validationAttributes($tip));

        if (!PropertyDocumentRules::canOwnerUploadStep($property, $validated['tip'])) {
            $prev = PropertyDocumentRules::previousStepLabel($property, $validated['tip']);

            return redirect()->route('properties.documents', $property)
                ->withErrors(['error' => $prev
                    ? 'Сначала пройдите шаг: «' . $prev . '».'
                    : 'Этот шаг на проверке или недоступен.']);
        }

        UserDocument::query()
            ->where('nedvizhimost_id', $property->id)
            ->where('tip', $validated['tip'])
            ->delete();

        $dannye = DocumentDataFields::extractFromRequest($request, $validated['tip']);
        $dannye = $this->normalizeDocumentDataCadastral($property, $validated['tip'], $dannye);

        $path = $request->file('file')->store(
            'documents/property-' . $property->id,
            'public'
        );

        $dataSummary = DocumentDataFields::summaryForComment($validated['tip'], $dannye);

        $document = UserDocument::create([
            'polzovatel_id' => $property->polzovatel_id,
            'nedvizhimost_id' => $property->id,
            'tip' => $validated['tip'],
            'tip_obekta' => $property->tip,
            'nazvanie' => PropertyDocumentRules::allTipLabels()[$validated['tip']] ?? null,
            'put_fajla' => $path,
            'status' => 'pending',
            'dannye_json' => array_filter($dannye, fn ($v) => $v !== null && $v !== ''),
            'kommentariy_mod' => $dataSummary !== '' ? $dataSummary : null,
        ]);

        app(DocumentVerificationService::class)->submitForExternalCheck($document);

        $label = PropertyDocumentRules::allTipLabels()[$validated['tip']] ?? 'Документ';

        $msg = PropertyDocumentRules::ownerCanEditDocuments($property)
            ? '«' . $label . '» обновлён и отправлен на проверку.'
            : '«' . $label . '» загружен. Переходите к следующему шагу.';

        return redirect()->route('properties.documents', $property)->with('success', $msg);
    }

    public function verifyEgrn(Request $request, Property $property): RedirectResponse
    {
        if (!$this->isOwner($request, $property)) {
            abort(403, 'Проверку по ЕГРН выполняет владелец объявления.');
        }

        $egrnTip = PropertyDocumentRules::egrnTipForProperty($property);
        if ($egrnTip === null) {
            abort(400);
        }

        if (!PropertyDocumentRules::canOwnerUploadStep($property, $egrnTip)) {
            $prev = PropertyDocumentRules::previousStepLabel($property, $egrnTip);

            return redirect()->route('properties.documents', $property)
                ->withErrors(['kadastrovy_nomer' => $prev
                    ? 'Сначала пройдите шаг: «' . $prev . '».'
                    : 'Этот шаг на проверке или недоступен.']);
        }

        UserDocument::query()
            ->where('nedvizhimost_id', $property->id)
            ->where('tip', $egrnTip)
            ->delete();

        $validated = $request->validate(
            DocumentDataFields::validationRules($egrnTip),
            [],
            DocumentDataFields::validationAttributes($egrnTip),
        );

        $dannye = DocumentDataFields::extractFromRequest($request, $egrnTip);
        $dannye = $this->normalizeDocumentDataCadastral($property, $egrnTip, $dannye);

        if (empty($dannye['kadastrovy_nomer'])) {
            return redirect()->route('properties.documents', $property)
                ->withErrors(['dannye.kadastrovy_nomer' => 'Укажите кадастровый номер.']);
        }

        $result = app(DocumentVerificationService::class)->verifyByCadastralNumber(
            $property,
            (string) $dannye['kadastrovy_nomer'],
            $dannye,
        );

        if (!$result['ok']) {
            return redirect()->route('properties.documents', $property)
                ->withErrors(['kadastrovy_nomer' => $result['message']]);
        }

        return redirect()->route('properties.documents', $property)
            ->with('success', $result['message'] . ' Переходите к следующему шагу.');
    }

    private function canView(Request $request, Property $property): bool
    {
        $user = $request->user();
        if ($user->isStaff() && ($property->status_obyavleniya ?? $property->status) === 'pending_review') {
            return true;
        }

        return $user->isAdmin() || $this->canManageDocuments($request, $property);
    }

    private function isOwner(Request $request, Property $property): bool
    {
        return $this->canManageDocuments($request, $property);
    }

    private function canManageDocuments(Request $request, Property $property): bool
    {
        $user = $request->user();

        return PropertyListingAuthor::canManage($user, $property);
    }

    /**
     * @param  array<string, string|null>  $dannye
     * @return array<string, string|null>
     */
    private function normalizeDocumentDataCadastral(Property $property, string $tip, array $dannye): array
    {
        if (!in_array($tip, ['egrn', 'egrn_land', 'cadastral'], true) || empty($dannye['kadastrovy_nomer'])) {
            return $dannye;
        }

        $normalized = app(DocumentVerificationService::class)
            ->normalizeCadastralNumber($dannye['kadastrovy_nomer']);
        if ($normalized !== null) {
            $dannye['kadastrovy_nomer'] = $normalized;
            $property->update(['kadastrovy_nomer' => $normalized]);
        }

        return $dannye;
    }
}
