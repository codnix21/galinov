<?php

namespace App\Services;

use App\Models\Property;
use App\Models\PropertyStatus;
use App\Models\SavedSearch;
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Support\PropertyCatalogFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SavedSearchService
{
    /** @param  array<string, mixed>  $filters */
    public static function store(User $user, string $name, array $filters, bool $notify = true): SavedSearch
    {
        return SavedSearch::create([
            'polzovatel_id' => $user->id,
            'nazvanie' => $name,
            'filtry' => self::normalizeFilters($filters),
            'uvedomleniya' => $notify,
            'poslednyaya_proverka_at' => now(),
        ]);
    }

    /** @return array<string, mixed> */
    public static function normalizeFilters(array $filters): array
    {
        $allowed = PropertyCatalogFilter::activeFilterKeys(
            Request::create('/', 'GET', $filters)
        );

        return array_intersect_key($filters, array_flip($allowed));
    }

    public static function notifyMatches(SavedSearch $search): int
    {
        if (!$search->uvedomleniya) {
            return 0;
        }

        $activeId = PropertyStatus::idFor('active');
        if ($activeId === null) {
            return 0;
        }

        $since = $search->poslednyaya_proverka_at ?? $search->created_at ?? now()->subDay();

        $query = Property::query()
            ->where('status_obyavleniya_id', $activeId)
            ->where('sozdano_at', '>', $since);

        $request = Request::create('/', 'GET', $search->filtry ?? []);
        PropertyCatalogFilter::apply($query, $request);

        $ids = $query->orderByDesc('id')->limit(20)->pluck('id');
        if ($ids->isEmpty()) {
            $search->update(['poslednyaya_proverka_at' => now()]);

            return 0;
        }

        $user = $search->user;
        if ($user) {
            $count = $ids->count();
            $url = route('properties.index', $search->filtry ?? []);
            $user->notify(new SystemNotification(
                'Новые объявления по сохранённому поиску',
                sprintf('«%s»: найдено %d новых объектов.', $search->nazvanie, $count),
                $url,
                'info',
            ));
            app(TelegramService::class)->notifyUser(
                $user,
                'Новые объявления',
                sprintf('Поиск «%s»: %d новых.', $search->nazvanie, $count),
                $url,
            );
        }

        $search->update(['poslednyaya_proverka_at' => now()]);

        return $ids->count();
    }

    public static function runAllNotifications(): int
    {
        $total = 0;
        SavedSearch::query()
            ->where('uvedomleniya', true)
            ->orderBy('id')
            ->chunkById(50, function ($chunk) use (&$total) {
                foreach ($chunk as $search) {
                    $total += self::notifyMatches($search);
                }
            });

        return $total;
    }
}
