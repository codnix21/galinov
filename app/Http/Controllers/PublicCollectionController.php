<?php

namespace App\Http\Controllers;

use App\Models\PropertyCollection;
use Illuminate\View\View;

/**
 * Публичная страница подборки по ссылке (без входа).
 */
class PublicCollectionController extends Controller
{
    public function show(string $token): View
    {
        $collection = PropertyCollection::query()
            ->where('token', $token)
            ->where('aktivna', true)
            ->with([
                'realtor',
                'items.property.images',
                'items.property.cityRelation',
            ])
            ->firstOrFail();

        return view('collections.public', compact('collection'));
    }
}
