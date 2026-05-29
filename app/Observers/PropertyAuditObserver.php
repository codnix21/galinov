<?php

namespace App\Observers;

use App\Models\Property;
use App\Models\PropertyStatus;
use App\Models\ZhurnalIzmeneniy;
use Illuminate\Support\Facades\Auth;

/**
 * Запись в журнал изменений при создании, правке и удалении объявления.
 */
class PropertyAuditObserver
{
    /** Поля объявления, которые попадают в журнал аудита. @var list<string> */
    private array $otslezhivaemyePolya = [
        'nazvanie', 'opisanie', 'tip', 'operatsiya', 'tsena', 'gorod_id',
        'adres_ulitsy', 'ploshchad', 'komnaty', 'etazh', 'vsego_etazhey',
        'polzovatel_id', 'status_obyavleniya_id', 'prichina_otkaza_mod',
        'geo_shirota', 'geo_dolgota',
    ];

    /** После создания объявления — запись «создано» со снимком полей. */
    public function created(Property $property): void
    {
        ZhurnalIzmeneniy::zapisat(
            Auth::user()?->getKey(),
            Property::class,
            $property->id,
            'sozdano',
            $this->snapshotKakDetal($property),
            null
        );
    }

    /**
     * При изменении — поля «было / стало»; смена статуса может означать публикацию или «продано».
     */
    public function updated(Property $property): void
    {
        $changes = $property->getChanges();
        $original = $property->getRawOriginal();
        $det = [];
        foreach ($changes as $key => $newVal) {
            if (!in_array($key, $this->otslezhivaemyePolya, true)) {
                continue;
            }
            $det[] = [
                'polya' => $key,
                'bilo' => $this->formatZnachenie($original[$key] ?? null),
                'stalo' => $this->formatZnachenie($newVal),
            ];
        }
        if ($det === []) {
            return;
        }

        $deystvie = 'obnovleno';
        $oldId = isset($original['status_obyavleniya_id']) ? (int) $original['status_obyavleniya_id'] : null;
        $newId = isset($property->getAttributes()['status_obyavleniya_id'])
            ? (int) $property->getAttributes()['status_obyavleniya_id']
            : null;
        $oldStatus = $oldId !== null ? PropertyStatus::kodFor($oldId) : null;
        $newStatus = $newId !== null ? PropertyStatus::kodFor($newId) : null;
        if ($oldStatus === 'draft' && $newStatus === 'active') {
            $deystvie = 'opublikovano';
        }
        if ($newStatus === 'sold' && $oldStatus !== 'sold') {
            $deystvie = 'otmecheno_kak_prodannoe';
        }

        ZhurnalIzmeneniy::zapisat(
            Auth::user()?->getKey(),
            Property::class,
            $property->id,
            $deystvie,
            $det,
            null
        );
    }

    /** Перед удалением — запись «удалено». */
    public function deleting(Property $property): void
    {
        ZhurnalIzmeneniy::zapisat(
            Auth::user()?->getKey(),
            Property::class,
            $property->id,
            'udaleno',
            $this->snapshotKakDetal($property),
            null
        );
    }

    /** Все отслеживаемые поля объявления в формате журнала. */
    private function snapshotKakDetal(Property $property): array
    {
        $det = [];
        foreach ($this->otslezhivaemyePolya as $key) {
            if (!array_key_exists($key, $property->getAttributes())) {
                continue;
            }
            $det[] = [
                'polya' => $key,
                'bilo' => null,
                'stalo' => $this->formatZnachenie($property->getAttribute($key)),
            ];
        }

        return $det;
    }

    /** Приводит значение поля к строке для хранения в журнале. */
    private function formatZnachenie(mixed $v): ?string
    {
        if ($v === null) {
            return null;
        }
        if (is_scalar($v) || $v instanceof \Stringable) {
            return (string) $v;
        }

        return json_encode($v, JSON_UNESCAPED_UNICODE);
    }
}
