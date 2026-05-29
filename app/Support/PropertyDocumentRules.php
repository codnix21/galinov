<?php

namespace App\Support;

use App\Models\Property;
use App\Models\UserDocument;

/**
 * Обязательные документы для публикации объявления по типу объекта и сделке (продажа / аренда).
 */
class PropertyDocumentRules
{
    /** @return array<string, string> код => название */
    public static function allTipLabels(): array
    {
        return [
            'passport' => 'Паспорт собственника',
            'egrn' => 'Выписка ЕГРН (объект)',
            'ownership' => 'Документ о праве собственности',
            'egrn_land' => 'Выписка ЕГРН (земельный участок)',
            'egrul' => 'Выписка ЕГРЮЛ (если продавец — юрлицо)',
            'cadastral' => 'Кадастровый паспорт / план',
            'rent_contract' => 'Договор аренды / право сдачи',
        ];
    }

    /** @return list<string> */
    public static function requiredForType(string $tip, string $operatsiya = 'sale'): array
    {
        $operatsiya = $operatsiya === 'rent' ? 'rent' : 'sale';

        if ($operatsiya === 'rent') {
            return match ($tip) {
                'apartment' => ['passport', 'egrn', 'rent_contract'],
                'house' => ['passport', 'egrn', 'cadastral', 'rent_contract'],
                'land' => ['passport', 'egrn_land', 'rent_contract'],
                'commercial' => ['passport', 'egrn', 'egrul', 'rent_contract'],
                default => ['passport', 'egrn', 'rent_contract'],
            };
        }

        return match ($tip) {
            'apartment' => ['passport', 'egrn', 'ownership'],
            'house' => ['passport', 'egrn', 'ownership', 'cadastral'],
            'land' => ['passport', 'egrn_land', 'ownership'],
            'commercial' => ['passport', 'egrn', 'ownership', 'egrul'],
            default => ['passport', 'egrn', 'ownership'],
        };
    }

    public static function requirementsSummary(string $tip, string $operatsiya = 'sale'): string
    {
        $labels = self::allTipLabels();
        $required = self::requiredForType($tip, $operatsiya);
        $names = array_map(fn (string $code) => $labels[$code] ?? $code, $required);

        $deal = $operatsiya === 'rent' ? 'сдачи в аренду' : 'продажи';

        return 'Для ' . $deal . ' нужны: ' . implode(', ', $names) . '.';
    }

    public static function isEgrnStep(string $tip): bool
    {
        return in_array($tip, ['egrn', 'egrn_land'], true);
    }

    /** Первый незавершённый шаг (по порядку чек-листа). */
    public static function currentStepTip(Property $property): ?string
    {
        $required = self::requiredForType(
            $property->tip ?? 'apartment',
            $property->operatsiya ?? 'sale',
        );
        $status = self::statusForProperty($property);

        foreach ($required as $tip) {
            if (!in_array($tip, $status['verified'], true)) {
                return $tip;
            }
        }

        return null;
    }

    /** Черновик — владелец может править документы и отправить снова на модерацию. */
    public static function ownerCanEditDocuments(Property $property): bool
    {
        return ($property->status_obyavleniya ?? $property->status) === 'draft';
    }

    /**
     * Загрузка или замена документа владельцем.
     * В черновике — любой шаг, кроме уже ожидающего проверки; иначе — по порядку чек-листа.
     */
    public static function canOwnerUploadStep(Property $property, string $tip): bool
    {
        $required = self::requiredForType(
            $property->tip ?? 'apartment',
            $property->operatsiya ?? 'sale',
        );
        if (!in_array($tip, $required, true)) {
            return false;
        }

        $status = self::statusForProperty($property);
        // «checking» и «pending» в statusForProperty оба попадают в pending
        if (in_array($tip, $status['pending'], true)) {
            return false;
        }

        if (self::ownerCanEditDocuments($property)) {
            return true;
        }

        return self::isStepAvailable($property, $tip);
    }

    /** Можно загружать этот шаг: все предыдущие уже проверены. */
    public static function isStepAvailable(Property $property, string $tip): bool
    {
        $required = self::requiredForType(
            $property->tip ?? 'apartment',
            $property->operatsiya ?? 'sale',
        );
        $status = self::statusForProperty($property);

        if (!in_array($tip, $required, true) || in_array($tip, $status['verified'], true)) {
            return false;
        }

        foreach ($required as $step) {
            if ($step === $tip) {
                return true;
            }
            if (!in_array($step, $status['verified'], true)) {
                return false;
            }
        }

        return false;
    }

    /** Подпись для заблокированного шага. */
    public static function previousStepLabel(Property $property, string $tip): ?string
    {
        $required = self::requiredForType(
            $property->tip ?? 'apartment',
            $property->operatsiya ?? 'sale',
        );
        $labels = self::allTipLabels();
        $status = self::statusForProperty($property);

        foreach ($required as $step) {
            if ($step === $tip) {
                break;
            }
            if (!in_array($step, $status['verified'], true)) {
                return $labels[$step] ?? $step;
            }
        }

        return null;
    }

    /** Типы документов, для которых доступна автопроверка по кадастровому номеру. */
    public static function egrnTipForProperty(Property $property): ?string
    {
        $required = self::requiredForType(
            $property->tip ?? 'apartment',
            $property->operatsiya ?? 'sale',
        );

        if (in_array('egrn', $required, true)) {
            return 'egrn';
        }

        if (in_array('egrn_land', $required, true)) {
            return 'egrn_land';
        }

        return null;
    }

    /** @return array{verified: list<string>, missing: list<string>, pending: list<string>, rejected: list<string>} */
    public static function statusForProperty(Property $property): array
    {
        $required = self::requiredForType(
            $property->tip ?? 'apartment',
            $property->operatsiya ?? 'sale',
        );

        $docs = UserDocument::query()
            ->where('nedvizhimost_id', $property->id)
            ->where('polzovatel_id', $property->polzovatel_id)
            ->get()
            ->groupBy('tip');

        // Профильные документы (не привязаны к объекту) — могут закрывать шаг "паспорт"
        $profileVerified = UserDocument::query()
            ->whereNull('nedvizhimost_id')
            ->where('polzovatel_id', $property->polzovatel_id)
            ->whereIn('tip', ['passport', 'inn'])
            ->where('status', 'verified')
            ->pluck('tip')
            ->all();
        $profileHasPassport = in_array('passport', $profileVerified, true);

        $verified = [];
        $pending = [];
        $rejected = [];
        $missing = [];

        foreach ($required as $tip) {
            $row = $docs->get($tip)?->sortByDesc('sozdano_at')->first();
            if (!$row) {
                if ($tip === 'passport' && $profileHasPassport) {
                    $verified[] = $tip;
                    continue;
                }
                $missing[] = $tip;
                continue;
            }
            if ($row->status === 'verified') {
                $verified[] = $tip;
            } elseif ($row->status === 'rejected') {
                $rejected[] = $tip;
            } else {
                $pending[] = $tip;
            }
        }

        return compact('verified', 'missing', 'pending', 'rejected');
    }

    public static function isReadyForPublication(Property $property): bool
    {
        $s = self::statusForProperty($property);

        return $s['missing'] === [] && $s['pending'] === [] && $s['rejected'] === [];
    }

    /** Паспорт и выписка ЕГРН — достаточно для одобрения модератором/админом. */
    public static function moderationCoreTips(Property $property): array
    {
        $tips = ['passport'];
        $egrn = self::egrnTipForProperty($property);
        if ($egrn !== null) {
            $tips[] = $egrn;
        }

        return $tips;
    }

    public static function isReadyForStaffModeration(Property $property): bool
    {
        $status = self::statusForProperty($property);

        foreach (self::moderationCoreTips($property) as $tip) {
            if (!in_array($tip, $status['verified'], true)) {
                return false;
            }
            if (in_array($tip, $status['pending'], true) || in_array($tip, $status['rejected'], true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<array{tip: string, label: string, verified: bool, url: ?string, source: ?string}>
     */
    public static function moderationCoreDocumentViews(Property $property): array
    {
        $labels = self::allTipLabels();
        $status = self::statusForProperty($property);

        $docs = UserDocument::query()
            ->where('nedvizhimost_id', $property->id)
            ->where('polzovatel_id', $property->polzovatel_id)
            ->orderByDesc('sozdano_at')
            ->get()
            ->groupBy('tip');

        $profilePassport = UserDocument::query()
            ->whereNull('nedvizhimost_id')
            ->where('polzovatel_id', $property->polzovatel_id)
            ->where('tip', 'passport')
            ->where('status', 'verified')
            ->orderByDesc('sozdano_at')
            ->first();

        $out = [];
        foreach (self::moderationCoreTips($property) as $tip) {
            $verified = in_array($tip, $status['verified'], true);
            $url = null;
            $source = null;
            $note = null;
            $row = $docs->get($tip)?->first();
            if ($row?->put_fajla) {
                $source = 'property';
                if (DocumentStorage::isJsonRegistryFile($row->put_fajla)) {
                    $note = 'Подтверждено по кадастровому номеру';
                    $url = $row->view_url;
                } else {
                    $url = $row->view_url;
                }
            } elseif ($tip === 'passport' && $profilePassport?->put_fajla) {
                $url = $profilePassport->view_url;
                $source = 'profile';
            }

            $out[] = [
                'tip' => $tip,
                'label' => $labels[$tip] ?? $tip,
                'verified' => $verified,
                'url' => $url,
                'source' => $source,
                'note' => $note,
            ];
        }

        return $out;
    }

    /** @return list<string> человекочитаемые названия недостающих документов */
    public static function missingLabels(Property $property): array
    {
        $labels = self::allTipLabels();
        $s = self::statusForProperty($property);
        $out = [];
        foreach (array_merge($s['missing'], $s['pending'], $s['rejected']) as $tip) {
            $out[] = $labels[$tip] ?? $tip;
        }

        return array_values(array_unique($out));
    }
}
