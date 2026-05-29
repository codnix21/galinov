<?php

namespace App\Support;

use App\Models\Property;
use App\Models\RealtorClient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Кто разместил объявление: клиент сам, риэлтор от агентства, за клиента или как собственник.
 */
class PropertyListingAuthor
{
    public const CLIENT = 'client';

    public const REALTOR_AGENCY = 'realtor_agency';

    public const REALTOR_FOR_CLIENT = 'realtor_for_client';

    public const REALTOR_OWN = 'realtor_own';

    /** @return array<string, string> */
    public static function realtorOptions(): array
    {
        return [
            self::REALTOR_FOR_CLIENT => 'От имени клиента-собственника',
            self::REALTOR_AGENCY => 'От имени агентства (объект агентства)',
            self::REALTOR_OWN => 'Моя недвижимость (я указан как собственник)',
        ];
    }

    public static function label(?string $kod): string
    {
        return match ($kod) {
            self::REALTOR_FOR_CLIENT => 'Риэлтор · за клиента',
            self::REALTOR_AGENCY => 'Риэлтор · агентство',
            self::REALTOR_OWN => 'Риэлтор · личный объект',
            self::CLIENT => 'Клиент',
            default => '—',
        };
    }

    public static function description(?string $kod): string
    {
        return match ($kod) {
            self::REALTOR_FOR_CLIENT => 'Документы и договор — на клиента-собственника, вы ведёте сделку как риэлтор.',
            self::REALTOR_AGENCY => 'Объект в каталоге агентства, собственник в карточке — вы как сотрудник.',
            self::REALTOR_OWN => 'Вы указаны собственником; документы загружаете сами.',
            default => 'Владелец разместил объявление самостоятельно.',
        };
    }

    public static function canManage(User $user, Property $property): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $uid = (int) $user->id;
        if ((int) ($property->polzovatel_id ?? 0) === $uid) {
            return true;
        }

        return $user->isRealtor() && (int) ($property->rieltor_id ?? 0) === $uid;
    }

    /**
     * @return array{polzovatel_id: int, rieltor_id: ?int, sozdal_kak: string}
     */
    public static function resolveFromRequest(User $user, Request $request): array
    {
        if (!$user->isRealtor() && !$user->isAdmin()) {
            return [
                'polzovatel_id' => (int) $user->id,
                'rieltor_id' => null,
                'sozdal_kak' => self::CLIENT,
            ];
        }

        if (!$user->isRealtor()) {
            return [
                'polzovatel_id' => (int) $user->id,
                'rieltor_id' => null,
                'sozdal_kak' => self::CLIENT,
            ];
        }

        $mode = (string) $request->input('listing_mode', self::REALTOR_FOR_CLIENT);
        if (!array_key_exists($mode, self::realtorOptions())) {
            throw ValidationException::withMessages([
                'listing_mode' => 'Укажите, от чьего имени размещается объявление.',
            ]);
        }

        return match ($mode) {
            self::REALTOR_FOR_CLIENT => self::resolveForClient($user, (int) $request->input('vladelets_id', 0)),
            self::REALTOR_AGENCY => [
                'polzovatel_id' => (int) $user->id,
                'rieltor_id' => (int) $user->id,
                'sozdal_kak' => self::REALTOR_AGENCY,
            ],
            self::REALTOR_OWN => [
                'polzovatel_id' => (int) $user->id,
                'rieltor_id' => (int) $user->id,
                'sozdal_kak' => self::REALTOR_OWN,
            ],
            default => throw ValidationException::withMessages([
                'listing_mode' => 'Некорректный тип размещения.',
            ]),
        };
    }

    /**
     * @return array{polzovatel_id: int, rieltor_id: int, sozdal_kak: string}
     */
    private static function resolveForClient(User $realtor, int $clientId): array
    {
        if ($clientId <= 0) {
            throw ValidationException::withMessages([
                'vladelets_id' => 'Выберите клиента-собственника из списка.',
            ]);
        }

        $client = User::find($clientId);
        if (!$client || !$client->isClient()) {
            throw ValidationException::withMessages([
                'vladelets_id' => 'Собственником может быть только пользователь с ролью «Клиент».',
            ]);
        }

        RealtorClient::firstOrCreate(
            ['rieltor_id' => (int) $realtor->id, 'klient_id' => $clientId],
            ['status' => 'new']
        );

        return [
            'polzovatel_id' => $clientId,
            'rieltor_id' => (int) $realtor->id,
            'sozdal_kak' => self::REALTOR_FOR_CLIENT,
        ];
    }
}
