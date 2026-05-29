<?php

namespace App\Observers;

use App\Models\User;
use App\Models\ZhurnalIzmeneniy;
use Illuminate\Support\Facades\Auth;

/**
 * Запись в журнал при создании, правке и удалении пользователя.
 */
class UserAuditObserver
{
    /** @var list<string> */
    private array $otslezhivaemyePolya = [
        'familia', 'imya', 'otchestvo', 'email_polzovatela', 'telefon', 'rol_id', 'zablokirovan',
    ];

    public function created(User $user): void
    {
        ZhurnalIzmeneniy::zapisat(
            Auth::user()?->getKey(),
            User::class,
            $user->id,
            'polzovatel_sozdan',
            $this->snapshotKakDetal($user),
            null
        );
    }

    public function updated(User $user): void
    {
        $changes = $user->getChanges();
        $original = $user->getRawOriginal();
        $det = [];

        foreach ($changes as $key => $newVal) {
            if ($key === 'parol' || !in_array($key, $this->otslezhivaemyePolya, true)) {
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

        $deystvie = 'polzovatel_obnovlen';
        if (array_key_exists('zablokirovan', $changes)) {
            $deystvie = ($changes['zablokirovan'] ?? false)
                ? 'polzovatel_zablokirovan'
                : 'polzovatel_razblokirovan';
        } elseif (array_key_exists('rol_id', $changes)) {
            $deystvie = 'polzovatel_rol_izmenena';
        }

        ZhurnalIzmeneniy::zapisat(
            Auth::user()?->getKey(),
            User::class,
            $user->id,
            $deystvie,
            $det,
            null
        );
    }

    public function deleting(User $user): void
    {
        ZhurnalIzmeneniy::zapisat(
            Auth::user()?->getKey(),
            User::class,
            $user->id,
            'polzovatel_udalen',
            $this->snapshotKakDetal($user),
            null
        );
    }

    /** @return list<array{polya: string, bilo: null, stalo: string|null}> */
    private function snapshotKakDetal(User $user): array
    {
        $det = [];
        foreach ($this->otslezhivaemyePolya as $key) {
            if (!array_key_exists($key, $user->getAttributes())) {
                continue;
            }
            $det[] = [
                'polya' => $key,
                'bilo' => null,
                'stalo' => $this->formatZnachenie($user->getAttribute($key)),
            ];
        }

        return $det;
    }

    private function formatZnachenie(mixed $v): ?string
    {
        if ($v === null) {
            return null;
        }
        if (is_bool($v)) {
            return $v ? '1' : '0';
        }
        if (is_scalar($v) || $v instanceof \Stringable) {
            return (string) $v;
        }

        return json_encode($v, JSON_UNESCAPED_UNICODE);
    }
}
