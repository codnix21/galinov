<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Property;
use App\Models\PropertyInquiry;
use App\Models\User;
use App\Models\UserDocument;
use App\Models\ZhurnalIzmeneniy;
use App\Support\PropertyDocumentRules;
use App\Support\PropertyListingAuthor;
use App\Support\UserProfileDocuments;

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
        $property->loadMissing(['user', 'realtor', 'images', 'cityRelation', 'statusRelation']);

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
            ->with(['owner', 'buyer', 'realtor', 'statusRelation'])
            ->where('nedvizhimost_id', $property->id)
            ->orderByDesc('sozdano_at')
            ->get();

        $inquiriesCount = PropertyInquiry::query()
            ->where('nedvizhimost_id', $property->id)
            ->count();

        $canSeeFullHistory = self::canSeeFullHistory($property, $viewer);
        $history = $canSeeFullHistory
            ? ZhurnalIzmeneniy::istoriyaDlyaNedvizhimosti($property, 80)
            : collect();

        $ownerId = (int) ($property->polzovatel_id ?? 0);
        $profilePassport = $ownerId > 0 && UserProfileDocuments::passportVerified($ownerId);

        $rosreestrUrl = app(DocumentVerificationService::class)
            ->publicMapUrl($property->kadastrovy_nomer);

        $st = $property->status_obyavleniya ?? $property->status;

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
            'contracts' => $contracts,
            'inquiriesCount' => $inquiriesCount,
            'history' => $history,
            'canSeeFullHistory' => $canSeeFullHistory,
            'profilePassport' => $profilePassport,
            'panoramaUrl' => self::panoramaUrl($property),
            'rosreestrUrl' => $rosreestrUrl,
            'requirementsSummary' => PropertyDocumentRules::requirementsSummary(
                $property->tip ?? 'apartment',
                $property->operatsiya ?? 'sale',
            ),
            'isOwnerOrStaff' => $viewer && (
                PropertyListingAuthor::canManage($viewer, $property)
                || $viewer->isStaff()
            ),
        ];
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
