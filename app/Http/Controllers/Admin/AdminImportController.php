<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PropertyImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminImportController extends Controller
{
    public function index(): View
    {
        $this->assertAdmin();

        return view('admin.import.index');
    }

    public function template(): StreamedResponse
    {
        $this->assertAdmin();

        $headers = ['nazvanie', 'tsena', 'gorod', 'adres', 'tip', 'operatsiya', 'status_kod', 'email_vladelca', 'opisanie'];
        $sample = ['Квартира 2к', '5500000', 'Иркутск', 'ул. Ленина, 1', 'apartment', 'sale', 'draft', '', 'Импортировано из шаблона'];

        return response()->streamDownload(function () use ($headers, $sample) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, $headers, ';');
            fputcsv($out, $sample, ';');
            fclose($out);
        }, 'shablon_importa_obyavleniy.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function store(Request $request, PropertyImportService $import): RedirectResponse
    {
        $this->assertAdmin();

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:10240'],
        ], [
            'file.required' => 'Выберите файл CSV или XLSX.',
            'file.mimes' => 'Допустимы форматы CSV и XLSX.',
        ]);

        try {
            $result = $import->import($validated['file'], $request->user());
            $msg = "Импортировано: {$result['imported']}, пропущено: {$result['skipped']}.";
            if ($result['errors'] !== []) {
                $msg .= ' Ошибки: '.implode(' | ', $result['errors']);
            }

            return back()->with($result['ok'] ? 'success' : 'warning', $msg);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Импорт не выполнен: '.$e->getMessage());
        }
    }

    private function assertAdmin(): void
    {
        if (!Auth::user()?->isAdmin()) {
            abort(403);
        }
    }
}
