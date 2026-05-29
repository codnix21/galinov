<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractStatus;
use App\Models\Property;
use App\Models\PropertyStatus;
use App\Support\LeanWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Личный кабинет: сводка по объявлениям и договорам пользователя.
 */
class CabinetController extends Controller
{
    /**
     * Главная страница кабинета — список своих объявлений и счётчики по роли (клиент / риэлтор).
     */
    public function index(): View
    {
        $user = Auth::user();
        $properties = Property::where('polzovatel_id', $user->id)
            ->with('cityRelation')
            ->latest()
            ->paginate(10);
        
        $activePid = PropertyStatus::idFor('active');
        $pendingPid = PropertyStatus::idFor('pending_review');
        $soldPid = PropertyStatus::idFor('sold');
        $pendingCid = ContractStatus::idFor('pending');
        $activeCid = ContractStatus::idFor('active');

        $stats = [
            'total_properties' => Property::where('polzovatel_id', $user->id)->count(),
            'active_properties' => $activePid !== null
                ? Property::where('polzovatel_id', $user->id)->where('status_obyavleniya_id', $activePid)->count() : 0,
            'pending_moderation_properties' => $pendingPid !== null
                ? Property::where('polzovatel_id', $user->id)->where('status_obyavleniya_id', $pendingPid)->count() : 0,
            'sold_properties' => $soldPid !== null
                ? Property::where('polzovatel_id', $user->id)->where('status_obyavleniya_id', $soldPid)->count() : 0,
        ];

        // Статистика договоров для клиентов
        if ($user->isClient()) {
            $partyQuery = fn ($q) => $q->where('vladelets_id', $user->id)
                ->orWhere('pokupatel_id', $user->id)
                ->orWhere('rieltor_id', $user->id);
            $stats['total_contracts'] = Contract::where($partyQuery)->count();
            $stats['pending_contracts'] = $pendingCid !== null
                ? Contract::where('status_dogovora_id', $pendingCid)->where($partyQuery)->count()
                : 0;
        }

        // Статистика договоров для риэлторов (все договоры, ожидающие подтверждения риэлтора)
        if ($user->isRealtor() || $user->isAdmin()) {
            $stats['pending_contracts'] = $pendingCid !== null
                ? Contract::where('status_dogovora_id', $pendingCid)
                    ->where(function ($q) {
                        $q->where('ozhidaet_podtverzhdeniya', 'realtor')
                            ->orWhereNull('ozhidaet_podtverzhdeniya');
                    })
                    ->count()
                : 0;
            $stats['active_contracts'] = $activeCid !== null
                ? Contract::where('status_dogovora_id', $activeCid)->count() : 0;
            $stats['total_contracts'] = Contract::count();
        }

        $leanActions = LeanWorkflow::nextActionsFor($user);

        return view('cabinet.index', compact('user', 'properties', 'stats', 'leanActions'));
    }
}


