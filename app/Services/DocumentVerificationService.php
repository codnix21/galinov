<?php

namespace App\Services;

use App\Models\Property;
use App\Models\UserDocument;
use App\Support\PropertyDocumentRules;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Автоматическая проверка документов и кадастрового номера (демо-режим). */
class DocumentVerificationService
{
    public const PROVIDER = 'RosreestrDemo';

    public function isDemoMode(): bool
    {
        return (bool) config('services.registry.demo_mode', true);
    }

    /** Публичная кадастровая карта (открытые сведения, без API). */
    public function publicMapUrl(?string $cadastralNumber = null): string
    {
        $cadastralNumber = $this->normalizeCadastralNumber($cadastralNumber ?? '') ?? $cadastralNumber;

        if ($cadastralNumber) {
            return 'https://pkk.rosreestr.ru/#/search/' . rawurlencode($cadastralNumber);
        }

        return 'https://rosreestr.gov.ru/';
    }

    /** Отправить документ на автоматическую проверку после загрузки. */
    public function submitForExternalCheck(UserDocument $document): UserDocument
    {
        $document->update([
            'status' => 'checking',
            'vneshniy_id' => 'RR-' . strtoupper(Str::random(10)),
            'vneshniy_status' => 'submitted',
            'vneshniy_provereno_at' => null,
        ]);

        return $this->runMockApiCheck($document);
    }

    /** Повторная проверка из модерации. */
    public function recheck(UserDocument $document): UserDocument
    {
        $document->update([
            'status' => 'checking',
            'vneshniy_status' => 'recheck',
        ]);

        return $this->runMockApiCheck($document);
    }

    /**
     * Проверка по кадастровому номеру (выписка ЕГРН).
     *
     * @return array{ok: bool, message: string, document?: UserDocument, provider: string}
     */
    /**
     * @param  array<string, string|null>  $extraData
     */
    public function verifyByCadastralNumber(Property $property, string $cadastralNumber, array $extraData = []): array
    {
        $normalized = $this->normalizeCadastralNumber($cadastralNumber);
        if ($normalized === null) {
            return [
                'ok' => false,
                'message' => 'Некорректный кадастровый номер. Формат: 77:01:0001001:1001',
                'provider' => self::PROVIDER,
            ];
        }

        $egrnTip = PropertyDocumentRules::egrnTipForProperty($property);
        if ($egrnTip === null) {
            return [
                'ok' => false,
                'message' => 'Для этого типа объявления выписка ЕГРН не требуется.',
                'provider' => self::PROVIDER,
            ];
        }

        $property->update(['kadastrovy_nomer' => $normalized]);

        $this->clearPreviousEgrnAttempts($property, $egrnTip);

        $provider = self::PROVIDER;
        $ok = true;
        $message = "Объект {$normalized} найден. Обременения не выявлены.";

        $path = 'documents/property-' . $property->id . '/egrn-registry-' . str_replace(':', '-', $normalized) . '.json';
        Storage::disk('public')->put($path, json_encode([
            'provider' => $provider,
            'cadastral_number' => $normalized,
            'checked_at' => now()->toIso8601String(),
            'demo' => $this->isDemoMode(),
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        $dannye = array_filter(array_merge(
            ['kadastrovy_nomer' => $normalized],
            $extraData,
        ), fn ($v) => $v !== null && $v !== '');

        $dataSummary = \App\Support\DocumentDataFields::summaryForComment($egrnTip, $dannye);
        $modComment = '[' . $provider . '] ' . $message;
        if ($dataSummary !== '') {
            $modComment .= ' · ' . $dataSummary;
        }

        $document = UserDocument::create([
            'polzovatel_id' => $property->polzovatel_id,
            'nedvizhimost_id' => $property->id,
            'tip' => $egrnTip,
            'tip_obekta' => $property->tip,
            'nazvanie' => PropertyDocumentRules::allTipLabels()[$egrnTip] ?? 'ЕГРН',
            'put_fajla' => $path,
            'status' => $ok ? 'verified' : 'rejected',
            'dannye_json' => $dannye,
            'vneshniy_id' => strtoupper(substr($provider, 0, 2)) . '-EGRN-' . strtoupper(Str::random(8)),
            'vneshniy_status' => $ok ? 'verified' : 'rejected',
            'vneshniy_provereno_at' => now(),
            'provereno_at' => $ok ? now() : null,
            'kommentariy_mod' => $modComment,
        ]);

        return [
            'ok' => $ok,
            'message' => $message,
            'document' => $document->fresh(),
            'provider' => $provider,
        ];
    }

    public function normalizeCadastralNumber(string $raw): ?string
    {
        $value = preg_replace('/\s+/', '', trim($raw));
        if ($value === null || $value === '') {
            return null;
        }

        if (!preg_match('/^\d{2}:\d{2}:\d{6,7}:\d{1,5}$/', $value)) {
            return null;
        }

        return $value;
    }

    private function clearPreviousEgrnAttempts(Property $property, string $egrnTip): void
    {
        UserDocument::query()
            ->where('nedvizhimost_id', $property->id)
            ->where('tip', $egrnTip)
            ->whereStatusKodIn(['rejected', 'pending', 'checking'])
            ->delete();
    }

    private function runMockApiCheck(UserDocument $document): UserDocument
    {
        $document->loadMissing(['user', 'property']);

        $tip = $document->tip ?? '';
        $cadastral = $document->property?->kadastrovy_nomer;

        if ($this->isDemoMode()) {
            $ok = true;
        } else {
            $hash = crc32($document->id . $tip . ($document->nedvizhimost_id ?? 0));
            $ok = ($hash % 5) !== 0;
        }

        $apiMessage = match (true) {
            !$ok => 'Расхождение в реестре: данные объекта не совпадают с выпиской.',
            $tip === 'egrn' || $tip === 'egrn_land' => $cadastral
                ? "Кадастровый номер {$cadastral} найден, обременения не выявлены."
                : 'Выписка ЕГРН соответствует требованиям.',
            $tip === 'egrul' => 'Организация действующая, статус «действует».',
            $tip === 'passport' => 'Паспортные данные приняты.',
            $tip === 'rent_contract' => 'Право сдачи подтверждено.',
            default => 'Документ соответствует требованиям.',
        };

        $prefix = trim((string) $document->kommentariy_mod);
        $modComment = '[' . self::PROVIDER . '] ' . $apiMessage;
        if ($prefix !== '') {
            $modComment = $prefix . ' · ' . $modComment;
        }

        $document->update([
            'status' => $ok ? 'verified' : 'rejected',
            'vneshniy_status' => $ok ? 'verified' : 'rejected',
            'vneshniy_provereno_at' => now(),
            'kommentariy_mod' => $modComment,
            'provereno_at' => $ok ? now() : null,
        ]);

        return $document->fresh();
    }

    public function externalSummary(UserDocument $document): string
    {
        if (!$document->vneshniy_id) {
            return 'Автопроверка не запускалась';
        }

        return sprintf(
            '%s: %s (%s)',
            self::PROVIDER,
            $document->vneshniy_status ?? '—',
            $document->vneshniy_id
        );
    }
}
