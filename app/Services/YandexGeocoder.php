<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Резервное определение координат по строке адреса через Yandex Geocoder API.
 */
final class YandexGeocoder
{
    /**
     * Широта и долгота по строке адреса; результат кэшируется на неделю.
     *
     * @return array{lat: float, lon: float}|null
     */
    public function coordinatesForQuery(string $query): ?array
    {
        $query = trim($query);
        if ($query === '') {
            return null;
        }

        $key = 'yandex_geocoder:'.md5(mb_strtolower($query));

        return Cache::remember($key, 86400 * 7, function () use ($query) {
            try {
                $params = [
                    'format' => 'json',
                    'geocode' => $query,
                    'results' => 1,
                ];

                $apiKey = (string) config('services.yandex_maps.geocoder_api_key', '');
                if ($apiKey !== '') {
                    $params['apikey'] = $apiKey;
                }

                $response = Http::timeout(8)->get('https://geocode-maps.yandex.ru/1.x/', $params);
                if (! $response->successful()) {
                    return null;
                }

                $json = $response->json();
                $pos = data_get($json, 'response.GeoObjectCollection.featureMember.0.GeoObject.Point.pos');
                if (! is_string($pos) || trim($pos) === '') {
                    return null;
                }

                // Формат pos у Яндекса: "lon lat"
                $parts = preg_split('/\s+/', trim($pos));
                if (! is_array($parts) || count($parts) !== 2) {
                    return null;
                }

                return [
                    'lat' => (float) $parts[1],
                    'lon' => (float) $parts[0],
                ];
            } catch (\Throwable) {
                return null;
            }
        });
    }
}
