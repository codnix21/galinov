<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Подсказки адресов и городов через API DaData (автодополнение в формах).
 */
class DaDataService
{
    private string $apiKey;
    private string $secretKey;
    private string $baseUrl = 'https://suggestions.dadata.ru/suggestions/api/4_1/rs';

    /**
     * Ключи API берутся из config/services.dadata.
     */
    public function __construct()
    {
        $this->apiKey = config('services.dadata.api_key', '');
        $this->secretKey = config('services.dadata.secret_key', '');
    }

    /**
     * Получить подсказки по адресу (улица, дом).
     *
     * @param  ?non-empty-string  $cityKladrId  КЛАДР населённого пункта — ограничивает поиск (из подсказки города).
     * @param  ?non-empty-string  $cityFiasId   ФИАС города, если КЛАДР недоступен.
     */
    public function suggestAddress(string $query, int $count = 10, ?string $cityKladrId = null, ?string $cityFiasId = null): array
    {
        if (empty($this->apiKey) || empty($this->secretKey)) {
            Log::warning('DaData API keys not configured');
            return [];
        }

        try {
            $body = [
                'query' => $query,
                'count' => $count,
                'restrict_value' => false,
            ];

            if ($cityKladrId !== null && $cityKladrId !== '') {
                $body['locations'] = [['kladr_id' => $cityKladrId]];
            } elseif ($cityFiasId !== null && $cityFiasId !== '') {
                $body['locations'] = [['fias_id' => $cityFiasId]];
            }

            $response = Http::withoutVerifying()->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Token ' . $this->apiKey,
                'X-Secret' => $this->secretKey,
            ])->post("{$this->baseUrl}/suggest/address", $body);

            if ($response->successful()) {
                $data = $response->json();
                return $data['suggestions'] ?? [];
            }

            Log::error('DaData API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error('DaData API exception', [
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Получить подсказки по городу
     */
    public function suggestCity(string $query, int $count = 10): array
    {
        if (empty($this->apiKey) || empty($this->secretKey)) {
            Log::warning('DaData API keys not configured');
            return [];
        }

        try {
            $response = Http::withoutVerifying()->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Token ' . $this->apiKey,
                'X-Secret' => $this->secretKey,
            ])->post("{$this->baseUrl}/suggest/address", [
                'query' => $query,
                'count' => $count,
                'from_bound' => ['value' => 'city'],
                // до settlement — иначе пгт/посёлки не попадают в подсказки «Город»
                'to_bound' => ['value' => 'settlement'],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['suggestions'] ?? [];
            }

            return [];
        } catch (\Exception $e) {
            Log::error('DaData API exception', [
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }
}


