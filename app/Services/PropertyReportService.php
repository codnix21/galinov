<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Property;
use App\Models\PropertyInfoRequest;
use App\Models\PropertyInquiry;
use App\Models\RequestStatus;
use App\Models\User;
use App\Models\UserDocument;
use App\Models\ZhurnalIzmeneniy;
use App\Support\DocumentDataFields;
use App\Support\PropertyDocumentRules;
use App\Support\PropertyHouseAttributes;
use App\Support\PropertyLandAttributes;
use App\Support\PropertyListingAuthor;
use App\Support\UserProfileDocuments;
use Illuminate\Support\Facades\DB;

class PropertyReportService
{
    public static function panoramaUrl(Property $property): ?string
    {
        $lat = $property->geo_shirota ?? null;
        $lng = $property->geo_dolgota ?? null;

        if ($lat === null || $lng === null) {
            $address = trim(($property->gorod ?? '').', '.($property->adres_ulitsy ?? ''));
            if ($address === ',' || $address === '') {
                return null;
            }

            return 'https://yandex.ru/maps/?mode=panorama&text='.rawurlencode($address);
        }

        return sprintf('https://yandex.ru/maps/?ll=%s,%s&z=17&mode=panorama', $lng, $lat);
    }

    /**
     * @return array<string, mixed>
     */
    public static function build(Property $property, ?User $viewer = null): array
    {
        $property->loadMissing(['user.personalData', 'realtor', 'images', 'cityRelation', 'statusRelation', 'owners.user']);

        PropertyOwnersService::ensureDefaultOwner($property);
        $property->load('owners.user');

        $docStatus = PropertyDocumentRules::statusForProperty($property);
        $required = PropertyDocumentRules::requiredForType(
            $property->tip ?? 'apartment',
            $property->operatsiya ?? 'sale',
        );
        $labels = PropertyDocumentRules::allTipLabels();
        $verifiedCount = count($docStatus['verified']);
        $totalRequired = count($required);

        $documents = UserDocument::query()
            ->where('nedvizhimost_id', $property->id)
            ->orderByDesc('sozdano_at')
            ->get();

        $contracts = Contract::query()
            ->with(['owner', 'buyer', 'realtor', 'statusRelation', 'sellers.user'])
            ->where('nedvizhimost_id', $property->id)
            ->orderByDesc('sozdano_at')
            ->get();

        $inquiriesCount = PropertyInquiry::query()
            ->where('nedvizhimost_id', $property->id)
            ->count();

        $isOwnerOrStaff = $viewer && (
            PropertyListingAuthor::canManage($viewer, $property)
            || $viewer->isStaff()
        );

        $canSeeFullHistory = self::canSeeFullHistory($property, $viewer);
        $history = $canSeeFullHistory
            ? ZhurnalIzmeneniy::istoriyaDlyaNedvizhimosti($property, 80)
            : collect();

        $ownerId = (int) ($property->polzovatel_id ?? 0);
        $profilePassport = $ownerId > 0 && UserProfileDocuments::passportVerified($ownerId);
        $profileVerifiedTips = $ownerId > 0
            ? UserProfileDocuments::verifiedTips($ownerId)
            : [];

        $rosreestrUrl = app(DocumentVerificationService::class)
            ->publicMapUrl($property->kadastrovy_nomer);

        $st = $property->status_obyavleniya ?? $property->status;
        $docsReady = PropertyDocumentRules::isReadyForPublication($property);
        $canManage = $viewer && PropertyListingAuthor::canManage($viewer, $property);
        $canPublishToModeration = $canManage && $st === 'draft' && $docsReady;

        $favoritesCount = (int) DB::table('izbrannoe')
            ->where('nedvizhimost_id', $property->id)
            ->count();

        $infoRequestsCount = PropertyInfoRequest::query()
            ->where('nedvizhimost_id', $property->id)
            ->count();

        $recentInquiries = collect();
        $recentInfoRequests = collect();
        if ($isOwnerOrStaff) {
            $recentInquiries = PropertyInquiry::query()
                ->where('nedvizhimost_id', $property->id)
                ->orderByDesc('sozdano_at')
                ->limit(10)
                ->get();

            $infoQuery = PropertyInfoRequest::with(['client', 'messages'])
                ->where('nedvizhimost_id', $property->id);
            if ($viewer && !$viewer->isStaff()) {
                $infoQuery->where('polzovatel_id', $viewer->id);
            }
            $recentInfoRequests = $infoQuery->orderByDesc('sozdano_at')->limit(10)->get();
        }

        $statusVersions = $canManage
            ? ProcessVersionService::history('property', (int) $property->id)
            : collect();

        $documentRows = self::documentReportRows($property, $required, $docStatus, (bool) $isOwnerOrStaff);
        $houseRows = PropertyHouseAttributes::displayRows($property);
        $landRows = PropertyLandAttributes::displayRows($property);

        return [
            'property' => $property,
            'statusCode' => $st,
            'statusLabel' => $property->status_name ?? $st,
            'docStatus' => $docStatus,
            'docLabels' => $labels,
            'requiredDocs' => $required,
            'verifiedCount' => $verifiedCount,
            'totalRequired' => $totalRequired,
            'docsPercent' => $totalRequired > 0 ? (int) round(($verifiedCount / $totalRequired) * 100) : 0,
            'documents' => $documents,
            'documentRows' => $documentRows,
            'contracts' => $contracts,
            'inquiriesCount' => $inquiriesCount,
            'recentInquiries' => $recentInquiries,
            'infoRequestsCount' => $infoRequestsCount,
            'recentInfoRequests' => $recentInfoRequests,
            'favoritesCount' => $favoritesCount,
            'history' => $history,
            'canSeeFullHistory' => $canSeeFullHistory,
            'profilePassport' => $profilePassport,
            'profileVerifiedTips' => $profileVerifiedTips,
            'panoramaUrl' => self::panoramaUrl($property),
            'rosreestrUrl' => $rosreestrUrl,
            'requirementsSummary' => PropertyDocumentRules::requirementsSummary(
                $property->tip ?? 'apartment',
                $property->operatsiya ?? 'sale',
            ),
            'isOwnerOrStaff' => $isOwnerOrStaff,
            'canManage' => (bool) $canManage,
            'docsReady' => $docsReady,
            'canPublishToModeration' => (bool) $canPublishToModeration,
            'statusVersions' => $statusVersions,
            'houseRows' => $houseRows,
            'landRows' => $landRows,
            'imagesCount' => $property->images->count(),
            'ownersCount' => $property->owners->count(),
            'isRent' => ($property->operatsiya ?? '') === 'rent',
        ];
    }

    /**
     * @param  list<string>  $required
     * @param  array{verified: list<string>, pending: list<string>, rejected: list<string>, missing: list<string>}  $docStatus
     * @return list<array{tip: string, label: string, state: string, state_label: string, kommentariy_mod: ?string, provereno_at: ?\Illuminate\Support\Carbon, has_file: bool, data_lines: list<array{label: string, value: string}>}>
     */
    private static function documentReportRows(
        Property $property,
        array $required,
        array $docStatus,
        bool $includeSensitive,
    ): array {
        $labels = PropertyDocumentRules::allTipLabels();
        $docsByTip = UserDocument::query()
            ->where('nedvizhimost_id', $property->id)
            ->orderByDesc('sozdano_at')
            ->get()
            ->groupBy('tip');

        $profilePassport = null;
        if ($includeSensitive) {
            $profilePassport = UserDocument::query()
                ->whereNull('nedvizhimost_id')
                ->where('polzovatel_id', $property->polzovatel_id)
                ->where('tip', 'passport')
                ->whereStatusKod('verified')
                ->orderByDesc('sozdano_at')
                ->first();
        }

        $rows = [];
        foreach ($required as $tip) {
            $row = $docsByTip->get($tip)?->first();
            $state = 'missing';
            $stateLabel = 'Не загружен';
            if (in_array($tip, $docStatus['verified'], true)) {
                $state = 'verified';
                $stateLabel = 'Проверен';
            } elseif (in_array($tip, $docStatus['rejected'], true)) {
                $state = 'rejected';
                $stateLabel = 'Отклонён';
            } elseif (in_array($tip, $docStatus['pending'], true)) {
                $state = 'pending';
                $stateLabel = 'На проверке';
            }

            $dataLines = [];
            if ($includeSensitive) {
                if ($tip === 'passport') {
                    $property->loadMissing('user.personalData');
                    $dataLines = DocumentDataFields::personalDataLines($property->user?->personalData);
                    if ($dataLines === [] && $profilePassport?->dannye_json) {
                        $dataLines = DocumentDataFields::displayLines('passport', $profilePassport->dannye_json);
                    }
                } elseif ($row?->dannye_json) {
                    $dataLines = DocumentDataFields::displayLines($tip, $row->dannye_json, $property);
                }
            }

            $rows[] = [
                'tip' => $tip,
                'label' => $labels[$tip] ?? $tip,
                'state' => $state,
                'state_label' => $stateLabel,
                'kommentariy_mod' => $row?->kommentariy_mod,
                'provereno_at' => $row?->provereno_at,
                'has_file' => (bool) ($row?->put_fajla),
                'data_lines' => $dataLines,
            ];
        }

        return $rows;
    }

    public static function inquiryStatusLabel(PropertyInquiry $inquiry): string
    {
        return RequestStatus::nazvanieFor('inquiry', $inquiry->status_zayavki_id)
            ?? match ($inquiry->status) {
                'contacted' => 'Связались',
                'closed' => 'Закрыта',
                default => 'Новая',
            };
    }

    public static function canView(Property $property, ?User $viewer): bool
    {
        $st = $property->status_obyavleniya ?? $property->status;

        if ($st === 'active') {
            return true;
        }

        if (!$viewer) {
            return false;
        }

        if ($viewer->isAdmin() || $viewer->isRealtor()) {
            return true;
        }

        return PropertyListingAuthor::canManage($viewer, $property);
    }

    public static function canSeeFullHistory(Property $property, ?User $viewer): bool
    {
        if (!$viewer) {
            return false;
        }

        if ($viewer->isStaff()) {
            return true;
        }

        $ownerId = (int) ($property->polzovatel_id ?? 0);

        return (int) $viewer->id === $ownerId
            || PropertyListingAuthor::canManage($viewer, $property);
    }
}
