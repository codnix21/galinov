<?php

/**
 * Добавляет файловый комментарий в начало PHP-файлов app/, если его ещё нет.
 * Запуск: php tools/add_php_comments.php
 *
 * Подробные комментарии к методам добавляются вручную в моделях и контроллерах.
 */

$base = dirname(__DIR__) . '/app';

$hints = [
    'Http/Controllers/Controller.php' => 'Базовый контроллер приложения — общие настройки для всех контроллеров.',
    'View/Components/AppLayout.php' => 'Layout-компонент для авторизованных страниц (обёртка layouts.app).',
    'View/Components/GuestLayout.php' => 'Layout для гостей: вход и регистрация без бокового меню.',
];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS)
);

$updated = 0;
foreach ($iterator as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }
    $path = $file->getPathname();
    $rel = str_replace('\\', '/', substr($path, strlen($base) + 1));
    $content = file_get_contents($path);

    if (preg_match('/^\s*<\?php\s*\n\s*\/\*\*/s', $content)) {
        continue;
    }
    if (!preg_match('/^namespace\s+/m', $content)) {
        continue;
    }

    $text = $hints[$rel] ?? null;
    if ($text === null) {
        continue;
    }

    $header = "<?php\n\n/**\n * {$text}\n */\n\n";
    $content = preg_replace('/^<\?php\s*\n+/', '', $content, 1);
    file_put_contents($path, $header . $content);
    $updated++;
    echo "OK {$rel}\n";
}

echo "updated={$updated}\n";
