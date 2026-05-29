<?php

/**
 * Одноразовый скрипт: добавляет понятный комментарий в начало каждого .blade.php.
 * Запуск: php tools/add_blade_comments.php
 */

$base = dirname(__DIR__) . '/resources/views';

$hints = [
    'layouts/app' => 'Основной макет сайта: шапка, меню, подвал, подключение стилей и скриптов.',
    'layouts/guest' => 'Макет для гостей: вход, регистрация — без бокового меню кабинета.',
    'layouts/navigation' => 'Верхнее меню: ссылки зависят от роли (клиент, риелтор, админ).',
    'welcome' => 'Главная страница для всех посетителей.',
    'properties/index' => 'Каталог объявлений: поиск, фильтры, карточки объектов.',
    'properties/show' => 'Страница одного объявления с фото и описанием.',
    'properties/create' => 'Форма добавления нового объявления.',
    'properties/edit' => 'Редактирование своего объявления.',
    'properties/drafts' => 'Список черновиков пользователя.',
    'cabinet/index' => 'Личный кабинет: сводка по роли.',
    'moderation/index' => 'Очередь объявлений на проверку модератором.',
    'favorites/index' => 'Избранные объявления пользователя.',
    'contracts/index' => 'Список договоров пользователя.',
    'contracts/create' => 'Создание нового договора.',
    'contracts/show' => 'Просмотр договора и действия по статусу.',
    'contracts/pending' => 'Договоры, ожидающие подтверждения риелтором.',
    'contracts/print-rent' => 'Печатная форма договора аренды.',
    'admin/dashboard' => 'Главная страница админ-панели со статистикой.',
    'admin/users/index' => 'Список пользователей в админке.',
    'admin/users/create' => 'Создание пользователя администратором.',
    'admin/users/edit' => 'Редактирование пользователя.',
    'admin/properties/index' => 'Все объявления в админке.',
    'admin/properties/create' => 'Админ создаёт объявление.',
    'admin/properties/edit' => 'Админ редактирует объявление.',
    'admin/contracts/index' => 'Все договоры в админке.',
    'admin/contracts/create' => 'Создание договора в админке.',
    'admin/contracts/edit' => 'Редактирование договора.',
    'admin/contracts/pdf' => 'HTML-шаблон PDF договора.',
    'admin/reports/index' => 'Отчёты: фильтры и кнопки экспорта.',
    'admin/reports/pdf' => 'PDF-версия отчёта.',
    'auth/login' => 'Форма входа в аккаунт.',
    'auth/register' => 'Регистрация нового пользователя.',
    'auth/forgot-password' => 'Запрос ссылки для сброса пароля.',
    'auth/reset-password' => 'Ввод нового пароля по ссылке из письма.',
    'auth/verify-email' => 'Напоминание подтвердить email.',
    'auth/confirm-password' => 'Повторный ввод пароля для опасных действий.',
    'profile/edit' => 'Настройки профиля: имя, email, пароль.',
    'profile/partials/update-profile-information-form' => 'Блок смены ФИО, телефона, аватара.',
    'profile/partials/update-password-form' => 'Блок смены пароля.',
    'profile/partials/delete-user-form' => 'Удаление своего аккаунта.',
    'pages/help' => 'Страница «Помощь».',
    'pages/about-contacts' => 'О компании и контакты.',
    'pages/mortgage-calculator' => 'Ипотечный калькулятор.',
    'pages/about' => 'О нас (если используется отдельно).',
    'pages/contacts' => 'Контакты.',
    'partials/property-zhurnal' => 'Журнал изменений по объявлению.',
    'partials/help-hint' => 'Подсказка со знаком вопроса.',
    'dashboard' => 'Редиректная/старая страница dashboard Breeze.',
    'components/footer' => 'Подвал сайта.',
    'components/primary-button' => 'Зелёная основная кнопка.',
    'components/secondary-button' => 'Второстепенная кнопка.',
    'components/danger-button' => 'Красная кнопка опасного действия.',
    'components/modal' => 'Всплывающее окно (Alpine.js).',
    'components/dropdown' => 'Выпадающее меню.',
    'components/dropdown-link' => 'Пункт выпадающего меню.',
    'components/nav-link' => 'Ссылка в навигации.',
    'components/responsive-nav-link' => 'Ссылка меню на мобильном.',
    'components/text-input' => 'Поле ввода текста.',
    'components/input-label' => 'Подпись к полю формы.',
    'components/input-error' => 'Текст ошибки валидации под полем.',
    'components/auth-session-status' => 'Сообщение после входа/выхода.',
    'components/application-logo' => 'Логотип в шапке.',
];

function defaultHint(string $rel): string
{
    $name = basename($rel, '.blade.php');
    $dir = dirname($rel);
    if ($name === 'index') {
        return "Страница списка: {$dir}.";
    }
    if ($name === 'create') {
        return "Форма создания: {$dir}.";
    }
    if ($name === 'edit') {
        return "Форма редактирования: {$dir}.";
    }
    if ($name === 'show') {
        return "Просмотр записи: {$dir}.";
    }

    return "Шаблон представления: {$rel}.";
}

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS)
);

$updated = 0;
foreach ($iterator as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php' || !str_ends_with($file->getFilename(), '.blade.php')) {
        continue;
    }
    $path = $file->getPathname();
    $rel = str_replace('\\', '/', substr($path, strlen($base) + 1));
    $relKey = preg_replace('/\.blade\.php$/', '', $rel);

    $content = file_get_contents($path);
    if (preg_match('/^\s*\{\{--/s', $content)) {
        continue;
    }

    $text = $hints[$relKey] ?? defaultHint($rel);
    $header = "{{-- {$text} --}}\n";
    file_put_contents($path, $header . $content);
    $updated++;
    echo "OK {$rel}\n";
}

echo "updated={$updated}\n";
