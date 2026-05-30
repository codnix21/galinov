<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PersonalDataExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PersonalDataExportController extends Controller
{
    public function exportSelf(Request $request): BinaryFileResponse|RedirectResponse
    {
        return $this->downloadZip(Auth::user(), route('profile.edit'));
    }

    public function exportUser(User $user): BinaryFileResponse|RedirectResponse
    {
        if (! Auth::user()->isAdmin()) {
            abort(403);
        }

        return $this->downloadZip($user, route('admin.users.edit', $user));
    }

    private function downloadZip(User $user, string $backRoute): BinaryFileResponse|RedirectResponse
    {
        try {
            $path = PersonalDataExportService::storeZip($user);
        } catch (RuntimeException $e) {
            return redirect($backRoute)->with('error', $e->getMessage());
        }

        return response()->download($path, 'personal_data_'.$user->id.'.zip')->deleteFileAfterSend(true);
    }
}
