<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Services\PropertyOwnersService;
use App\Support\PropertyListingAuthor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PropertyOwnerController extends Controller
{
    public function update(Request $request, Property $property): RedirectResponse
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }
        if (!$user->isAdmin() && !PropertyListingAuthor::canManage($user, $property)) {
            abort(403);
        }

        $rows = $request->input('owners', []);
        if (!is_array($rows)) {
            $rows = [];
        }

        $errors = PropertyOwnersService::validateRows($rows);
        if ($errors !== []) {
            return redirect()
                ->back()
                ->withErrors($errors)
                ->withInput()
                ->withFragment('sobstvenniki');
        }

        PropertyOwnersService::sync($property, $rows);

        return redirect()
            ->back()
            ->with('success', 'Собственники объекта сохранены.')
            ->withFragment('sobstvenniki');
    }
}
