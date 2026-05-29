<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractStatus;
use App\Models\Property;
use App\Models\PropertyCollection;
use App\Models\PropertyShowing;
use App\Models\PropertyStatus;
use App\Models\RealtorClient;
use App\Models\RealtorTask;
use App\Support\ContractApproval;
use App\Support\LeanWorkflow;
use App\Support\PropertyCatalogFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Рабочее место риэлтора: дашборд и портфель объявлений.
 */
class RealtorController extends Controller
{
    public function dashboard(): View
    {
        $user = Auth::user();
        $isAdmin = $user->isAdmin();

        $activePid = PropertyStatus::idFor('active');
        $pendingPid = PropertyStatus::idFor('pending_review');
        $draftPid = PropertyStatus::idFor('draft');
        $soldPid = PropertyStatus::idFor('sold');
        $rentedPid = PropertyStatus::idFor('rented');
        $pendingCid = ContractStatus::idFor('pending');
        $activeCid = ContractStatus::idFor('active');

        $propertyBase = Property::query();
        if (!$isAdmin) {
            $propertyBase->where('polzovatel_id', $user->id);
        }

        $stats = [
            'my_properties' => (clone $propertyBase)->count(),
            'active_properties' => $activePid ? (clone $propertyBase)->where('status_obyavleniya_id', $activePid)->count() : 0,
            'pending_moderation' => $pendingPid ? (clone $propertyBase)->where('status_obyavleniya_id', $pendingPid)->count() : 0,
            'drafts' => $draftPid ? (clone $propertyBase)->where('status_obyavleniya_id', $draftPid)->count() : 0,
            'sold' => $soldPid ? (clone $propertyBase)->where('status_obyavleniya_id', $soldPid)->count() : 0,
            'rented' => $rentedPid ? (clone $propertyBase)->where('status_obyavleniya_id', $rentedPid)->count() : 0,
        ];

        $contractQuery = Contract::query();
        if (!$isAdmin) {
            $contractQuery->where('rieltor_id', $user->id);
        }

        $stats['my_contracts'] = (clone $contractQuery)->count();
        $stats['active_contracts'] = $activeCid
            ? (clone $contractQuery)->where('status_dogovora_id', $activeCid)->count()
            : 0;

        $moderationQueue = 0;
        if ($pendingPid) {
            $modQ = Property::where('status_obyavleniya_id', $pendingPid);
            if ($user->isRealtor() && !$isAdmin) {
                $modQ->where('polzovatel_id', '!=', $user->id);
            }
            $moderationQueue = $modQ->count();
        }
        $stats['moderation_queue'] = $moderationQueue;

        $crmRealtorId = $isAdmin ? null : $user->getKey();
        $clientQ = RealtorClient::query();
        $taskQ = RealtorTask::query()->whereNull('vypolneno_at');
        $showingQ = PropertyShowing::query()->where('naznacheno_na', '>=', now());
        $collectionQ = PropertyCollection::query()->where('aktivna', true);
        if ($crmRealtorId) {
            $clientQ->where('rieltor_id', $crmRealtorId);
            $taskQ->where('rieltor_id', $crmRealtorId);
            $showingQ->where('rieltor_id', $crmRealtorId);
            $collectionQ->where('rieltor_id', $crmRealtorId);
        }
        $stats['crm_clients'] = $clientQ->count();
        $stats['crm_tasks_open'] = $taskQ->count();
        $stats['crm_showings_upcoming'] = $showingQ->count();
        $stats['crm_collections'] = $collectionQ->count();

        $upcomingTasks = (clone $taskQ)->with(['client', 'property'])->orderBy('srok_do')->limit(5)->get();
        $upcomingShowings = (clone $showingQ)->with(['client', 'property'])->orderBy('naznacheno_na')->limit(5)->get();

        $pendingContracts = Contract::query()
            ->when($pendingCid, fn ($q) => $q->where('status_dogovora_id', $pendingCid))
            ->when(!$pendingCid, fn ($q) => $q->whereRaw('1 = 0'))
            ->with(['property', 'owner', 'buyer'])
            ->latest()
            ->limit(8)
            ->get()
            ->filter(fn (Contract $c) => $isAdmin || (int) $c->rieltor_id === (int) $user->id)
            ->map(function (Contract $contract) use ($user) {
                $contract->needs_my_approval = ContractApproval::userCanApprove($user, $contract);

                return $contract;
            });

        $recentProperties = (clone $propertyBase)
            ->with(['cityRelation', 'images'])
            ->latest('obnovleno_at')
            ->limit(6)
            ->get();

        $leanActions = LeanWorkflow::nextActionsFor($user);

        return view('realtor.dashboard', compact(
            'user',
            'stats',
            'pendingContracts',
            'recentProperties',
            'isAdmin',
            'upcomingTasks',
            'upcomingShowings',
            'leanActions',
        ));
    }

    public function properties(Request $request): View
    {
        $user = Auth::user();
        $ownerId = $user->isAdmin() && $request->filled('user_id')
            ? (int) $request->input('user_id')
            : (int) $user->id;

        $query = Property::query()->with(['cityRelation', 'images', 'statusRelation']);
        PropertyCatalogFilter::applyRealtorPortfolio($query, $request, $ownerId);

        $properties = $query->paginate(15)->withQueryString();

        $statusOptions = [
            '' => 'Все статусы',
            'draft' => 'Черновик',
            'pending_review' => 'На модерации',
            'active' => 'Активно',
            'sold' => 'Продано',
            'rented' => 'Сдано',
            'inactive' => 'Неактивно',
        ];

        return view('realtor.properties', compact('properties', 'statusOptions', 'ownerId'));
    }
}
