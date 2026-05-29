<?php

/**
 * Ключи внешних сервисов: почта, DaData (подсказки адреса), Яндекс.Геокодер.
 * Значения берутся из .env — см. DADATA_API_KEY, YANDEX_GEOCODER_API_KEY.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Подсказки адресов и городов (форма объявления)
    'dadata' => [
        'api_key' => env('DADATA_API_KEY'),
        'secret_key' => env('DADATA_SECRET_KEY'),
    ],

    // Геокодер и JavaScript API Яндекс.Карт (один ключ в кабинете разработчика)
    'yandex_maps' => [
        'api_key' => env('YANDEX_MAPS_API_KEY', env('YANDEX_GEOCODER_API_KEY')),
        'geocoder_api_key' => env('YANDEX_GEOCODER_API_KEY'),
    ],

    // Telegram Bot API — уведомления риэлторам и клиентам
    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'bot_username' => env('TELEGRAM_BOT_USERNAME'),
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
    ],

    // Автопроверка документов на объект (демо-режим)
    'registry' => [
        'demo_mode' => env('DOCUMENT_VERIFICATION_DEMO', true),
    ],

    'robokassa' => [
        'login' => env('ROBOKASSA_LOGIN'),
        'password1' => env('ROBOKASSA_PASSWORD1'),
        'password2' => env('ROBOKASSA_PASSWORD2'),
        'test' => env('ROBOKASSA_TEST', true),
        'hash' => env('ROBOKASSA_HASH', 'md5'),
        'payment_url' => env('ROBOKASSA_PAYMENT_URL', 'https://auth.robokassa.ru/Merchant/Index.aspx'),
    ],

    'payment' => [
        'test_gateway' => env('PAYMENT_TEST_GATEWAY', false),
    ],

];
