<?php

namespace App\Support;

/**
 * Оформление in-app уведомлений по типу (иконка, цвет).
 */
final class NotificationDisplay
{
    /** @return array{icon: string, bg: string, border: string, text: string, label: string} */
    public static function forIcon(string $icon): array
    {
        return match ($icon) {
            'success' => [
                'icon' => '✓',
                'bg' => 'bg-emerald-50',
                'border' => 'border-emerald-200',
                'text' => 'text-emerald-900',
                'label' => 'Успех',
            ],
            'warning' => [
                'icon' => '⚠',
                'bg' => 'bg-amber-50',
                'border' => 'border-amber-200',
                'text' => 'text-amber-900',
                'label' => 'Внимание',
            ],
            'error', 'danger' => [
                'icon' => '✕',
                'bg' => 'bg-red-50',
                'border' => 'border-red-200',
                'text' => 'text-red-900',
                'label' => 'Ошибка',
            ],
            'contract' => [
                'icon' => '📄',
                'bg' => 'bg-violet-50',
                'border' => 'border-violet-200',
                'text' => 'text-violet-900',
                'label' => 'Договор',
            ],
            'moderation' => [
                'icon' => '🛡',
                'bg' => 'bg-sky-50',
                'border' => 'border-sky-200',
                'text' => 'text-sky-900',
                'label' => 'Модерация',
            ],
            default => [
                'icon' => 'ℹ',
                'bg' => 'bg-brand-50',
                'border' => 'border-brand-200',
                'text' => 'text-brand-900',
                'label' => 'Информация',
            ],
        };
    }
}
