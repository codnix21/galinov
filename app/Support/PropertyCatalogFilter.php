<?php

namespace App\Support;

use App\Models\Property;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Фильтры и сортировка каталога объявлений (публичная витрина).
 */
class PropertyCatalogFilter
{
    public const SORT_NEWEST = 'newest';

    public const SORT_PRICE_ASC = 'price_asc';

    public const SORT_PRICE_DESC = 'price_desc';

    public const SORT_AREA_DESC = 'area_desc';

    /** @return array<string, string> */
    public static function sortOptions(): array
    {
        return [
            self::SORT_NEWEST => 'Сначала новые',
            self::SORT_PRICE_ASC => 'Цена: по возрастанию',
            self::SORT_PRICE_DESC => 'Цена: по убыванию',
            self::SORT_AREA_DESC => 'Площадь: по убыванию',
        ];
    }

    public static function apply(Builder $query, Request $request): Builder
    {
        if ($request->filled('search')) {
            self::applySmartSearch($query, $request->string('search')->trim()->toString());
        }

        if ($request->filled('type')) {
            $query->where('tip', $request->string('type')->toString());
        }

        if ($request->filled('operation')) {
            $query->where('operatsiya', $request->string('operation')->toString());
        }

        if ($request->filled('city_id')) {
            $query->where('gorod_id', (int) $request->input('city_id'));
        }

        if ($request->filled('min_price')) {
            $query->where('tsena', '>=', (float) $request->input('min_price'));
        }

        if ($request->filled('max_price')) {
            $query->where('tsena', '<=', (float) $request->input('max_price'));
        }

        if ($request->filled('min_rooms')) {
            $query->where('komnaty', '>=', (int) $request->input('min_rooms'));
        }

        if ($request->filled('max_rooms')) {
            $query->where('komnaty', '<=', (int) $request->input('max_rooms'));
        }

        if ($request->filled('min_area')) {
            $query->where('ploshchad', '>=', (int) $request->input('min_area'));
        }

        if ($request->filled('max_area')) {
            $query->where('ploshchad', '<=', (int) $request->input('max_area'));
        }

        if ($request->filled('min_floor')) {
            $query->where('etazh', '>=', (int) $request->input('min_floor'));
        }

        if ($request->filled('max_floor')) {
            $query->where('etazh', '<=', (int) $request->input('max_floor'));
        }

        if ($request->boolean('has_photos')) {
            $query->whereHas('images');
        }

        self::applyHouseFilters($query, $request);

        return self::applySort($query, $request->string('sort')->toString());
    }

    public static function applyHouseFilters(Builder $query, Request $request): Builder
    {
        $houseOnlyKeys = ['tip_doma', 'est_tsokol', 'garazh', 'parking', 'internet', 'min_ploshchad_uchastka', 'max_ploshchad_uchastka'];
        $hasHouseFilter = false;
        foreach ($houseOnlyKeys as $key) {
            if ($key === 'est_tsokol' || $key === 'garazh' || $key === 'parking' || $key === 'internet') {
                if ($request->boolean($key)) {
                    $hasHouseFilter = true;
                    break;
                }
            } elseif ($request->filled($key)) {
                $hasHouseFilter = true;
                break;
            }
        }

        $type = $request->string('type')->toString();
        if ($type !== '' && $type !== 'house') {
            return $query;
        }

        if ($type !== 'house' && !$hasHouseFilter) {
            return $query;
        }

        $query->where('tip', 'house');

        if ($request->filled('tip_doma')) {
            $query->where('tip_doma', $request->string('tip_doma')->toString());
        }

        if ($request->boolean('est_tsokol')) {
            $query->where('est_tsokol', true);
        }

        if ($request->boolean('garazh')) {
            $query->where('garazh', true);
        }

        if ($request->boolean('parking')) {
            $query->where('parking', true);
        }

        if ($request->boolean('internet')) {
            $query->where('internet', true);
        }

        if ($request->filled('min_ploshchad_uchastka')) {
            $query->where('ploshchad_uchastka', '>=', (float) $request->input('min_ploshchad_uchastka'));
        }

        if ($request->filled('max_ploshchad_uchastka')) {
            $query->where('ploshchad_uchastka', '<=', (float) $request->input('max_ploshchad_uchastka'));
        }

        return $query;
    }

    /** Поиск по словам, номеру объявления, адресу и описанию. */
    public static function applySmartSearch(Builder $query, string $search): Builder
    {
        $search = trim($search);
        if ($search === '') {
            return $query;
        }

        if (preg_match('/^(#|№)?\s*(\d+)$/u', $search, $m)) {
            return $query->where('id', (int) $m[2]);
        }

        $tokens = preg_split('/\s+/u', $search, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return $query->where(function ($outer) use ($search, $tokens) {
            $outer->where('nazvanie', 'like', "%{$search}%")
                ->orWhere('opisanie', 'like', "%{$search}%")
                ->orWhere('adres_ulitsy', 'like', "%{$search}%")
                ->orWhereHas('cityRelation', fn ($cq) => $cq->where('nazvanie', 'like', "%{$search}%"));

            if (count($tokens) > 1) {
                $outer->orWhere(function ($and) use ($tokens) {
                    foreach ($tokens as $token) {
                        $like = '%'.$token.'%';
                        $and->where(function ($q) use ($like) {
                            $q->where('nazvanie', 'like', $like)
                                ->orWhere('opisanie', 'like', $like)
                                ->orWhere('adres_ulitsy', 'like', $like)
                                ->orWhereHas('cityRelation', fn ($cq) => $cq->where('nazvanie', 'like', $like));
                        });
                    }
                });
            }
        });
    }

    public static function applySort(Builder $query, string $sort): Builder
    {
        return match ($sort) {
            self::SORT_PRICE_ASC => $query->orderBy('tsena')->orderByDesc('id'),
            self::SORT_PRICE_DESC => $query->orderByDesc('tsena')->orderByDesc('id'),
            self::SORT_AREA_DESC => $query->orderByRaw('ploshchad IS NULL')->orderByDesc('ploshchad')->orderByDesc('id'),
            default => $query->latest('sozdano_at')->latest('id'),
        };
    }

    /** Фильтры для «мои объявления» риэлтора (статус, поиск). */
    public static function applyRealtorPortfolio(Builder $query, Request $request, int $userId): Builder
    {
        $query->where('polzovatel_id', $userId);

        if ($request->filled('status')) {
            $statusKod = $request->string('status')->toString();
            $statusId = \App\Models\PropertyStatus::idFor($statusKod);
            if ($statusId !== null) {
                $query->where('status_obyavleniya_id', $statusId);
            }
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->trim()->toString();
            $query->where(function ($q) use ($search) {
                $q->where('nazvanie', 'like', "%{$search}%")
                    ->orWhere('adres_ulitsy', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('tip', $request->string('type')->toString());
        }

        if ($request->filled('operation')) {
            $query->where('operatsiya', $request->string('operation')->toString());
        }

        return $query->latest('obnovleno_at')->latest('id');
    }

    /** Фильтры списка объявлений в админке. */
    public static function applyAdminList(Builder $query, Request $request): Builder
    {
        if ($request->filled('search')) {
            $search = $request->string('search')->trim()->toString();
            $escaped = addcslashes($search, '%_\\');
            $query->where(function ($q) use ($escaped) {
                $q->where('nazvanie', 'like', "%{$escaped}%")
                    ->orWhere('opisanie', 'like', "%{$escaped}%")
                    ->orWhere('adres_ulitsy', 'like', "%{$escaped}%")
                    ->orWhereHas('cityRelation', fn ($cq) => $cq->where('nazvanie', 'like', "%{$escaped}%"));
            });
        }

        if ($request->filled('type')) {
            $query->where('tip', $request->string('type')->toString());
        }

        if ($request->filled('operation')) {
            $query->where('operatsiya', $request->string('operation')->toString());
        }

        if ($request->filled('status')) {
            $statusId = \App\Models\PropertyStatus::idFor($request->string('status')->toString());
            if ($statusId !== null) {
                $query->where('status_obyavleniya_id', $statusId);
            }
        }

        if ($request->filled('city_id')) {
            $query->where('gorod_id', (int) $request->input('city_id'));
        }

        if ($request->filled('min_price')) {
            $query->where('tsena', '>=', (float) $request->input('min_price'));
        }

        if ($request->filled('max_price')) {
            $query->where('tsena', '<=', (float) $request->input('max_price'));
        }

        $sort = $request->string('sort')->toString();

        return self::applySort($query, $sort !== '' ? $sort : self::SORT_NEWEST);
    }

    /** @return list<string> Имена query-параметров активных фильтров каталога */
    public static function activeFilterKeys(Request $request): array
    {
        $keys = [];
        foreach ([
            'search', 'type', 'operation', 'city_id', 'min_price', 'max_price',
            'min_rooms', 'max_rooms', 'min_area', 'max_area', 'min_floor', 'max_floor',
            'has_photos', 'sort', 'tip_doma', 'min_ploshchad_uchastka', 'max_ploshchad_uchastka',
            'est_tsokol', 'garazh', 'parking', 'internet',
        ] as $key) {
            if (in_array($key, ['est_tsokol', 'garazh', 'parking', 'internet'], true)) {
                if ($request->boolean($key)) {
                    $keys[] = $key;
                }
                continue;
            }
            if ($key === 'sort') {
                if ($request->filled('sort') && $request->string('sort')->toString() !== self::SORT_NEWEST) {
                    $keys[] = $key;
                }
                continue;
            }
            if ($key === 'has_photos') {
                if ($request->boolean('has_photos')) {
                    $keys[] = $key;
                }
                continue;
            }
            if ($request->filled($key)) {
                $keys[] = $key;
            }
        }

        return $keys;
    }
}
