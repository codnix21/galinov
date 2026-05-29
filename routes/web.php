<?php

// Основные веб-маршруты сайта (подключаются из bootstrap/app.php).
// Здесь: публичные страницы, каталог объявлений, личный кабинет, админка.

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\PropertyOwnerController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CabinetController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\DaDataController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\PublicFileController;
use App\Http\Controllers\ModerationController;
use App\Http\Controllers\RealtorController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RealtorClientController;
use App\Http\Controllers\RealtorTaskController;
use App\Http\Controllers\RealtorShowingController;
use App\Http\Controllers\RealtorCollectionController;
use App\Http\Controllers\PublicCollectionController;
use App\Http\Controllers\OnlinePurchaseController;
use App\Http\Controllers\ExpressDealController;
use App\Http\Controllers\UserDocumentController;
use App\Http\Controllers\PropertyDocumentController;
use App\Http\Controllers\PropertyInquiryController;
use App\Http\Controllers\PropertySelectionRequestController;
use App\Http\Controllers\PropertyInfoRequestController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\RobokassaPaymentController;
use Illuminate\Support\Facades\Route;

// Главная страница (приветствие)
Route::get('/', function () {
    return view('welcome');
});

// Файлы из storage/app/public без симлинка public/storage (удобно на Windows)
Route::get('/media/{path}', [PublicFileController::class, 'publicDisk'])
    ->where('path', '.*')
    ->name('media.public');

// Статические информационные страницы — без входа в аккаунт
Route::get('/about-contacts', [PageController::class, 'aboutContacts'])->name('pages.about-contacts');
Route::get('/help', [PageController::class, 'help'])->name('pages.help');
Route::get('/process', [PageController::class, 'process'])->name('pages.process');
Route::get('/mortgage-calculator', [PageController::class, 'mortgageCalculator'])->name('pages.mortgage-calculator');
Route::get('/search', [SearchController::class, 'index'])->name('search.index');

// Robokassa: Result / Success / Fail (без авторизации — callback платёжной системы)
Route::match(['get', 'post'], '/payment/result', [RobokassaPaymentController::class, 'result'])->name('payment.robokassa.result');
Route::match(['get', 'post'], '/payment/success', [RobokassaPaymentController::class, 'success'])->name('payment.robokassa.success');
Route::match(['get', 'post'], '/payment/fail', [RobokassaPaymentController::class, 'fail'])->name('payment.robokassa.fail');

// Подсказки DaData (адрес и город) — только для авторизованных
Route::middleware('auth')->group(function () {
    Route::get('/api/dadata/address', [DaDataController::class, 'suggestAddress'])->name('api.dadata.address');
    Route::get('/api/dadata/city', [DaDataController::class, 'suggestCity'])->name('api.dadata.city');
});

// Публичная подборка риэлтора по ссылке
Route::get('/podborka/{token}', [PublicCollectionController::class, 'show'])->name('collections.public');

// Каталог объявлений — видят все посетители
Route::get('/properties', [PropertyController::class, 'index'])->name('properties.index');
Route::get('/properties/map', [PropertyController::class, 'map'])->name('properties.map');
Route::post('/properties/selection-request', [PropertySelectionRequestController::class, 'store'])->name('properties.selection-request.store');

// Webhook Telegram (без CSRF)
Route::post('/telegram/webhook', \App\Http\Controllers\TelegramWebhookController::class)->name('telegram.webhook');

// Личный кабинет: нужен вход и аккаунт не заблокирован
Route::middleware(['auth', 'check.blocked'])->group(function () {
    Route::get('/cabinet', [CabinetController::class, 'index'])->name('cabinet.index');
    Route::get('/selection-request', [PropertySelectionRequestController::class, 'create'])->name('properties.selection-request.create');

    Route::middleware('realtor.training')->group(function () {
        Route::get('/training', [PageController::class, 'training'])->name('pages.training');
        Route::get('/training/pdf/{topic}', [PageController::class, 'trainingPdf'])
            ->whereIn('topic', ['cold', 'hot', 'listings'])
            ->name('pages.training.pdf');

        Route::prefix('realtor')->name('realtor.')->group(function () {
            Route::get('/', [RealtorController::class, 'dashboard'])->name('dashboard');
            Route::get('/properties', [RealtorController::class, 'properties'])->name('properties');

            Route::get('/clients', [RealtorClientController::class, 'index'])->name('clients.index');
            Route::get('/clients/search', [RealtorClientController::class, 'searchClients'])->name('clients.search');
            Route::post('/clients', [RealtorClientController::class, 'store'])->name('clients.store');
            Route::get('/clients/{realtorClient}', [RealtorClientController::class, 'show'])->name('clients.show');
            Route::put('/clients/{realtorClient}', [RealtorClientController::class, 'update'])->name('clients.update');
            Route::delete('/clients/{realtorClient}', [RealtorClientController::class, 'destroy'])->name('clients.destroy');

            Route::get('/tasks', [RealtorTaskController::class, 'index'])->name('tasks.index');
            Route::post('/tasks', [RealtorTaskController::class, 'store'])->name('tasks.store');
            Route::post('/tasks/{task}/complete', [RealtorTaskController::class, 'complete'])->name('tasks.complete');
            Route::delete('/tasks/{task}', [RealtorTaskController::class, 'destroy'])->name('tasks.destroy');

            Route::get('/showings', [RealtorShowingController::class, 'index'])->name('showings.index');
            Route::post('/showings', [RealtorShowingController::class, 'store'])->name('showings.store');
            Route::put('/showings/{showing}', [RealtorShowingController::class, 'update'])->name('showings.update');
            Route::delete('/showings/{showing}', [RealtorShowingController::class, 'destroy'])->name('showings.destroy');

            Route::get('/collections', [RealtorCollectionController::class, 'index'])->name('collections.index');
            Route::get('/collections/create', [RealtorCollectionController::class, 'create'])->name('collections.create');
            Route::post('/collections', [RealtorCollectionController::class, 'store'])->name('collections.store');
            Route::post('/collections/from-favorites', [RealtorCollectionController::class, 'storeFromFavorites'])->name('collections.from-favorites');
            Route::get('/collections/{collection}', [RealtorCollectionController::class, 'show'])->name('collections.show');
            Route::post('/collections/{collection}/properties', [RealtorCollectionController::class, 'addProperty'])->name('collections.add-property');
            Route::delete('/collections/{collection}/properties/{property}', [RealtorCollectionController::class, 'removeProperty'])->name('collections.remove-property');
            Route::delete('/collections/{collection}', [RealtorCollectionController::class, 'destroy'])->name('collections.destroy');

            Route::get('/inquiries', [PropertyInquiryController::class, 'index'])->name('inquiries.index');
            Route::post('/inquiries/{inquiry}/process', [PropertyInquiryController::class, 'process'])->name('inquiries.process');
            Route::get('/selection-requests', [PropertySelectionRequestController::class, 'index'])->name('selection-requests.index');
            Route::post('/selection-requests/{selectionRequest}/process', [PropertySelectionRequestController::class, 'process'])->name('selection-requests.process');
            Route::get('/info-requests', [PropertyInfoRequestController::class, 'index'])->name('info-requests.index');
            Route::post('/info-requests/{infoRequest}/reply', [PropertyInfoRequestController::class, 'reply'])->name('info-requests.reply');
            Route::post('/info-requests/{infoRequest}/close', [PropertyInfoRequestController::class, 'close'])->name('info-requests.close');
        });
    });

    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/unread-count', [NotificationController::class, 'unreadCount'])->name('unread-count');
        Route::post('/read-all', [NotificationController::class, 'markAllRead'])->name('read-all');
        Route::match(['get', 'post'], '/{id}/read', [NotificationController::class, 'markRead'])->name('read');
    });

    // Модерация объявлений — только для сотрудников (middleware staff.moderate)
    Route::middleware('staff.moderate')->prefix('moderation')->name('moderation.')->group(function () {
        Route::get('/', [ModerationController::class, 'index'])->name('index');
        Route::post('/properties/{property}/approve', [ModerationController::class, 'approve'])->name('approve');
        Route::post('/properties/{property}/reject', [ModerationController::class, 'reject'])->name('reject');
        Route::get('/documents', [UserDocumentController::class, 'moderationIndex'])->name('documents');
        Route::post('/documents/{document}/verify', [UserDocumentController::class, 'verify'])->name('documents.verify');
        Route::post('/documents/{document}/recheck', [UserDocumentController::class, 'recheck'])->name('documents.recheck');
    });
    
    // Создание и редактирование своих объявлений (конкретные URL — выше общего /properties/{id})
    Route::get('/properties/create', [PropertyController::class, 'create'])->name('properties.create');
    Route::post('/properties', [PropertyController::class, 'store'])->name('properties.store');
    Route::get('/properties/drafts', [PropertyController::class, 'drafts'])->name('properties.drafts');
    Route::get('/properties/{property}/edit', [PropertyController::class, 'edit'])->name('properties.edit');
    Route::put('/properties/{property}', [PropertyController::class, 'update'])->name('properties.update');
    Route::patch('/properties/{property}', [PropertyController::class, 'update']);
    Route::put('/properties/{property}/owners', [PropertyOwnerController::class, 'update'])->name('properties.owners.update');
    Route::get('/properties/{property}/documents', [PropertyDocumentController::class, 'show'])->name('properties.documents');
    Route::post('/properties/{property}/documents', [PropertyDocumentController::class, 'store'])->name('properties.documents.store');
    Route::post('/properties/{property}/documents/egrn-check', [PropertyDocumentController::class, 'verifyEgrn'])->name('properties.documents.egrn-check');
    Route::post('/properties/{property}/publish', [PropertyController::class, 'publish'])->name('properties.publish');
    Route::post('/properties/{property}/info-requests', [PropertyInfoRequestController::class, 'store'])->name('properties.info-requests.store');
    Route::delete('/properties/{property}', [PropertyController::class, 'destroy'])->name('properties.destroy');

    Route::get('/properties/{property}/buy', [OnlinePurchaseController::class, 'show'])->name('purchase.buy');
    Route::post('/properties/{property}/buy', [OnlinePurchaseController::class, 'store'])->name('purchase.store');
    Route::get('/properties/{property}/express-deal', [ExpressDealController::class, 'show'])->name('deals.express');
    Route::post('/properties/{property}/express-deal', [ExpressDealController::class, 'store'])->name('deals.express.store');

    Route::get('/purchase/{contract}/payment', [OnlinePurchaseController::class, 'payment'])->whereNumber('contract')->name('purchase.payment');
    Route::post('/purchase/{contract}/pay/robokassa', [OnlinePurchaseController::class, 'payRobokassa'])->whereNumber('contract')->name('purchase.pay.robokassa');
    Route::post('/purchase/{contract}/pay', [OnlinePurchaseController::class, 'paySimulate'])->whereNumber('contract')->name('purchase.pay');
    Route::get('/purchase/{contract}/complete', [OnlinePurchaseController::class, 'complete'])->whereNumber('contract')->name('purchase.complete');

    Route::get('/profile/documents', [UserDocumentController::class, 'index'])->name('profile.documents.index');
    Route::post('/profile/documents', [UserDocumentController::class, 'store'])->name('profile.documents.store');
    Route::get('/documents/{document}/view', [UserDocumentController::class, 'viewFile'])->name('documents.view');
    
    // Избранные объявления пользователя
    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/favorites/{property}', [FavoriteController::class, 'store'])->name('favorites.store');
    Route::delete('/favorites/{property}', [FavoriteController::class, 'destroy'])->name('favorites.destroy');
    
    // Договоры аренды/сделок для клиентов и риэлторов
    Route::get('/contracts', [ContractController::class, 'index'])->name('contracts.index');
    Route::get('/api/contracts/search/properties', [ContractController::class, 'searchProperties'])->name('api.contracts.search.properties');
    Route::get('/api/contracts/search/clients', [ContractController::class, 'searchClients'])->name('api.contracts.search.clients');
    Route::get('/api/contracts/search/realtors', [ContractController::class, 'searchRealtors'])->name('api.contracts.search.realtors');
    Route::get('/contracts/create/{property?}', [ContractController::class, 'create'])->name('contracts.create');
    Route::post('/contracts', [ContractController::class, 'store'])->name('contracts.store');
    // /contracts/pending выше {contract}, иначе Laravel примет "pending" за id договора
    Route::get('/contracts/pending', [ContractController::class, 'pending'])->name('contracts.pending');
    Route::post('/contracts/{contract}/sign-ecp', [ContractController::class, 'signEcp'])->whereNumber('contract')->name('contracts.sign-ecp');
    Route::post('/contracts/{contract}/approve', [ContractController::class, 'approve'])->name('contracts.approve');
    Route::post('/contracts/{contract}/reject', [ContractController::class, 'reject'])->name('contracts.reject');
    Route::post('/contracts/{contract}/complete', [ContractController::class, 'complete'])->name('contracts.complete');
    Route::get('/contracts/{contract}/pdf', [ContractController::class, 'exportPdf'])->whereNumber('contract')->name('contracts.pdf');
    Route::get('/contracts/{contract}/print-rent', [ContractController::class, 'printRent'])->whereNumber('contract')->name('contracts.print-rent');
    Route::post('/contracts/{contract}/scan', [ContractController::class, 'uploadScan'])->whereNumber('contract')->name('contracts.upload-scan');
    Route::get('/contracts/{contract}', [ContractController::class, 'show'])->whereNumber('contract')->name('contracts.show');
    
    // Настройки профиля (имя, email, пароль, удаление аккаунта)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/personal', [ProfileController::class, 'updatePersonalData'])->name('profile.personal.update');
    Route::patch('/profile/telegram', [ProfileController::class, 'updateTelegram'])->name('profile.telegram.update');
    Route::post('/profile/telegram/link', [\App\Http\Controllers\TelegramLinkController::class, 'createLink'])->name('profile.telegram.link');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::post('/properties/{property}/inquiry', [PropertyInquiryController::class, 'store'])->name('properties.inquiry');
Route::get('/properties/{property}/report', [\App\Http\Controllers\PropertyReportController::class, 'show'])->name('properties.report');
Route::get('/properties/{property}', [PropertyController::class, 'show'])->name('properties.show');

// Админ-панель: префикс /admin, имена маршрутов admin.*
Route::middleware(['auth', 'check.blocked'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('dashboard');
    
    // Пользователи
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::get('/users/create', [AdminController::class, 'createUser'])->name('users.create');
    Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
    Route::get('/users/{user}/edit', [AdminController::class, 'editUser'])->name('users.edit');
    Route::put('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');
    Route::delete('/users/{user}', [AdminController::class, 'deleteUser'])->name('users.delete');
    Route::post('/users/{user}/block', [AdminController::class, 'blockUser'])->name('users.block');
    Route::post('/users/{user}/unblock', [AdminController::class, 'unblockUser'])->name('users.unblock');
    
    // Объявления (полный CRUD для администратора)
    Route::get('/properties', [AdminController::class, 'properties'])->name('properties');
    Route::get('/properties/create', [AdminController::class, 'createProperty'])->name('properties.create');
    Route::post('/properties', [AdminController::class, 'storeProperty'])->name('properties.store');
    Route::get('/properties/{property}/edit', [AdminController::class, 'editProperty'])->name('properties.edit');
    Route::put('/properties/{property}', [AdminController::class, 'updateProperty'])->name('properties.update');
    Route::delete('/properties/{property}', [AdminController::class, 'deleteProperty'])->name('properties.delete');
    
    // Договоры
    Route::get('/contracts', [AdminController::class, 'contracts'])->name('contracts');
    Route::get('/contracts/create', [AdminController::class, 'createContract'])->name('contracts.create');
    Route::post('/contracts', [AdminController::class, 'storeContract'])->name('contracts.store');
    Route::get('/contracts/{contract}/edit', [AdminController::class, 'editContract'])->name('contracts.edit');
    Route::put('/contracts/{contract}', [AdminController::class, 'updateContract'])->name('contracts.update');
    Route::delete('/contracts/{contract}', [AdminController::class, 'deleteContract'])->name('contracts.delete');
    Route::get('/contracts/{contract}/pdf', [AdminController::class, 'exportContractPdf'])->name('contracts.pdf');
    
    // Отчёты и выгрузки
    Route::get('/reports', [ReportController::class, 'index'])->name('reports');
    Route::get('/reports/pdf', [ReportController::class, 'exportPdf'])->name('reports.pdf');
    Route::get('/reports/csv', [ReportController::class, 'exportCsv'])->name('reports.csv');
    Route::get('/reports/xlsx', [ReportController::class, 'exportXlsx'])->name('reports.xlsx');

    Route::get('/audit-logs', [\App\Http\Controllers\AdminAuditLogController::class, 'index'])->name('audit-logs');

    Route::get('/database', [\App\Http\Controllers\Admin\AdminDatabaseController::class, 'index'])->name('database');
    Route::post('/database/backup', [\App\Http\Controllers\Admin\AdminDatabaseController::class, 'store'])->name('database.backup');
    Route::get('/database/download/{file}', [\App\Http\Controllers\Admin\AdminDatabaseController::class, 'download'])->name('database.download');
    Route::post('/database/restore', [\App\Http\Controllers\Admin\AdminDatabaseController::class, 'restore'])->name('database.restore');

    Route::get('/dictionaries', [\App\Http\Controllers\Admin\AdminDictionaryController::class, 'index'])->name('dictionaries');
    Route::post('/dictionaries/cities', [\App\Http\Controllers\Admin\AdminDictionaryController::class, 'storeCity'])->name('dictionaries.cities.store');
    Route::delete('/dictionaries/cities/{city}', [\App\Http\Controllers\Admin\AdminDictionaryController::class, 'destroyCity'])->name('dictionaries.cities.destroy');
    Route::post('/dictionaries/property-statuses', [\App\Http\Controllers\Admin\AdminDictionaryController::class, 'storePropertyStatus'])->name('dictionaries.property-statuses.store');
    Route::put('/dictionaries/property-statuses/{propertyStatus}', [\App\Http\Controllers\Admin\AdminDictionaryController::class, 'updatePropertyStatus'])->name('dictionaries.property-statuses.update');
    Route::post('/dictionaries/contract-statuses', [\App\Http\Controllers\Admin\AdminDictionaryController::class, 'storeContractStatus'])->name('dictionaries.contract-statuses.store');
    Route::put('/dictionaries/contract-statuses/{contractStatus}', [\App\Http\Controllers\Admin\AdminDictionaryController::class, 'updateContractStatus'])->name('dictionaries.contract-statuses.update');

    Route::get('/import', [\App\Http\Controllers\Admin\AdminImportController::class, 'index'])->name('import');
    Route::get('/import/template', [\App\Http\Controllers\Admin\AdminImportController::class, 'template'])->name('import.template');
    Route::post('/import', [\App\Http\Controllers\Admin\AdminImportController::class, 'store'])->name('import.store');

    Route::post('/bulk/properties', [\App\Http\Controllers\Admin\AdminBulkController::class, 'properties'])->name('bulk.properties');
    Route::post('/bulk/contracts', [\App\Http\Controllers\Admin\AdminBulkController::class, 'contracts'])->name('bulk.contracts');
});

// Регистрация, вход, сброс пароля и т.д. (отдельный файл)
require __DIR__.'/auth.php';
