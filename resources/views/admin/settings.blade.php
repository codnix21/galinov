@extends('layouts.app')

@section('title', 'Настройки системы')

@section('content')
<div class="mb-8">
    <a href="{{ route('admin.dashboard') }}" class="text-sm text-brand-700 hover:underline">← Админ-панель</a>
    <h1 class="text-3xl font-bold mt-2">Настройки системы</h1>
</div>

<form method="POST" action="{{ route('admin.settings.update') }}" class="card p-6 max-w-lg space-y-4">
    @csrf
    @method('PUT')
    <div>
        <label class="form-label">SLA заявок по объектам (часов)</label>
        <input type="number" name="inquiry_sla_hours" value="{{ old('inquiry_sla_hours', $inquirySlaHours) }}" min="1" max="168" class="form-input" required>
        <p class="text-xs text-slate-500 mt-1">После этого срока новая заявка подсвечивается как просроченная.</p>
    </div>
    <div>
        <label class="form-label">Email агентства</label>
        <input type="email" name="contact_email" value="{{ old('contact_email', $contactEmail) }}" class="form-input">
    </div>
    <div>
        <label class="form-label">Название агентства</label>
        <input type="text" name="agency_name" value="{{ old('agency_name', $agencyName) }}" class="form-input">
    </div>
    <hr class="border-slate-200">
    <h2 class="font-bold text-lg">Еженедельный отчёт на email</h2>
    <label class="flex items-center gap-2">
        <input type="checkbox" name="report_email_enabled" value="1" {{ old('report_email_enabled', $reportEmailEnabled) ? 'checked' : '' }}>
        <span class="text-sm">Включить рассылку (понедельник, 08:00)</span>
    </label>
    <div>
        <label class="form-label">Получатели (через запятую)</label>
        <input type="text" name="report_email_recipients" value="{{ old('report_email_recipients', $reportEmailRecipients) }}" class="form-input" placeholder="admin@example.com, director@example.com">
    </div>
    <button type="submit" class="btn-primary">Сохранить</button>
</form>
@endsection
