<?php

namespace App\Services;

use App\Models\Contract;
use Illuminate\Support\Str;

/**
 * Тестовый платёжный шлюз (имитация ЮKassa / СБП).
 */
class TestPaymentService
{
    public const TEST_CARDS = [
        '4111111111111111' => 'success',
        '4000000000000002' => 'declined',
    ];

    /**
     * @return array{ok: bool, message: string, transaction_id: ?string}
     */
    public function process(Contract $contract, string $method, array $payload): array
    {
        $amount = (float) ($contract->tsena ?? 0);
        if ($amount <= 0) {
            return ['ok' => false, 'message' => 'Сумма сделки не указана', 'transaction_id' => null];
        }

        $last4 = '0000';
        if ($method === 'card') {
            $number = preg_replace('/\D/', '', $payload['card_number'] ?? '');
            if (strlen($number) < 16) {
                return ['ok' => false, 'message' => 'Некорректный номер карты', 'transaction_id' => null];
            }
            $last4 = substr($number, -4);
            $prefix16 = substr($number, 0, 16);
            if (($this->cardCheck($number) === false)) {
                return ['ok' => false, 'message' => 'Неверный номер карты (контрольная сумма)', 'transaction_id' => null];
            }
            if (isset(self::TEST_CARDS[$prefix16]) && self::TEST_CARDS[$prefix16] === 'declined') {
                return ['ok' => false, 'message' => 'Тестовая карта отклонена банком (4000…0002)', 'transaction_id' => null];
            }
        }

        if ($method === 'sbp' && empty($payload['sbp_phone'])) {
            return ['ok' => false, 'message' => 'Укажите номер телефона для СБП', 'transaction_id' => null];
        }

        $tx = 'TX-' . strtoupper(Str::random(12));

        $contract->update([
            'oplata_status' => 'simulated_paid',
            'oplata_at' => now(),
            'oplata_metod' => $method,
            'oplata_tranzaktsiya' => $tx,
            'oplata_summa' => $amount,
        ]);

        return [
            'ok' => true,
            'message' => $method === 'sbp'
                ? 'Платёж СБП принят (тест)'
                : 'Платёж картой •••• ' . ($last4 ?? '0000') . ' принят (тест)',
            'transaction_id' => $tx,
        ];
    }

    /** Алгоритм Луна */
    public function cardCheck(string $number): bool
    {
        $sum = 0;
        $alt = false;
        for ($i = strlen($number) - 1; $i >= 0; $i--) {
            $n = (int) $number[$i];
            if ($alt) {
                $n *= 2;
                if ($n > 9) {
                    $n -= 9;
                }
            }
            $sum += $n;
            $alt = !$alt;
        }

        return $sum % 10 === 0;
    }
}
