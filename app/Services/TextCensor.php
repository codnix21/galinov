<?php

namespace App\Services;

/**
 * Проверка текста на запрещённые слова из конфига (для объявлений и обычных полей).
 */
final class TextCensor
{
    /** @var list<string>|null */
    private static ?array $sorted = null;

    /**
     * Список слов из конфига, отсортированный по длине (длинные раньше — точнее совпадения).
     *
     * @return list<string>
     */
    private static function sortedWords(): array
    {
        if (self::$sorted === null) {
            $words = config('censor.words', []);
            if ($words === []) {
                self::$sorted = [];

                return self::$sorted;
            }
            $w = array_map(static fn (string $s) => mb_strtolower(trim($s)), $words);
            $w = array_values(array_filter($w));
            $w = array_values(array_unique($w));
            usort($w, static fn (string $a, string $b) => mb_strlen($b) <=> mb_strlen($a));
            self::$sorted = $w;
        }

        return self::$sorted;
    }

    /**
     * Есть ли в тексте хотя бы одно запрещённое слово (как отдельное слово, не часть другого).
     */
    public static function containsProfanity(?string $text): bool
    {
        if ($text === null || $text === '') {
            return false;
        }

        foreach (self::sortedWords() as $word) {
            if ($word === '') {
                continue;
            }
            $q = preg_quote($word, '/');
            $pattern = '/(?<![\p{L}\p{N}_])'.$q.'(?![\p{L}\p{N}_])/iu';
            if (preg_match($pattern, $text) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ошибка валидации для одного текстового поля (сохранение отменяется).
     *
     * @return array<string, string>
     */
    public static function fieldError(string $field, ?string $text): array
    {
        if (! self::containsProfanity($text)) {
            return [];
        }

        return [$field => self::textRejectionMessage()];
    }

    /**
     * Ошибки валидации для полей объявления (название / описание).
     *
     * @return array<string, string>
     */
    public static function propertyFieldErrors(?string $nazvanie, ?string $opisanie): array
    {
        $msg = self::propertyRejectionMessage();
        $errors = [];
        if (self::containsProfanity($nazvanie)) {
            $errors['nazvanie'] = $msg;
        }
        if (self::containsProfanity($opisanie)) {
            $errors['opisanie'] = $msg;
        }

        return $errors;
    }

    /**
     * Текст ошибки для обычного поля (из настроек censor).
     */
    public static function textRejectionMessage(): string
    {
        return (string) config(
            'censor.text_rejection_message',
            'Текст содержит запрещённую или ненормативную лексику. Уберите такие слова и сохраните снова.'
        );
    }

    /**
     * Текст отказа при модерации объявления (из настроек censor).
     */
    public static function propertyRejectionMessage(): string
    {
        return (string) config(
            'censor.property_rejection_message',
            'Публикация отклонена: в названии или описании обнаружена ненормативная лексика. Исправьте текст и отправьте объявление снова.'
        );
    }
}
