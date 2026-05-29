<?php

/**
 * Добавляет секционные комментарии {{-- ... --}} в ключевые Blade-шаблоны.
 * Запуск: php tools/add_blade_section_comments.php
 */

$base = dirname(__DIR__) . '/resources/views';

/** Путь относительно views → маркеры: строка-якорь => комментарий (вставляется перед якорем, если комментария ещё нет) */
$sections = [
    'admin/contracts/create.blade.php' => [
        '<div class="max-w-3xl mx-auto">' => 'Заголовок и подсказки для администратора.',
        '<form method="POST"' => 'Основная форма: объект, стороны, цена, даты, статус.',
        '<div class="mb-6">' => 'Блок выбора объявления — тип подтягивается в JS.',
        '<div class="grid grid-cols-2 gap-6 mb-6">' => 'Клиент и риелтор — обязательные участники договора.',
        '<div class="mb-6" id="rent_end_block_admin"' => 'Дата окончания — только для аренды.',
        '<div class="divider pt-6' => 'Кнопки «Отмена» и отправка формы.',
        '<script>' => 'На странице: тип и подписи цены зависят от продажи или аренды.',
    ],
    'admin/contracts/edit.blade.php' => [
        '<form method="POST"' => 'Редактирование существующего договора.',
        '<script>' => 'Переключение полей аренды и подписей цены.',
    ],
    'contracts/create.blade.php' => [
        '<form method="POST"' => 'Клиент или риелтор создаёт договор по объявлению.',
        '<script>' => 'Синхронизация типа договора с типом объявления.',
    ],
    'contracts/show.blade.php' => [
        '@auth' => 'Действия доступны только авторизованным участникам сделки.',
        '<form method="POST"' => 'Кнопки подтверждения, отклонения, загрузки скана.',
    ],
    'properties/show.blade.php' => [
        '<div class="grid' => 'Галерея и основная информация об объекте.',
        '@auth' => 'Избранное и кнопки владельца/модератора.',
        '@include' => 'Журнал изменений по объявлению.',
    ],
    'properties/index.blade.php' => [
        '<form method="GET"' => 'Фильтры каталога: поиск, тип, цена.',
        '@forelse' => 'Сетка карточек объявлений.',
    ],
    'properties/create.blade.php' => [
        '<form method="POST"' => 'Новое объявление: DaData для города и адреса.',
        'type="file"' => 'Загрузка фотографий (несколько файлов).',
    ],
    'properties/edit.blade.php' => [
        '<form method="POST"' => 'Изменение полей и статуса публикации.',
    ],
    'layouts/app.blade.php' => [
        '<header' => 'Шапка: логотип и меню по роли пользователя.',
        '<main' => 'Контент конкретной страницы (@yield content).',
        '@include' => 'Подвал сайта.',
    ],
    'layouts/navigation.blade.php' => [
        '<nav' => 'Ссылки личного кабинета (Breeze).',
    ],
    'cabinet/index.blade.php' => [
        '@if' => 'Блоки кабинета зависят от роли: клиент, риелтор, админ.',
    ],
    'moderation/index.blade.php' => [
        '@forelse' => 'Очередь объявлений на проверку.',
    ],
    'admin/dashboard.blade.php' => [
        '<div class="grid' => 'Плитки со статистикой и последние записи.',
    ],
];

function hasNearbyComment(string $content, int $pos): bool
{
    $before = substr($content, max(0, $pos - 400), min(400, $pos));
    return (bool) preg_match('/\{\{--[^-]*--\}\}\s*$/s', $before);
}

$updated = 0;
foreach ($sections as $rel => $markers) {
    $path = $base . '/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if (!is_file($path)) {
        echo "SKIP missing {$rel}\n";
        continue;
    }
    $content = file_get_contents($path);
    $changed = false;
    foreach ($markers as $anchor => $comment) {
        $pos = strpos($content, $anchor);
        if ($pos === false) {
            continue;
        }
        if (hasNearbyComment($content, $pos)) {
            continue;
        }
        $insert = "{{-- {$comment} --}}\n        ";
        $content = substr_replace($content, $insert, $pos, 0);
        $changed = true;
    }
    if ($changed) {
        file_put_contents($path, $content);
        $updated++;
        echo "OK {$rel}\n";
    }
}

echo "updated_files={$updated}\n";
