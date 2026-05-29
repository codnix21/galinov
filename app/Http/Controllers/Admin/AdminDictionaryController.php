<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\ContractStatus;
use App\Models\PropertyStatus;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminDictionaryController extends Controller
{
    public function index(): View
    {
        $this->assertAdmin();

        return view('admin.dictionaries.index', [
            'roles' => Role::orderBy('id')->get(),
            'cities' => City::orderBy('nazvanie')->paginate(20, ['*'], 'cities_page'),
            'propertyStatuses' => PropertyStatus::orderBy('id')->get(),
            'contractStatuses' => ContractStatus::orderBy('id')->get(),
        ]);
    }

    public function storeCity(Request $request): RedirectResponse
    {
        $this->assertAdmin();
        $validated = $request->validate([
            'nazvanie' => ['required', 'string', 'max:255', 'unique:goroda,nazvanie'],
        ], ['nazvanie.unique' => 'Такой город уже есть в справочнике.']);

        City::create($validated);

        return back()->with('success', 'Город добавлен.');
    }

    public function destroyCity(City $city): RedirectResponse
    {
        $this->assertAdmin();
        if ($city->properties()->exists()) {
            return back()->with('error', 'Нельзя удалить город: есть связанные объявления.');
        }
        $city->delete();

        return back()->with('success', 'Город удалён.');
    }

    public function storePropertyStatus(Request $request): RedirectResponse
    {
        $this->assertAdmin();
        $validated = $request->validate([
            'kod' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/', 'unique:statusy_obyavleniy,kod'],
            'nazvanie' => ['required', 'string', 'max:100'],
        ], [
            'kod.regex' => 'Код: только латиница, цифры и подчёркивание.',
            'kod.unique' => 'Такой код статуса уже существует.',
        ]);

        PropertyStatus::create($validated);
        PropertyStatus::forgetKodIdCache();

        return back()->with('success', 'Статус объявления добавлен.');
    }

    public function updatePropertyStatus(Request $request, PropertyStatus $propertyStatus): RedirectResponse
    {
        $this->assertAdmin();
        $validated = $request->validate([
            'nazvanie' => ['required', 'string', 'max:100'],
        ]);

        $propertyStatus->update($validated);
        PropertyStatus::forgetKodIdCache();

        return back()->with('success', 'Статус объявления обновлён.');
    }

    public function storeContractStatus(Request $request): RedirectResponse
    {
        $this->assertAdmin();
        $validated = $request->validate([
            'kod' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/', 'unique:statusy_dogovorov,kod'],
            'nazvanie' => ['required', 'string', 'max:100'],
        ], [
            'kod.regex' => 'Код: только латиница, цифры и подчёркивание.',
            'kod.unique' => 'Такой код статуса уже существует.',
        ]);

        ContractStatus::create($validated);
        ContractStatus::forgetKodIdCache();

        return back()->with('success', 'Статус договора добавлен.');
    }

    public function updateContractStatus(Request $request, ContractStatus $contractStatus): RedirectResponse
    {
        $this->assertAdmin();
        $validated = $request->validate([
            'nazvanie' => ['required', 'string', 'max:100'],
        ]);

        $contractStatus->update($validated);
        ContractStatus::forgetKodIdCache();

        return back()->with('success', 'Статус договора обновлён.');
    }

    private function assertAdmin(): void
    {
        if (!Auth::user()?->isAdmin()) {
            abort(403);
        }
    }
}
