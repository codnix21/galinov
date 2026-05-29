<?php

namespace App\Support;

use App\Models\Property;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Похожие объявления при пустой выдаче — ослабленные критерии.
 */
class PropertyCatalogSimilar
{
    public static function hasActiveFilters(Request $request): bool
    {
        return count(PropertyCatalogFilter::activeFilterKeys($request)) > 0;
    }

    /**
     * @return Builder<Property>
     */
    public static function query(Request $request, ?int $activeStatusId): Builder
    {
        $query = Property::query();
        if ($activeStatusId !== null) {
            $query->where('status_obyavleniya_id', $activeStatusId);
        } else {
            $query->whereRaw('1 = 0');
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
            $query->where('tsena', '>=', (float) $request->input('min_price') * 0.8);
        }
        if ($request->filled('max_price')) {
            $query->where('tsena', '<=', (float) $request->input('max_price') * 1.2);
        }

        if ($request->filled('min_rooms')) {
            $query->where('komnaty', '>=', max(0, (int) $request->input('min_rooms') - 1));
        }
        if ($request->filled('max_rooms')) {
            $query->where('komnaty', '<=', (int) $request->input('max_rooms') + 1);
        }

        if ($request->filled('min_area')) {
            $query->where('ploshchad', '>=', (int) ((float) $request->input('min_area') * 0.85));
        }
        if ($request->filled('max_area')) {
            $query->where('ploshchad', '<=', (int) ((float) $request->input('max_area') * 1.15));
        }

        if ($request->filled('search')) {
            $tokens = preg_split('/\s+/u', $request->string('search')->trim()->toString(), 2, PREG_SPLIT_NO_EMPTY);
            if (!empty($tokens[0])) {
                $token = $tokens[0];
                $like = '%'.$token.'%';
                $query->where(function ($q) use ($like) {
                    $q->where('nazvanie', 'like', $like)
                        ->orWhere('opisanie', 'like', $like)
                        ->orWhereHas('cityRelation', fn ($cq) => $cq->where('nazvanie', 'like', $like));
                });
            }
        }

        return PropertyCatalogFilter::applySort($query, $request->string('sort')->toString());
    }

    /** @return array<string, scalar|null> */
    public static function captureFilters(Request $request): array
    {
        $keys = [
            'search', 'type', 'operation', 'city_id', 'min_price', 'max_price',
            'min_rooms', 'max_rooms', 'min_area', 'max_area', 'min_floor', 'max_floor',
            'has_photos', 'sort', 'tip_doma', 'min_ploshchad_uchastka', 'max_ploshchad_uchastka',
            'est_tsokol', 'garazh', 'parking', 'internet',
        ];
        $out = [];
        foreach ($keys as $key) {
            if ($request->boolean($key) || $request->filled($key)) {
                $out[$key] = $request->input($key);
            }
        }

        return $out;
    }
}
