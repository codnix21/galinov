<?php

namespace App\Support;

use Carbon\Carbon;
use App\Models\City;
use App\Models\ContractStatus;
use App\Models\Property;
use App\Models\PropertyStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Подготовка строк журнала аудита для отображения (без внутренних кодов и лишних ID).
 */
final class AuditJournalDisplay
{
    /** Код статуса объявления → подпись для журнала. @return array<string, string> */
    private static function statusyObyavleniya(): array
    {
        return [
            'draft' => 'Черновик',
            'active' => 'Опубликовано (в каталоге)',
            'pending_review' => 'На модерации',
            'sold' => 'Продано',
            'rented' => 'Сдано в аренду',
            'inactive' => 'Снято с публикации',
        ];
    }

    /** Код статуса договора → подпись для журнала. @return array<string, string> */
    private static function statusyDogovora(): array
    {
        return [
            'draft' => 'Черновик',
            'pending' => 'Ожидает подтверждения',
            'active' => 'Активен',
            'completed' => 'Завершён',
            'cancelled' => 'Отклонён / отменён',
        ];
    }

    /** Тип объекта (apartment и т.д.) → русское название. @return array<string, string> */
    private static function tipNedvizhimosti(): array
    {
        return Property::tipNazvaniya();
    }

    /** Продажа или аренда → подпись. @return array<string, string> */
    private static function operatsii(): array
    {
        return [
            'sale' => 'Продажа',
            'rent' => 'Аренда',
        ];
    }

    /** Что произошло: создание, публикация, договор и т.д. */
    public static function nadpisDeystviya(string $kod): string
    {
        return match ($kod) {
            'sozdano' => 'Создание объявления',
            'obnovleno' => 'Изменение данных',
            'opublikovano' => 'Публикация в каталоге',
            'otmecheno_kak_prodannoe' => 'Отмечено как продано',
            'udaleno' => 'Удаление',
            'dogovor_sozdan' => 'Договор создан',
            'dogovor_obnovlen' => 'Договор изменён',
            'dogovor_podtverzhden' => 'Договор подтверждён',
            'dogovor_otklonen' => 'Договор отклонён',
            'dogovor_udalen' => 'Договор удалён',
            'polzovatel_sozdan' => 'Пользователь создан',
            'polzovatel_obnovlen' => 'Пользователь изменён',
            'polzovatel_udalen' => 'Пользователь удалён',
            'polzovatel_zablokirovan' => 'Пользователь заблокирован',
            'polzovatel_razblokirovan' => 'Пользователь разблокирован',
            'polzovatel_rol_izmenena' => 'Изменена роль пользователя',
            default => $kod,
        };
    }

    /** Тип объекта в журнале → подпись. */
    public static function nadpisTipaObyekta(string $obyektType): string
    {
        return match ($obyektType) {
            Property::class => 'Объявление',
            Contract::class => 'Договор',
            User::class => 'Пользователь',
            default => 'Объект',
        };
    }

    /** Список действий для фильтра в админке. @return array<string, string> */
    public static function kodyDeystviyDlyaFiltra(): array
    {
        $kody = [
            'sozdano', 'obnovleno', 'opublikovano', 'otmecheno_kak_prodannoe', 'udaleno',
            'dogovor_sozdan', 'dogovor_obnovlen', 'dogovor_podtverzhden', 'dogovor_otklonen', 'dogovor_udalen',
            'polzovatel_sozdan', 'polzovatel_obnovlen', 'polzovatel_udalen',
            'polzovatel_zablokirovan', 'polzovatel_razblokirovan', 'polzovatel_rol_izmenena',
        ];
        $out = [];
        foreach ($kody as $kod) {
            $out[$kod] = self::nadpisDeystviya($kod);
        }

        return $out;
    }

    /** Имя поля в БД → заголовок колонки в таблице журнала. */
    public static function nadpisPolya(string $polya): string
    {
        return match ($polya) {
            'nazvanie' => 'Название',
            'opisanie' => 'Описание',
            'tip' => 'Тип недвижимости',
            'operatsiya' => 'Тип сделки',
            'tsena' => 'Цена',
            'gorod' => 'Город',
            'gorod_id' => 'Город',
            'adres_ulitsy' => 'Адрес',
            'geo_shirota' => 'Широта (геоточка)',
            'geo_dolgota' => 'Долгота (геоточка)',
            'ploshchad' => 'Площадь, м²',
            'komnaty' => 'Комнат',
            'etazh' => 'Этаж',
            'vsego_etazhey' => 'Этажность дома',
            'polzovatel_id' => 'Владелец объявления',
            'status_obyavleniya' => 'Статус объявления',
            'status_obyavleniya_id' => 'Статус объявления',
            'nedvizhimost_id' => 'Объект по договору',
            'klient_id' => 'Клиент',
            'vladelets_id' => 'Владелец',
            'pokupatel_id' => 'Покупатель',
            'rieltor_id' => 'Риэлтор',
            'rol_id' => 'Роль',
            'zablokirovan' => 'Блокировка',
            'email_polzovatela' => 'Email',
            'telefon' => 'Телефон',
            'familia' => 'Фамилия',
            'imya' => 'Имя',
            'otchestvo' => 'Отчество',
            'data_nachala' => 'Дата начала',
            'data_okonchaniya' => 'Дата окончания',
            'skan_dogovora' => 'Скан подписанного договора',
            'status_dogovora' => 'Статус договора',
            'status_dogovora_id' => 'Статус договора',
            'primechaniya' => 'Примечания',
            'prichina_otkaza_mod' => 'Причина отказа модерации',
            default => str_replace('_', ' ', $polya),
        };
    }

    /**
     * Убирает технические строки, если рядом есть «человеческое» поле с тем же смыслом.
     *
     * @param  list<array{polya: string, bilo: mixed, stalo: mixed}>  $stroki
     * @return list<array{polya: string, bilo: mixed, stalo: mixed}>
     */
    public static function otfiltrovatTehnicheskieStroki(array $stroki): array
    {
        $keys = [];
        foreach ($stroki as $st) {
            $keys[$st['polya']] = true;
        }

        return array_values(array_filter($stroki, static function (array $st) use ($keys): bool {
            $p = $st['polya'];
            if ($p === 'status_obyavleniya_id' && isset($keys['status_obyavleniya'])) {
                return false;
            }
            if ($p === 'status_obyavleniya' && isset($keys['status_obyavleniya_id'])) {
                return false;
            }
            if ($p === 'status_dogovora_id' && isset($keys['status_dogovora'])) {
                return false;
            }
            if ($p === 'status_dogovora' && isset($keys['status_dogovora_id'])) {
                return false;
            }
            if ($p === 'gorod_id' && isset($keys['gorod'])) {
                return false;
            }
            if ($p === 'gorod' && isset($keys['gorod_id'])) {
                return false;
            }

            return true;
        }));
    }

    /**
     * Готовит строки для вывода в UI: подписи полей и отформатированные «было / стало».
     *
     * @param  list<array{polya: string, bilo: mixed, stalo: mixed}>  $stroki
     * @return list<array{nadpis_polya: string, bilo: string, stalo: string}>
     */
    public static function podgotovitStrokiTablitsy(array $stroki): array
    {
        $stroki = self::otfiltrovatTehnicheskieStroki($stroki);
        $ctx = self::sobratKontekst($stroki);

        $out = [];
        foreach ($stroki as $st) {
            if ($st['polya'] === 'skan_dogovora') {
                $sk = self::formatSkanDogovoraStroku($st);
                $out[] = [
                    'nadpis_polya' => self::nadpisPolya('skan_dogovora'),
                    'bilo' => $sk['bilo'],
                    'stalo' => $sk['stalo'],
                ];

                continue;
            }
            $out[] = [
                'nadpis_polya' => self::nadpisPolya($st['polya']),
                'bilo' => self::formatZnachenie($st['polya'], $st['bilo'] ?? null, $ctx),
                'stalo' => self::formatZnachenie($st['polya'], $st['stalo'] ?? null, $ctx),
            ];
        }

        return $out;
    }

    /**
     * Подгружает из БД пользователей, города и статусы по ID из строк журнала.
     *
     * @param  list<array{polya: string, bilo: mixed, stalo: mixed}>  $stroki
     * @return array{users: Collection, props: Collection, cities: Collection, propStatuses: Collection, contractStatuses: Collection}
     */
    private static function sobratKontekst(array $stroki): array
    {
        $userIds = [];
        $propIds = [];
        $cityIds = [];
        $propStatusIds = [];
        $contractStatusIds = [];
        $roleIds = [];

        foreach ($stroki as $st) {
            $p = $st['polya'];
            foreach (['bilo', 'stalo'] as $kk) {
                $v = $st[$kk] ?? null;
                if ($v === null || $v === '' || ! is_numeric($v)) {
                    continue;
                }
                $id = (int) $v;
                match ($p) {
                    'polzovatel_id', 'klient_id', 'rieltor_id', 'vladelets_id', 'pokupatel_id' => $userIds[$id] = true,
                    'nedvizhimost_id' => $propIds[$id] = true,
                    'gorod_id' => $cityIds[$id] = true,
                    'status_obyavleniya_id' => $propStatusIds[$id] = true,
                    'status_dogovora_id' => $contractStatusIds[$id] = true,
                    'rol_id' => $roleIds[$id] = true,
                    default => null,
                };
            }
        }

        return [
            'users' => User::query()->whereIn('id', array_keys($userIds))->get()->keyBy('id'),
            'props' => Property::query()->whereIn('id', array_keys($propIds))->get()->keyBy('id'),
            'cities' => City::query()->whereIn('id', array_keys($cityIds))->get()->keyBy('id'),
            'propStatuses' => PropertyStatus::query()->whereIn('id', array_keys($propStatusIds))->get()->keyBy('id'),
            'contractStatuses' => ContractStatus::query()->whereIn('id', array_keys($contractStatusIds))->get()->keyBy('id'),
            'roles' => Role::query()->whereIn('id', array_keys($roleIds))->get()->keyBy('id'),
        ];
    }

    /**
     * Одно значение поля в читаемый вид (цена с ₽, ФИО, статус по справочнику).
     *
     * @param  array{users: Collection, props: Collection, cities: Collection, propStatuses: Collection, contractStatuses: Collection}  $ctx
     */
    private static function formatZnachenie(string $polya, mixed $znachenie, array $ctx): string
    {
        if ($znachenie === null || $znachenie === '') {
            return '—';
        }
        $s = is_scalar($znachenie) || $znachenie instanceof \Stringable
            ? (string) $znachenie
            : json_encode($znachenie, JSON_UNESCAPED_UNICODE);

        return match ($polya) {
            'status_obyavleniya' => self::statusyObyavleniya()[$s] ?? $s,
            'status_obyavleniya_id' => self::statusObyavleniyaPoId($s, $ctx['propStatuses']),
            'status_dogovora' => self::statusyDogovora()[$s] ?? $s,
            'status_dogovora_id' => self::statusDogovoraPoId($s, $ctx['contractStatuses']),
            'tip' => self::tipNedvizhimosti()[$s] ?? $s,
            'operatsiya' => self::operatsii()[$s] ?? $s,
            'polzovatel_id', 'klient_id', 'rieltor_id', 'vladelets_id', 'pokupatel_id' => self::imyaPolzovatelya($s, $ctx['users']),
            'rol_id' => self::rolPoId($s, $ctx['roles']),
            'zablokirovan' => in_array($s, ['1', 'true', 'yes'], true) ? 'Заблокирован' : 'Активен',
            'nedvizhimost_id' => self::zagolovokObekta($s, $ctx['props']),
            'tsena' => is_numeric($s)
                ? number_format((float) $s, 0, ',', ' ').' ₽'
                : $s,
            'geo_shirota', 'geo_dolgota' => is_numeric($s) ? (string) round((float) $s, 6) : $s,
            'gorod_id' => self::gorodPoId($s, $ctx['cities']),
            'data_nachala', 'data_okonchaniya' => self::formatData($s),
            'ploshchad' => is_numeric($s) ? $s.' м²' : $s,
            default => $s,
        };
    }

    /** Дата в формате дд.мм.гггг или исходная строка при ошибке разбора. */
    private static function formatData(string $s): string
    {
        try {
            return Carbon::parse($s)->format('d.m.Y');
        } catch (\Throwable) {
            return $s;
        }
    }

    /** Статус объявления по числовому ID из справочника. */
    private static function statusObyavleniyaPoId(string $idStr, Collection $rows): string
    {
        if (! ctype_digit($idStr)) {
            return $idStr;
        }
        $row = $rows->get((int) $idStr);
        if (! $row instanceof PropertyStatus) {
            return 'Другой статус';
        }
        $kod = (string) ($row->kod ?? '');

        return self::statusyObyavleniya()[$kod] ?? (string) ($row->nazvanie ?? 'Статус');
    }

    /** Статус договора по числовому ID из справочника. */
    private static function statusDogovoraPoId(string $idStr, Collection $rows): string
    {
        if (! ctype_digit($idStr)) {
            return $idStr;
        }
        $row = $rows->get((int) $idStr);
        if (! $row instanceof ContractStatus) {
            return 'Другой статус';
        }
        $kod = (string) ($row->kod ?? '');

        return self::statusyDogovora()[$kod] ?? (string) ($row->nazvanie ?? 'Статус');
    }

    /** Название города по ID. */
    private static function gorodPoId(string $idStr, Collection $cities): string
    {
        if (! ctype_digit($idStr)) {
            return $idStr;
        }
        $c = $cities->get((int) $idStr);
        if (! $c instanceof City) {
            return 'Другой город';
        }

        return (string) ($c->nazvanie ?? 'Город');
    }

    /** ФИО или email пользователя по ID. */
    private static function imyaPolzovatelya(string $idStr, Collection $users): string
    {
        if (! ctype_digit($idStr)) {
            return $idStr;
        }
        $u = $users->get((int) $idStr);
        if (! $u instanceof User) {
            return 'Другой пользователь';
        }
        $fio = trim(implode(' ', array_filter([$u->familia, $u->imya, $u->otchestvo])));

        return $fio !== '' ? $fio : (string) ($u->email_polzovatela ?? $u->email ?? 'Пользователь');
    }

    /** Название роли по ID. */
    private static function rolPoId(string $idStr, Collection $roles): string
    {
        if (! ctype_digit($idStr)) {
            return $idStr;
        }
        $row = $roles->get((int) $idStr);
        if (! $row instanceof Role) {
            return 'Другая роль';
        }

        return (string) ($row->nazvanie ?? $row->kod ?? 'Роль');
    }

    /** Название объявления по ID недвижимости. */
    private static function zagolovokObekta(string $idStr, Collection $props): string
    {
        if (! ctype_digit($idStr)) {
            return $idStr;
        }
        $p = $props->get((int) $idStr);
        if (! $p instanceof Property) {
            return 'Объект недвижимости';
        }
        $t = $p->nazvanie ?? $p->title ?? '';

        return $t !== '' ? $t : 'Объект недвижимости';
    }

    /**
     * Для поля скана договора — «нет файла» / «файл прикреплён» / «новый файл (замена)».
     *
     * @param  array{bilo: mixed, stalo: mixed}  $st
     * @return array{bilo: string, stalo: string}
     */
    public static function formatSkanDogovoraStroku(array $st): array
    {
        $b = $st['bilo'] ?? null;
        $s = $st['stalo'] ?? null;
        $bPust = $b === null || $b === '';
        $sPust = $s === null || $s === '';

        $bilo = $bPust ? 'Нет файла' : 'Файл прикреплён';
        $stalo = $sPust ? 'Нет файла' : 'Файл прикреплён';
        if (! $bPust && ! $sPust && (string) $b !== (string) $s) {
            $stalo = 'Новый файл (замена)';
        }

        return ['bilo' => $bilo, 'stalo' => $stalo];
    }
}
