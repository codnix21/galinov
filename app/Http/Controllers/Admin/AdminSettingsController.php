<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Support\InquirySla;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminSettingsController extends Controller
{
    public function index(): View
    {
        if (!Auth::user()?->isAdmin()) {
            abort(403);
        }

        return view('admin.settings', [
            'inquirySlaHours' => InquirySla::hours(),
            'contactEmail' => SystemSetting::get('contact_email', ''),
            'agencyName' => SystemSetting::get('agency_name', ''),
            'reportEmailEnabled' => SystemSetting::get('report_email_enabled', '0') === '1',
            'reportEmailRecipients' => SystemSetting::get('report_email_recipients', ''),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        if (!Auth::user()?->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'inquiry_sla_hours' => 'required|integer|min:1|max:168',
            'contact_email' => 'nullable|email|max:255',
            'agency_name' => 'nullable|string|max:120',
            'report_email_recipients' => 'nullable|string|max:500',
        ]);

        SystemSetting::set(InquirySla::SETTING_KEY, (string) $validated['inquiry_sla_hours']);
        SystemSetting::set('contact_email', $validated['contact_email'] ?? '');
        SystemSetting::set('agency_name', $validated['agency_name'] ?? '');
        SystemSetting::set('report_email_enabled', $request->boolean('report_email_enabled') ? '1' : '0');
        SystemSetting::set('report_email_recipients', $validated['report_email_recipients'] ?? '');

        return back()->with('success', 'Настройки сохранены.');
    }
}
