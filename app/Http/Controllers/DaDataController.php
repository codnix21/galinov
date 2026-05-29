<?php

namespace App\Http\Controllers;

use App\Services\DaDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * JSON-API для подсказок адреса и города (сервис DaData).
 * Вызывается из форм объявлений при вводе адреса.
 */
class DaDataController extends Controller
{
    /** Сервис запросов к API DaData (ключи из config/services.php) */
    private DaDataService $daDataService;

    /** Подставляем сервис из контейнера Laravel */
    public function __construct(DaDataService $daDataService)
    {
        $this->daDataService = $daDataService;
    }

    /**
     * Подсказки по полному адресу (с учётом выбранного города, если передан).
     */
    public function suggestAddress(Request $request): JsonResponse
    {
        $query = $request->input('query', '');
        
        if (empty($query)) {
            return response()->json([]);
        }

        try {
            $suggestions = $this->daDataService->suggestAddress(
                $query,
                10,
                $request->filled('city_kladr_id') ? (string) $request->input('city_kladr_id') : null,
                $request->filled('city_fias_id') ? (string) $request->input('city_fias_id') : null,
            );
            
            $results = array_map(function ($suggestion) {
                return [
                    'value' => $suggestion['value'] ?? '',
                    'unrestricted_value' => $suggestion['unrestricted_value'] ?? '',
                    'data' => $suggestion['data'] ?? [],
                ];
            }, $suggestions);

            return response()->json($results);
        } catch (\Exception $e) {
            \Log::error('DaData API error in controller', [
                'message' => $e->getMessage(),
                'query' => $query,
            ]);
            
            return response()->json([
                'error' => 'Ошибка при получении подсказок. Проверьте настройки API ключей DaData.'
            ], 500);
        }
    }

    /**
     * Подсказки только по городу.
     */
    public function suggestCity(Request $request): JsonResponse
    {
        $query = $request->input('query', '');
        
        if (empty($query)) {
            return response()->json([]);
        }

        try {
            $suggestions = $this->daDataService->suggestCity($query);
            
            $results = array_map(function ($suggestion) {
                return [
                    'value' => $suggestion['value'] ?? '',
                    'unrestricted_value' => $suggestion['unrestricted_value'] ?? '',
                    'data' => $suggestion['data'] ?? [],
                ];
            }, $suggestions);

            return response()->json($results);
        } catch (\Exception $e) {
            \Log::error('DaData API error in controller', [
                'message' => $e->getMessage(),
                'query' => $query,
            ]);
            
            return response()->json([
                'error' => 'Ошибка при получении подсказок. Проверьте настройки API ключей DaData.'
            ], 500);
        }
    }
}
