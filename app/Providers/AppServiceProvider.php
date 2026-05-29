<?php

namespace App\Providers;

use App\Models\Contract;
use App\Models\Property;
use App\Models\User;
use App\Observers\ContractAuditObserver;
use App\Observers\ContractEcpObserver;
use App\Observers\ContractStatusVersionObserver;
use App\Observers\PropertyAuditObserver;
use App\Observers\PropertyStatusVersionObserver;
use App\Observers\UserAuditObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\ViewErrorBag;

/**
 * Общие настройки приложения при старте: локаль, наблюдатели за моделями.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Регистрация сервисов в контейнере (пока пусто).
     */
    public function register(): void
    {
        //
    }

    /**
     * Запуск при старте: русская локаль, журнал аудита для объявлений, договоров и пользователей.
     */
    public function boot(): void
    {
        // Устанавливаем русскую локаль по умолчанию
        app()->setLocale('ru');
        Paginator::defaultView('vendor.pagination.galinov');

        Property::observe(PropertyAuditObserver::class);
        Property::observe(PropertyStatusVersionObserver::class);
        Contract::observe(ContractAuditObserver::class);
        Contract::observe(ContractStatusVersionObserver::class);
        Contract::observe(ContractEcpObserver::class);
        User::observe(UserAuditObserver::class);

        // В шаблонах $errors всегда должен быть ViewErrorBag (для @error и списка ошибок).
        // Иначе при случайной перезаписи переменной (int, массив) падает вызов $errors->any().
        View::composer('*', function ($view): void {
            $errors = $view->getData()['errors'] ?? null;
            if ($errors instanceof ViewErrorBag) {
                return;
            }
            $fromSession = session('errors');
            $view->with(
                'errors',
                $fromSession instanceof ViewErrorBag ? $fromSession : new ViewErrorBag
            );
        });

        View::composer('layouts.app', function ($view): void {
            if (auth()->check()) {
                $view->with('unreadNotificationsCount', auth()->user()->unreadNotifications()->count());
                $view->with(
                    'headerNotifications',
                    auth()->user()->notifications()->limit(8)->get()
                );
            }
        });
    }
}
