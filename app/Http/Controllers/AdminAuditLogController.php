<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Property;
use App\Models\User;
use App\Models\ZhurnalIzmeneniy;
use App\Support\AuditJournalDisplay;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Журнал изменений системы — просмотр и поиск для администратора.
 */
class AdminAuditLogController extends Controller
{
    private function checkAdmin(): void
    {
        if (!Auth::check() || !Auth::user()->isAdmin()) {
            abort(403, 'Доступ запрещен');
        }
    }

    public function index(Request $request): View
    {
        $this->checkAdmin();

        $request->validate([
            'search' => 'nullable|string|max:200',
            'deystvie' => 'nullable|string|max:64',
            'obyekt' => 'nullable|in:property,contract,user',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $query = ZhurnalIzmeneniy::query()
            ->with('polzovatel')
            ->orderByDesc('sozdano_at');

        if ($request->filled('date_from')) {
            $query->whereDate('sozdano_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('sozdano_at', '<=', $request->date_to);
        }

        if ($request->filled('deystvie')) {
            $query->where('deystvie', $request->deystvie);
        }

        if ($request->filled('obyekt')) {
            $type = match ($request->obyekt) {
                'property' => Property::class,
                'contract' => Contract::class,
                'user' => User::class,
                default => null,
            };
            if ($type) {
                $query->where('obyekt_type', $type);
            }
        }

        if ($request->filled('search')) {
            $term = trim($request->search);
            $query->where(function ($q) use ($term) {
                $q->where('kommentariy', 'like', '%'.$term.'%')
                    ->orWhere('deystvie', 'like', '%'.$term.'%')
                    ->orWhere('detalizatsiya', 'like', '%'.$term.'%');

                if (ctype_digit($term)) {
                    $q->orWhere('obyekt_id', (int) $term);
                }

                $q->orWhereHas('polzovatel', function ($uq) use ($term) {
                    $uq->where('familia', 'like', '%'.$term.'%')
                        ->orWhere('imya', 'like', '%'.$term.'%')
                        ->orWhere('otchestvo', 'like', '%'.$term.'%')
                        ->orWhere('email_polzovatela', 'like', '%'.$term.'%');
                });
            });
        }

        $logs = $query->paginate(25)->withQueryString();

        $propertyIds = $logs->getCollection()
            ->where('obyekt_type', Property::class)
            ->pluck('obyekt_id')
            ->unique()
            ->filter();
        $contractIds = $logs->getCollection()
            ->where('obyekt_type', Contract::class)
            ->pluck('obyekt_id')
            ->unique()
            ->filter();
        $userObjectIds = $logs->getCollection()
            ->where('obyekt_type', User::class)
            ->pluck('obyekt_id')
            ->unique()
            ->filter();

        $propertyTitles = $propertyIds->isNotEmpty()
            ? Property::whereIn('id', $propertyIds)->pluck('nazvanie', 'id')
            : collect();
        $contractLabels = $contractIds->isNotEmpty()
            ? Contract::whereIn('id', $contractIds)->pluck('id', 'id')->map(fn ($id) => 'Договор №'.$id)
            : collect();
        $userObjectLabels = $userObjectIds->isNotEmpty()
            ? User::whereIn('id', $userObjectIds)->get()->mapWithKeys(fn (User $u) => [
                $u->id => trim($u->familia.' '.$u->imya) ?: $u->email_polzovatela,
            ])
            : collect();

        return view('admin.audit.index', [
            'logs' => $logs,
            'deystviya' => AuditJournalDisplay::kodyDeystviyDlyaFiltra(),
            'propertyTitles' => $propertyTitles,
            'contractLabels' => $contractLabels,
            'userObjectLabels' => $userObjectLabels,
            'filters' => $request->only(['search', 'deystvie', 'obyekt', 'date_from', 'date_to']),
        ]);
    }
}
