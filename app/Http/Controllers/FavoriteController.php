<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\PropertyStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Избранные объявления текущего пользователя (добавить / убрать / список).
 */
class FavoriteController extends Controller
{
    /**
     * Список избранного — только активные (опубликованные) объявления.
     */
    public function index(): View
    {
        $user = Auth::user();
        
        // Показываем только активные объявления (не sold, не inactive)
        $activeId = PropertyStatus::idFor('active');
        $favorites = $user->favorites()
            ->whereHas('property', function ($query) use ($activeId) {
                if ($activeId !== null) {
                    $query->where('status_obyavleniya_id', $activeId);
                } else {
                    $query->whereRaw('1 = 0');
                }
            })
            ->with(['property.user', 'property.images'])
            ->latest()
            ->paginate(12);
        
        return view('favorites.index', compact('favorites'));
    }

    /**
     * Добавить объявление в избранное (только если статус «активно»).
     */
    public function store(Property $property)
    {
        $user = Auth::user();

        $st = $property->status_obyavleniya ?? $property->status;
        if ($st !== 'active') {
            if (request()->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'В избранное можно добавить только опубликованное объявление'], 422);
            }
            return redirect()->back()->withErrors(['favorite' => 'В избранное можно добавить только опубликованное объявление']);
        }

        // Проверяем, не добавлено ли уже в избранное
        if (!$user->favorites()->where('nedvizhimost_id', $property->id)->exists()) {
            $user->favorites()->create([
                'nedvizhimost_id' => $property->id,
            ]);
        }

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Объявление добавлено в избранное']);
        }

        return redirect()->back()->with('success', 'Объявление добавлено в избранное');
    }

    /**
     * Убрать объявление из избранного.
     */
    public function destroy(Request $request, Property $property)
    {
        $user = Auth::user();
        $user->favorites()->where('nedvizhimost_id', $property->id)->delete();

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Объявление удалено из избранного']);
        }

        return redirect()->back()->with('success', 'Объявление удалено из избранного');
    }
}
