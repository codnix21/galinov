<?php

/**
 * Точка входа с веб-сервера: все запросы к сайту попадают сюда,
 * дальше Laravel обрабатывает маршруты из routes/web.php.
 */

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Режим обслуживания: если есть файл maintenance — показываем заглушку
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Подключаем библиотеки Composer
require __DIR__.'/../vendor/autoload.php';

// Запускаем приложение и отдаём ответ браузеру
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
