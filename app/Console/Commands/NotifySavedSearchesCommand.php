<?php

namespace App\Console\Commands;

use App\Services\SavedSearchService;
use Illuminate\Console\Command;

class NotifySavedSearchesCommand extends Command
{
    protected $signature = 'app:notify-saved-searches';

    protected $description = 'Уведомления о новых объявлениях по сохранённым поискам';

    public function handle(): int
    {
        $count = SavedSearchService::runAllNotifications();
        $this->info("Отправлено уведомлений по новым объектам: {$count}");

        return self::SUCCESS;
    }
}
