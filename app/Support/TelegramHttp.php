<?php

namespace App\Support;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * HTTP-клиент для api.telegram.org (прокси и SSL из .env).
 */
final class TelegramHttp
{
    public static function client(int $timeout = 20): PendingRequest
    {
        $options = [];

        if (! filter_var(config('services.http_verify_ssl', true), FILTER_VALIDATE_BOOL)) {
            $options['verify'] = false;
        }

        $proxy = config('services.telegram.proxy');
        if (is_string($proxy) && $proxy !== '') {
            $options['proxy'] = $proxy;
        }

        $http = Http::timeout($timeout);

        return $options !== [] ? $http->withOptions($options) : $http;
    }

    public static function apiUrl(string $method): string
    {
        $base = rtrim((string) config('services.telegram.api_base', 'https://api.telegram.org'), '/');
        $token = (string) config('services.telegram.bot_token');

        return "{$base}/bot{$token}/{$method}";
    }
}
