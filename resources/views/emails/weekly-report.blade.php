<x-mail::message>
# Еженедельный отчёт {{ $periodLabel }}

**Объявления:** всего {{ $summary['properties_total'] ?? 0 }}, активных {{ $summary['properties_active'] ?? 0 }}, продано {{ $summary['properties_sold'] ?? 0 }}

**Договоры:** новых за период {{ $summary['contracts_period'] ?? 0 }}, активных {{ $summary['contracts_active'] ?? 0 }}

**Заявки по объектам:** {{ $summary['inquiries_total'] ?? 0 }} (обработано {{ $summary['inquiries_processed'] ?? 0 }})

**Пользователи:** всего {{ $summary['users_total'] ?? 0 }}

<x-mail::button :url="config('app.url')">
Открыть панель
</x-mail::button>

</x-mail::message>
