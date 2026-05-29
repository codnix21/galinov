<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\User;
use App\Models\UserDocument;
use App\Services\DocumentVerificationService;
use App\Support\DocumentStorage;
use App\Support\PropertyListingAuthor;
use App\Support\UserProfileDocuments;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Загрузка и проверка документов продавца.
 */
class UserDocumentController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user()->load('personalData');

        $documents = UserDocument::query()
            ->where('polzovatel_id', $user->id)
            ->whereNull('nedvizhimost_id')
            ->orderByDesc('sozdano_at')
            ->get();

        $documentsByTip = $documents->groupBy('tip');

        $listingProperties = Property::query()
            ->where('polzovatel_id', $user->id)
            ->orderByDesc('sozdano_at')
            ->limit(10)
            ->get(['id', 'nazvanie', 'status_obyavleniya_id']);

        return view('profile.documents', [
            'user' => $user,
            'documents' => $documents,
            'documentsByTip' => $documentsByTip,
            'tipLabels' => UserDocument::tipLabels(),
            'profileDocs' => UserProfileDocuments::summary($user),
            'personalDataFilled' => UserProfileDocuments::hasPersonalDataFilled($user),
            'listingProperties' => $listingProperties,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $profileTips = ['passport', 'inn'];
        $validated = $request->validate([
            'tip' => ['required', 'string', 'in:'.implode(',', $profileTips)],
            'nazvanie' => ['nullable', 'string', 'max:255'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $user = $request->user();
        $tip = $validated['tip'];

        UserDocument::query()
            ->where('polzovatel_id', $user->id)
            ->whereNull('nedvizhimost_id')
            ->where('tip', $tip)
            ->delete();

        $path = $request->file('file')->store('documents/'.$user->id, 'public');
        $defaultNames = [
            'passport' => 'Паспорт (скан)',
            'inn' => 'ИНН / СНИЛС',
        ];

        $document = UserDocument::create([
            'polzovatel_id' => $user->id,
            'tip' => $tip,
            'nazvanie' => $validated['nazvanie'] ?? ($defaultNames[$tip] ?? $tip),
            'put_fajla' => $path,
            'status' => 'pending',
        ]);

        app(DocumentVerificationService::class)->submitForExternalCheck($document);

        $label = $defaultNames[$tip] ?? 'Документ';

        return redirect()
            ->to(route('profile.documents.index') . '#step-' . $tip)
            ->with('success', $label . ' отправлен на проверку.');
    }

    public function viewFile(Request $request, UserDocument $document): BinaryFileResponse
    {
        $user = $request->user();
        if (!$user || !$this->canViewDocument($user, $document)) {
            abort(403, 'Нет доступа к этому документу.');
        }

        $absolute = DocumentStorage::absolutePath($document->put_fajla);
        if ($absolute === null) {
            abort(404, 'Файл не найден на сервере. Попросите собственника загрузить документ повторно.');
        }

        $mime = mime_content_type($absolute) ?: 'application/octet-stream';

        return response()->file($absolute, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . basename($absolute) . '"',
        ]);
    }

    public function moderationIndex(Request $request): View
    {
        if (!$request->user()->isStaff()) {
            abort(403);
        }

        $q = trim((string) $request->input('q', ''));
        $status = (string) $request->input('status', 'queue');
        $tip = (string) $request->input('tip', '');
        $scope = (string) $request->input('scope', '');
        $sort = (string) $request->input('sort', 'queue');
        $dir = $request->input('dir', 'asc') === 'desc' ? 'desc' : 'asc';

        $qb = UserDocument::query()->with(['user', 'property']);

        if ($q !== '') {
            $escaped = addcslashes($q, '%_\\');
            $qb->where(function ($w) use ($escaped, $q) {
                $w->where('nazvanie', 'like', '%'.$escaped.'%')
                    ->orWhereHas('user', function ($u) use ($escaped) {
                        $u->where('familia', 'like', '%'.$escaped.'%')
                            ->orWhere('imya', 'like', '%'.$escaped.'%')
                            ->orWhere('otchestvo', 'like', '%'.$escaped.'%')
                            ->orWhere('email_polzovatela', 'like', '%'.$escaped.'%');
                    })
                    ->orWhereHas('property', function ($p) use ($escaped) {
                        $p->where('nazvanie', 'like', '%'.$escaped.'%')
                            ->orWhere('adres_ulitsy', 'like', '%'.$escaped.'%');
                    });
                if (ctype_digit($q)) {
                    $w->orWhere('nedvizhimost_id', (int) $q);
                }
            });
        }

        if ($status === 'queue') {
            $qb->whereIn('status', ['pending', 'checking', 'rejected']);
        } elseif ($status !== '' && $status !== 'all') {
            $qb->where('status', $status);
        }

        if ($tip !== '') {
            $qb->where('tip', $tip);
        }

        if ($scope === 'profile') {
            $qb->whereNull('nedvizhimost_id');
        } elseif ($scope === 'property') {
            $qb->whereNotNull('nedvizhimost_id');
        }

        if ($sort === 'client') {
            $qb->join('polzovateli as doc_client', 'doc_client.id', '=', 'dokumenty_proverki.polzovatel_id')
                ->orderBy('doc_client.familia', $dir)
                ->orderBy('doc_client.imya', $dir)
                ->select('dokumenty_proverki.*');
        } elseif ($sort === 'newest') {
            $qb->orderByDesc('sozdano_at');
        } elseif ($sort === 'oldest') {
            $qb->orderBy('sozdano_at');
        } else {
            $qb->orderByRaw("FIELD(status, 'pending', 'checking', 'rejected', 'verified')")
                ->orderByDesc('sozdano_at');
        }

        $documents = $qb->paginate(30)->withQueryString();

        return view('moderation.documents', [
            'documents' => $documents,
            'tipLabels' => UserDocument::tipLabels(),
            'q' => $q,
            'status' => $status,
            'tip' => $tip,
            'scope' => $scope,
            'sort' => $sort,
            'dir' => $dir,
        ]);
    }

    public function verify(Request $request, UserDocument $document): RedirectResponse
    {
        if (!$request->user()->isStaff()) {
            abort(403);
        }

        $validated = $request->validate([
            'action' => ['required', 'in:verify,reject'],
            'kommentariy_mod' => ['nullable', 'string', 'max:1000'],
        ]);

        $document->update([
            'status' => $validated['action'] === 'verify' ? 'verified' : 'rejected',
            'kommentariy_mod' => $validated['kommentariy_mod'] ?? null,
            'provereno_at' => now(),
        ]);

        return back()->with('success', 'Статус документа обновлён.');
    }

    public function recheck(Request $request, UserDocument $document): RedirectResponse
    {
        if (!$request->user()->isStaff()) {
            abort(403);
        }

        app(DocumentVerificationService::class)->recheck($document);

        return back()->with('success', 'Повторная автопроверка выполнена.');
    }

    private function canViewDocument(User $user, UserDocument $document): bool
    {
        if ($user->isStaff()) {
            return true;
        }

        if ((int) $document->polzovatel_id === (int) $user->id) {
            return true;
        }

        if ($document->nedvizhimost_id) {
            $property = Property::find($document->nedvizhimost_id);

            return $property && PropertyListingAuthor::canManage($user, $property);
        }

        return false;
    }
}
