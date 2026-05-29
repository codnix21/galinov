<?php

namespace App\Observers;

use App\Models\Contract;
use App\Models\ContractStatus;
use App\Models\ZhurnalIzmeneniy;
use Illuminate\Support\Facades\Auth;

/**
 * Запись в журнал изменений при создании, правке и удалении договора.
 */
class ContractAuditObserver
{
    /** Поля договора, которые попадают в журнал аудита. @var list<string> */
    private array $otslezhivaemyePolya = [
        'nedvizhimost_id', 'vladelets_id', 'pokupatel_id', 'rieltor_id', 'sozdal_kak', 'sozdal_storona',
        'tip', 'tsena', 'data_nachala', 'data_okonchaniya',
        'status_dogovora_id', 'primechaniya', 'skan_dogovora',
    ];

    /** После создания договора — запись «договор создан» со снимком полей. */
    public function created(Contract $contract): void
    {
        ZhurnalIzmeneniy::zapisat(
            Auth::user()?->getKey(),
            Contract::class,
            $contract->id,
            'dogovor_sozdan',
            $this->snapshotKakDetal($contract),
            null
        );
    }

    /**
     * При изменении — список полей «было / стало»; смена статуса может дать подтверждение или отклонение.
     */
    public function updated(Contract $contract): void
    {
        $changes = $contract->getChanges();
        $original = $contract->getRawOriginal();
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

        $deystvie = 'dogovor_obnovlen';
        $oldId = isset($original['status_dogovora_id']) ? (int) $original['status_dogovora_id'] : null;
        $newId = isset($contract->getAttributes()['status_dogovora_id'])
            ? (int) $contract->getAttributes()['status_dogovora_id']
            : null;
        $oldSt = $oldId !== null ? ContractStatus::kodFor($oldId) : null;
        $newSt = $newId !== null ? ContractStatus::kodFor($newId) : null;
        if ($oldSt === 'pending' && $newSt === 'active') {
            $deystvie = 'dogovor_podtverzhden';
        }
        if ($oldSt === 'pending' && $newSt === 'cancelled') {
            $deystvie = 'dogovor_otklonen';
        }

        ZhurnalIzmeneniy::zapisat(
            Auth::user()?->getKey(),
            Contract::class,
            $contract->id,
            $deystvie,
            $det,
            null
        );
    }

    /** Перед удалением — запись «договор удалён». */
    public function deleting(Contract $contract): void
    {
        ZhurnalIzmeneniy::zapisat(
            Auth::user()?->getKey(),
            Contract::class,
            $contract->id,
            'dogovor_udalen',
            $this->snapshotKakDetal($contract),
            null
        );
    }

    /** Все отслеживаемые поля договора в формате журнала (было пусто, стало — значение). */
    private function snapshotKakDetal(Contract $contract): array
    {
        $det = [];
        foreach ($this->otslezhivaemyePolya as $key) {
            if (!array_key_exists($key, $contract->getAttributes())) {
                continue;
            }
            $det[] = [
                'polya' => $key,
                'bilo' => null,
                'stalo' => $this->formatZnachenie($contract->getAttribute($key)),
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
