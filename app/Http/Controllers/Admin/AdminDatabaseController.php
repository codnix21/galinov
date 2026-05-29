<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DatabaseBackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminDatabaseController extends Controller
{
    public function __construct(private DatabaseBackupService $backups)
    {
    }

    public function index(): View
    {
        $this->assertAdmin();

        return view('admin.database.index', [
            'backups' => $this->backups->listBackups(),
            'driver' => config('database.default'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->assertAdmin();

        try {
            $name = $this->backups->createBackup();

            return back()->with('success', 'Резервная копия создана: '.$name);
        } catch (\Throwable $e) {
            return back()->with('error', 'Не удалось создать копию: '.$e->getMessage());
        }
    }

    public function download(string $file): BinaryFileResponse
    {
        $this->assertAdmin();
        $path = $this->backups->resolvePath($file);

        return response()->download($path, $file);
    }

    public function restore(Request $request): RedirectResponse
    {
        $this->assertAdmin();

        $validated = $request->validate([
            'backup_file' => ['required_without:upload_sql', 'nullable', 'string', 'max:255'],
            'upload_sql' => ['required_without:backup_file', 'nullable', 'file', 'mimes:sql,txt,sqlite', 'max:51200'],
            'confirm_restore' => ['accepted'],
        ], [
            'confirm_restore.accepted' => 'Подтвердите, что понимаете риск перезаписи данных.',
        ]);

        try {
            if ($request->hasFile('upload_sql')) {
                $uploadName = 'upload_'.now()->format('Y-m-d_His').'.sql';
                $request->file('upload_sql')->move($this->backups->backupDirectory(), $uploadName);
                $this->backups->restoreFromFile($uploadName);
            } else {
                $this->backups->restoreFromFile($validated['backup_file']);
            }

            return back()->with('success', 'База данных восстановлена из резервной копии.');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Ошибка восстановления: '.$e->getMessage());
        }
    }

    private function assertAdmin(): void
    {
        if (!Auth::user()?->isAdmin()) {
            abort(403);
        }
    }
}
