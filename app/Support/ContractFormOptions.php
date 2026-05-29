<?php

namespace App\Support;

use App\Models\Property;
use App\Models\PropertyStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Подготовка списков и API-поиска для форм создания договора.
 */
class ContractFormOptions
{
    public static function activePropertiesQuery(): Builder
    {
        $activeId = PropertyStatus::idFor('active');
        if ($activeId === null) {
            PropertyStatus::forgetKodIdCache();
            $activeId = PropertyStatus::idFor('active');
        }

        return Property::query()
            ->when($activeId !== null, fn ($q) => $q->where('status_obyavleniya_id', $activeId))
            ->when($activeId === null, fn ($q) => $q->whereRaw('1 = 0'))
            ->whereIn('operatsiya', ['sale', 'rent']);
    }

    public static function propertyItem(Property $prop): array
    {
        $op = $prop->operatsiya ?? $prop->operation;
        $opLabel = $op === 'rent' ? 'Аренда' : 'Продажа';
        $city = $prop->gorod ?? '';

        return [
            'value' => (string) $prop->id,
            'label' => sprintf(
                '[%s] %s — %s ₽',
                $opLabel,
                $prop->nazvanie ?? $prop->title ?? 'Без названия',
                number_format((float) ($prop->tsena ?? $prop->price ?? 0), 0, ',', ' ')
            ),
            'hint' => trim($city . ($prop->adres_ulitsy ? ', ' . $prop->adres_ulitsy : '')),
            'data' => [
                'operation' => $op,
                'owner_id' => (string) ($prop->polzovatel_id ?? $prop->user_id ?? ''),
            ],
        ];
    }

    public static function userItem(User $user): array
    {
        return [
            'value' => (string) $user->id,
            'label' => trim($user->familia . ' ' . $user->imya . ' ' . ($user->otchestvo ?? '')),
            'hint' => $user->email_polzovatela ?? $user->email ?? '',
        ];
    }

    public static function applyFioSearch(Builder $query, string $q): void
    {
        $like = '%' . $q . '%';
        $query->where(function ($sub) use ($like) {
            $sub->whereRaw("CONCAT(familia, ' ', imya, ' ', COALESCE(otchestvo, '')) LIKE ?", [$like])
                ->orWhere('familia', 'like', $like)
                ->orWhere('imya', 'like', $like)
                ->orWhere('otchestvo', 'like', $like)
                ->orWhere('email_polzovatela', 'like', $like);
        });
    }

    public static function searchProperties(string $q): array
    {
        $query = self::activePropertiesQuery()->with('cityRelation')->orderBy('nazvanie');

        if ($q !== '') {
            $like = '%' . $q . '%';
            $query->where(function ($sub) use ($like) {
                $sub->where('nazvanie', 'like', $like)
                    ->orWhere('adres_ulitsy', 'like', $like)
                    ->orWhereHas('cityRelation', fn ($cq) => $cq->where('nazvanie', 'like', $like));
            });
        }

        return $query->limit(40)->get()->map(fn (Property $p) => self::propertyItem($p))->values()->all();
    }

    public static function searchClients(string $q): array
    {
        $query = User::whereHas('roleRelation', fn ($r) => $r->where('kod', 'client'))
            ->orderBy('familia')->orderBy('imya');

        if ($q !== '') {
            self::applyFioSearch($query, $q);
        }

        return $query->limit(40)->get()->map(fn (User $u) => self::userItem($u))->values()->all();
    }

    public static function searchRealtors(string $q): array
    {
        $query = User::whereHas('roleRelation', fn ($r) => $r->whereIn('kod', ['realtor', 'admin']))
            ->orderBy('familia')->orderBy('imya');

        if ($q !== '') {
            self::applyFioSearch($query, $q);
        }

        return $query->limit(40)->get()->map(fn (User $u) => self::userItem($u))->values()->all();
    }
}
