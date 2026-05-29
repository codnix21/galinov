<?php

namespace App\Observers;

use App\Models\Property;
use App\Services\ProcessVersionService;
use Illuminate\Support\Facades\Auth;

class PropertyStatusVersionObserver
{
    public function updated(Property $property): void
    {
        if (!$property->wasChanged('status_obyavleniya_id')) {
            return;
        }

        ProcessVersionService::recordProperty($property, Auth::user());
    }

    public function created(Property $property): void
    {
        ProcessVersionService::recordProperty($property, Auth::user(), 'Создание объявления');
    }
}
