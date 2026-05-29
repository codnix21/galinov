<?php

namespace App\Services;

use App\Models\Contract;
use Illuminate\Support\Str;

/**
 * Интеграция Robokassa (тестовый и боевой режим через IsTest).
 *
 * @see https://docs.robokassa.ru/
 */
class RobokassaService
{
    public function login(): string
    {
        return trim((string) config('services.robokassa.login'));
    }

    public function password1(): string
    {
        return trim((string) config('services.robokassa.password1'));
    }

    public function password2(): string
    {
        return trim((string) config('services.robokassa.password2'));
    }

    public function isConfigured(): bool
    {
        return $this->login() !== ''
            && $this->password1() !== ''
            && $this->password2() !== '';
    }

    public function isTestMode(): bool
    {
        return filter_var(config('services.robokassa.test', true), FILTER_VALIDATE_BOOLEAN);
    }

    /** URL перехода на страницу оплаты Robokassa. */
    public function paymentUrl(Contract $contract): string
    {
        $login = $this->login();
        $password1 = $this->password1();
        $outSum = $this->formatAmount((float) ($contract->tsena ?? 0));
        $invId = (int) $contract->id;

        if ($login === '' || $password1 === '') {
            throw new \RuntimeException('Robokassa: не задан логин или пароль №1');
        }

        $signature = $this->paymentSignature($login, $outSum, $invId, $password1);

        $params = [
            'MerchantLogin' => $login,
            'OutSum' => $outSum,
            'InvId' => $invId,
            'Description' => $this->paymentDescription($contract),
            'SignatureValue' => $signature,
            'Encoding' => 'utf-8',
        ];

        if ($this->isTestMode()) {
            $params['IsTest'] = 1;
        }

        $email = $contract->buyer?->email_polzovatela ?? $contract->buyer?->email ?? null;
        if ($email) {
            $params['Email'] = $email;
        }

        return config('services.robokassa.payment_url')
            . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    public function paymentDescription(Contract $contract): string
    {
        $title = Str::limit((string) ($contract->property?->nazvanie ?? 'сделка'), 80, '');

        return 'Договор №' . $contract->id . ($title !== '' ? ': ' . $title : '');
    }

    /** Проверка подписи Result URL (Password #2). */
    public function verifyResultSignature(string $outSum, int $invId, string $signatureValue, array $shpParams = []): bool
    {
        $expected = $this->resultSignature($outSum, $invId, $this->password2(), $shpParams);

        return hash_equals(strtolower($expected), strtolower($signatureValue));
    }

    public function paymentSignature(string $login, string $outSum, int $invId, string $password1): string
    {
        return $this->hash("{$login}:{$outSum}:{$invId}:{$password1}");
    }

    public function resultSignature(string $outSum, int $invId, string $password2, array $shpParams = []): string
    {
        $parts = [$outSum, (string) $invId];

        if ($shpParams !== []) {
            ksort($shpParams);
            foreach ($shpParams as $key => $value) {
                $parts[] = "{$key}={$value}";
            }
        }

        $parts[] = $password2;

        return $this->hash(implode(':', $parts));
    }

    private function hash(string $value): string
    {
        $algo = strtolower((string) config('services.robokassa.hash', 'md5'));

        return match ($algo) {
            'sha256', 'sha-256' => hash('sha256', $value),
            default => md5($value),
        };
    }

    public function formatAmount(float $amount): string
    {
        return number_format(max(0.01, $amount), 2, '.', '');
    }

    /** @return array<string, string> */
    public function extractShpParams(array $input): array
    {
        $shp = [];
        foreach ($input as $key => $value) {
            if (is_string($key) && str_starts_with($key, 'Shp_') && is_scalar($value)) {
                $shp[$key] = (string) $value;
            }
        }

        return $shp;
    }
}
