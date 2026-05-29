{{-- Отчёты: фильтры и кнопки экспорта. --}}
@extends('layouts.app')

@section('title', 'Отчёты')

@section('content')
<div class="mb-8">
    <h1 class="text-4xl font-bold mb-2">Отчёты</h1>
    <p class="text-gray-600">Статистика и аналитика системы</p>
</div>

<!-- Фильтры отчёта -->
<div class="card p-8 mb-8">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
        <h2 class="text-2xl font-bold">Параметры отчёта</h2>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.reports.pdf', request()->query()) }}" class="btn-primary flex items-center gap-2" target="_blank">
                <span>📄</span>
                <span>Экспорт PDF</span>
            </a>
            <a href="{{ route('admin.reports.csv', request()->query()) }}" class="btn flex items-center gap-2">
                <span>📊</span>
                <span>Экспорт CSV</span>
            </a>
            <a href="{{ route('admin.reports.xlsx', request()->query()) }}" class="btn flex items-center gap-2">
                <span>📗</span>
                <span>Экспорт XLSX</span>
            </a>
        </div>
    </div>
    <form method="GET" action="{{ route('admin.reports') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div>
            <label for="date_from" class="form-label">Дата начала</label>
            <input type="date" id="date_from" name="date_from" value="{{ $dateFrom }}" class="form-input" required>
        </div>
        <div>
            <label for="date_to" class="form-label">Дата окончания</label>
            <input type="date" id="date_to" name="date_to" value="{{ $dateTo }}" class="form-input" required>
        </div>
        <div>
            <label for="filter_property_status" class="form-label">Статус объявления</label>
            <select id="filter_property_status" name="filter_property_status" class="form-input">
                <option value="">Все</option>
                <option value="draft" @selected(($filterPropertyStatus ?? '') === 'draft')>Черновик</option>
                <option value="active" @selected(($filterPropertyStatus ?? '') === 'active')>Активно</option>
                <option value="pending_review" @selected(($filterPropertyStatus ?? '') === 'pending_review')>На модерации</option>
                <option value="sold" @selected(($filterPropertyStatus ?? '') === 'sold')>Продано</option>
                <option value="rented" @selected(($filterPropertyStatus ?? '') === 'rented')>Сдано</option>
                <option value="inactive" @selected(($filterPropertyStatus ?? '') === 'inactive')>Неактивно</option>
            </select>
        </div>
        <div>
            <label for="filter_property_tip" class="form-label">Тип недвижимости</label>
            <select id="filter_property_tip" name="filter_property_tip" class="form-input">
                <option value="">Все</option>
                @foreach(\App\Models\Property::tipNazvaniya() as $value => $label)
                    <option value="{{ $value }}" @selected(($filterPropertyTip ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="filter_contract_status" class="form-label">Статус договора</label>
            <select id="filter_contract_status" name="filter_contract_status" class="form-input">
                <option value="">Все</option>
                <option value="draft" @selected(($filterContractStatus ?? '') === 'draft')>Черновик</option>
                <option value="pending" @selected(($filterContractStatus ?? '') === 'pending')>На подтверждении</option>
                <option value="active" @selected(($filterContractStatus ?? '') === 'active')>Активен</option>
                <option value="completed" @selected(($filterContractStatus ?? '') === 'completed')>Завершён</option>
                <option value="cancelled" @selected(($filterContractStatus ?? '') === 'cancelled')>Отменён</option>
            </select>
        </div>
        <div>
            <label for="filter_user_role" class="form-label">Роль (новые пользователи в периоде)</label>
            <select id="filter_user_role" name="filter_user_role" class="form-input">
                <option value="">Все</option>
                <option value="client" @selected(($filterUserRole ?? '') === 'client')>Клиент</option>
                <option value="realtor" @selected(($filterUserRole ?? '') === 'realtor')>Риэлтор</option>
                <option value="admin" @selected(($filterUserRole ?? '') === 'admin')>Администратор</option>
                <option value="guest" @selected(($filterUserRole ?? '') === 'guest')>Гость</option>
            </select>
        </div>
        <div>
            <label for="sort_top" class="form-label">Сортировка топа пользователей</label>
            <select id="sort_top" name="sort_top" class="form-input">
                <option value="properties_count" @selected(($sortTop ?? 'properties_count') === 'properties_count')>По числу объявлений</option>
                <option value="familia" @selected(($sortTop ?? '') === 'familia')>По фамилии</option>
                <option value="email_polzovatela" @selected(($sortTop ?? '') === 'email_polzovatela')>По email</option>
            </select>
        </div>
        <div>
            <label for="sort_dir" class="form-label">Направление сортировки</label>
            <select id="sort_dir" name="sort_dir" class="form-input">
                <option value="desc" @selected(($sortDir ?? 'desc') === 'desc')>По убыванию</option>
                <option value="asc" @selected(($sortDir ?? '') === 'asc')>По возрастанию</option>
            </select>
        </div>
        <div class="flex items-end gap-2 flex-wrap">
            <button type="submit" class="btn-primary">Применить</button>
            <a href="{{ route('admin.reports') }}" class="btn">Сбросить</a>
            <a href="{{ route('admin.reports', ['date_from' => \Carbon\Carbon::now()->subWeek()->format('Y-m-d'), 'date_to' => \Carbon\Carbon::now()->format('Y-m-d')]) }}" class="btn">Последняя неделя</a>
        </div>
    </form>
</div>

<!-- Статистика по объявлениям -->
<div class="card p-8 mb-8">
    <h2 class="text-2xl font-bold mb-6">Статистика по объявлениям</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="border border-slate-200 rounded-xl p-4">
            <div class="text-3xl font-bold mb-2">{{ $propertiesStats['total'] }}</div>
            <div class="text-sm text-gray-600">Всего объявлений</div>
        </div>
        <div class="border border-slate-200 rounded-xl p-4">
            <div class="text-3xl font-bold mb-2 text-green-600">{{ $propertiesStats['active'] }}</div>
            <div class="text-sm text-gray-600">Активных</div>
        </div>
        <div class="border border-slate-200 rounded-xl p-4">
            <div class="text-3xl font-bold mb-2 text-amber-600">{{ $propertiesStats['pending_review'] ?? 0 }}</div>
            <div class="text-sm text-gray-600">На модерации</div>
        </div>
        <div class="border border-slate-200 rounded-xl p-4">
            <div class="text-3xl font-bold mb-2 text-yellow-600">{{ $propertiesStats['draft'] }}</div>
            <div class="text-sm text-gray-600">Черновиков</div>
        </div>
        <div class="border border-slate-200 rounded-xl p-4">
            <div class="text-3xl font-bold mb-2 text-blue-600">{{ $propertiesStats['sold'] }}</div>
            <div class="text-sm text-gray-600">Продано</div>
        </div>
        <div class="border border-slate-200 rounded-xl p-4">
            <div class="text-3xl font-bold mb-2 text-gray-600">{{ $propertiesStats['inactive'] }}</div>
            <div class="text-sm text-gray-600">Неактивных</div>
        </div>
    </div>
</div>

<!-- Статистика по договорам -->
<div class="card p-8 mb-8">
    <h2 class="text-2xl font-bold mb-6">Статистика по договорам</h2>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="border border-slate-200 rounded-xl p-4">
            <div class="text-3xl font-bold mb-2">{{ $contractsStats['total'] }}</div>
            <div class="text-sm text-gray-600">Всего договоров</div>
        </div>
        <div class="border border-slate-200 rounded-xl p-4">
            <div class="text-3xl font-bold mb-2 text-green-600">{{ $contractsStats['active'] }}</div>
            <div class="text-sm text-gray-600">Активных</div>
        </div>
        <div class="border border-slate-200 rounded-xl p-4">
            <div class="text-3xl font-bold mb-2 text-yellow-600">{{ $contractsStats['pending'] }}</div>
            <div class="text-sm text-gray-600">На подтверждении</div>
        </div>
        <div class="border border-slate-200 rounded-xl p-4">
            <div class="text-3xl font-bold mb-2 text-red-600">{{ $contractsStats['rejected'] }}</div>
            <div class="text-sm text-gray-600">Отклонено</div>
        </div>
    </div>
</div>

<!-- Статистика по пользователям -->
<div class="card p-8 mb-8">
    <h2 class="text-2xl font-bold mb-6">Статистика по пользователям</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="border border-slate-200 rounded-xl p-4">
            <div class="text-3xl font-bold mb-2">{{ $usersStats['total'] }}</div>
            <div class="text-sm text-gray-600">Всего пользователей</div>
        </div>
        <div class="border border-slate-200 rounded-xl p-4">
            <div class="text-3xl font-bold mb-2 text-blue-600">{{ $usersStats['clients'] }}</div>
            <div class="text-sm text-gray-600">Клиентов</div>
        </div>
        <div class="border border-slate-200 rounded-xl p-4">
            <div class="text-3xl font-bold mb-2 text-purple-600">{{ $usersStats['realtors'] }}</div>
            <div class="text-sm text-gray-600">Риэлторов</div>
        </div>
    </div>
</div>

<!-- График объявлений по дням -->
@if($dailyProperties->count() > 0)
<div class="card p-8 mb-8">
    <h2 class="text-2xl font-bold mb-6">Объявления по дням</h2>
    <div class="space-y-2">
        @foreach($dailyProperties as $day)
        <div class="flex items-center gap-4">
            <div class="w-24 text-sm text-gray-600">{{ $day['date'] }}</div>
            <div class="flex-1">
                <div class="bg-gray-200 h-6 rounded relative">
                    <div class="bg-blue-600 h-6 rounded" style="width: {{ min(100, ($day['count'] / max(1, $dailyProperties->max('count'))) * 100) }}%"></div>
                    <span class="absolute inset-0 flex items-center justify-center text-xs font-medium">{{ $day['count'] }}</span>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

<!-- Статистика по типам недвижимости -->
@if($propertiesByType->count() > 0)
<div class="card p-8 mb-8">
    <h2 class="text-2xl font-bold mb-6">Объявления по типам недвижимости</h2>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        @foreach($propertiesByType as $type)
        <div class="border border-slate-200 rounded-xl p-4">
            <div class="text-2xl font-bold mb-2">{{ $type['count'] }}</div>
            <div class="text-sm text-gray-600">{{ $type['type'] }}</div>
        </div>
        @endforeach
    </div>
</div>
@endif

<!-- Топ пользователей по объявлениям -->
@if($topUsersByProperties->count() > 0)
<div class="card p-8 mb-8">
    <h2 class="text-2xl font-bold mb-2">Топ пользователей по количеству объявлений</h2>
    <p class="text-sm text-gray-600 mb-6">Сортировка задаётся в блоке параметров отчёта выше.</p>
    <div class="overflow-x-auto">
        <table class="w-full border-collapse">
            <thead>
                <tr class="border-b border-slate-200">
                    <th class="text-left p-2">Пользователь</th>
                    <th class="text-left p-2">Email</th>
                    <th class="text-right p-2">Количество объявлений</th>
                </tr>
            </thead>
            <tbody>
                @foreach($topUsersByProperties as $user)
                <tr class="border-b border-gray-300">
                    <td class="p-2">{{ $user->familia }} {{ $user->imya }} {{ $user->otchestvo }}</td>
                    <td class="p-2">{{ $user->email_polzovatela }}</td>
                    <td class="p-2 text-right font-bold">{{ $user->properties_count }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@if(!empty($generatedByFio))
<p class="text-sm text-gray-500 mt-8 text-center">
    Отчёт на экране. При экспорте в PDF, CSV или XLSX будет указано: сформировал {{ $generatedByFio }}@if(!empty($generatedByEmail)) ({{ $generatedByEmail }})@endif, {{ $generatedAt->format('d.m.Y H:i') }}.
</p>
@endif

@endsection


