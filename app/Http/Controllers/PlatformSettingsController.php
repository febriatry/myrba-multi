<?php

namespace App\Http\Controllers;

use App\Models\SettingWeb;
use App\Models\WaTemplate;
use Illuminate\Http\Request;

class PlatformSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'platform.team', 'role:Platform Owner']);
    }

    public function index()
    {
        $setting = getSettingWeb();
        $templates = WaTemplate::query()->orderBy('name')->orderBy('template_id')->get(['template_id', 'name', 'status', 'language']);
        $defaults = is_array($setting?->wa_template_defaults_json) ? $setting->wa_template_defaults_json : [];

        return view('platform.settings', [
            'setting' => $setting,
            'templates' => $templates,
            'waTemplateDefaults' => $defaults,
        ]);
    }

    public function updateTripay(Request $request)
    {
        $validated = $request->validate([
            'url_tripay' => 'required|string|max:255',
            'kode_merchant' => 'required|string|max:100',
            'api_key_tripay' => 'required|string|max:255',
            'private_key' => 'required|string|max:255',
        ]);

        $data = [
            'url_tripay' => rtrim(trim((string) $validated['url_tripay']), '/').'/',
            'kode_merchant' => trim((string) $validated['kode_merchant']),
            'api_key_tripay' => trim((string) $validated['api_key_tripay']),
            'private_key' => trim((string) $validated['private_key']),
        ];

        $setting = SettingWeb::query()->first();
        if (! $setting) {
            $setting = new SettingWeb;
        }
        foreach ($data as $k => $v) {
            $setting->{$k} = $v;
        }
        $setting->save();

        return redirect()->route('platform.settings.index')->with('success', 'Tripay API berhasil diperbarui.');
    }

    public function updateWa(Request $request)
    {
        $validated = $request->validate([
            'is_wa_broadcast_active' => 'nullable|in:Yes,No',
            'is_wa_billing_active' => 'nullable|in:Yes,No',
            'is_wa_payment_active' => 'nullable|in:Yes,No',
            'is_wa_welcome_active' => 'nullable|in:Yes,No',
        ]);

        $setting = SettingWeb::query()->first();
        if (! $setting) {
            $setting = new SettingWeb;
        }

        $setting->is_wa_broadcast_active = (string) ($validated['is_wa_broadcast_active'] ?? ($setting->is_wa_broadcast_active ?? 'Yes'));
        $setting->is_wa_billing_active = (string) ($validated['is_wa_billing_active'] ?? ($setting->is_wa_billing_active ?? 'Yes'));
        $setting->is_wa_payment_active = (string) ($validated['is_wa_payment_active'] ?? ($setting->is_wa_payment_active ?? 'Yes'));
        $setting->is_wa_welcome_active = (string) ($validated['is_wa_welcome_active'] ?? ($setting->is_wa_welcome_active ?? 'Yes'));
        $setting->save();

        return redirect()->route('platform.settings.index')->with('success', 'WA broadcast settings berhasil diperbarui.');
    }

    public function updateWaTemplates(Request $request)
    {
        $templateIds = WaTemplate::query()->pluck('template_id')->filter()->values()->all();

        $validated = $request->validate([
            'billing_reminder' => 'nullable|string|max:80',
            'billing_total' => 'nullable|string|max:80',
            'invoice_link' => 'nullable|string|max:80',
            'broadcast' => 'nullable|string|max:80',
            'payment_receipt' => 'nullable|string|max:80',
            'welcome_registration' => 'nullable|string|max:80',
        ]);

        $allowed = array_fill_keys($templateIds, true);
        $map = [];
        foreach ($validated as $k => $v) {
            $id = trim((string) $v);
            if ($id === '') {
                continue;
            }
            if (! isset($allowed[$id])) {
                return redirect()->back()->withInput()->with('error', 'Template ID tidak valid: '.$id);
            }
            $map[$k] = $id;
        }

        $setting = SettingWeb::query()->first();
        if (! $setting) {
            $setting = new SettingWeb;
        }
        $setting->wa_template_defaults_json = $map;
        $setting->save();

        return redirect()->route('platform.settings.index')->with('success', 'WA template defaults berhasil diperbarui.');
    }
}
