<?php

namespace App\Http\Controllers;

use App\Support\PropertyCatalogFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Старый URL /search — редирект в каталог объявлений (поиск только там).
 */
class SearchController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        $q = trim((string) $request->input('q', $request->input('search', '')));

        $params = array_filter([
            'search' => $q !== '' ? $q : null,
            'sort' => $request->string('sort')->toString() ?: PropertyCatalogFilter::SORT_NEWEST,
            'type' => $request->input('type'),
            'operation' => $request->input('operation'),
            'city_id' => $request->input('city_id'),
            'min_price' => $request->input('min_price'),
            'max_price' => $request->input('max_price'),
        ], fn ($v) => $v !== null && $v !== '');

        return redirect()->route('properties.index', $params);
    }
}
