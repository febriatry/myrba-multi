<?php

namespace App\Http\Controllers;

use App\Models\SettingWeb;
use App\Models\WaTemplate;
use App\Models\WaTemplateMapping;
use App\Services\WhatsApp\IvosightGateway;
use App\Support\WaMessageTrigger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class WaConfigController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'platform.team', 'role:Platform Owner'])->only('index', 'toggleStatus', 'syncTemplates', 'testConnection', 'storeMapping', 'updateMapping', 'destroyMapping');
    }

    public function index()
    {
        $settingWeb = SettingWeb::first();
        $allTemplates = WaTemplate::query()->orderBy('name')->orderBy('template_id')->get();

        return view('wa-config.index', [
            'provider' => config('whatsapp.provider'),
            'base_url' => config('whatsapp.ivosight.base_url'),
            'api_key' => config('whatsapp.ivosight.api_key'),
            'sender_id' => config('whatsapp.ivosight.sender_id'),
            'use_template' => config('whatsapp.ivosight.use_template'),
            'template_endpoints' => config('whatsapp.ivosight.template_endpoints', []),
            'is_wa_broadcast_active' => ($settingWeb->is_wa_broadcast_active ?? 'Yes') === 'Yes',
            'is_wa_billing_active' => ($settingWeb->is_wa_billing_active ?? 'Yes') === 'Yes',
            'is_wa_payment_active' => ($settingWeb->is_wa_payment_active ?? 'Yes') === 'Yes',
            'is_wa_welcome_active' => ($settingWeb->is_wa_welcome_active ?? 'Yes') === 'Yes',
            'templates' => WaTemplate::query()->orderByDesc('synced_at')->orderByDesc('id')->limit(20)->get(),
            'all_templates' => $allTemplates,
            'template_mappings' => WaTemplateMapping::query()
                ->orderBy('template_id')
                ->orderBy('message_type')
                ->orderBy('component_type')
                ->orderBy('component_index')
                ->orderBy('param_index')
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->limit(100)
                ->get(),
            'source_key_options' => $this->sourceKeyOptions(),
            'message_type_options' => $this->messageTypeOptions(),
            'connection_report' => session('ivosight_connection_report'),
        ]);
    }

    public function toggleStatus(Request $request)
    {
        $request->validate([
            'is_wa_broadcast_active' => 'nullable|in:Yes,No',
            'is_wa_billing_active' => 'nullable|in:Yes,No',
            'is_wa_payment_active' => 'nullable|in:Yes,No',
            'is_wa_welcome_active' => 'nullable|in:Yes,No',
        ]);

        $settingWeb = SettingWeb::first();
        if (! $settingWeb) {
            return redirect()->route('wa-config.index')->with('error', __('Data setting web belum tersedia.'));
        }

        $updateData = [];
        if ($request->has('is_wa_broadcast_active')) {
            $updateData['is_wa_broadcast_active'] = $request->is_wa_broadcast_active;
        }
        if ($request->has('is_wa_billing_active')) {
            $updateData['is_wa_billing_active'] = $request->is_wa_billing_active;
        }
        if ($request->has('is_wa_payment_active')) {
            $updateData['is_wa_payment_active'] = $request->is_wa_payment_active;
        }
        if ($request->has('is_wa_welcome_active')) {
            $updateData['is_wa_welcome_active'] = $request->is_wa_welcome_active;
        }

        $settingWeb->update($updateData);

        return redirect()->route('wa-config.index')->with('success', __('Pengaturan WA Broadcast berhasil diperbarui.'));
    }

    public function syncTemplates()
    {
        $exitCode = Artisan::call('wa:sync-templates');

        if ($exitCode !== 0) {
            return redirect()
                ->route('wa-config.index')
                ->with('error', __('Sinkronisasi template gagal. Periksa endpoint template sandbox Ivosight.'));
        }

        return redirect()
            ->route('wa-config.index')
            ->with('success', __('Sinkronisasi template Ivosight berhasil.'));
    }

    public function testConnection()
    {
        $gateway = new IvosightGateway;
        $report = $gateway->testConnection();

        return redirect()
            ->route('wa-config.index')
            ->with('ivosight_connection_report', $report)
            ->with($report['ok'] ? 'success' : 'error', $report['ok']
                ? __('Koneksi sandbox Ivosight berhasil pada minimal satu endpoint template.')
                : __('Koneksi sandbox Ivosight belum berhasil. Periksa BASE_URL, API_KEY, dan endpoint template.'));
    }

    public function storeMapping(Request $request)
    {
        $validated = $this->validateMapping($request);
        $validated['message_type'] = WaMessageTrigger::normalize($validated['message_type']);

        WaTemplateMapping::updateOrCreate(
            [
                'template_id' => $validated['template_id'],
                'message_type' => $validated['message_type'],
                'component_type' => $validated['component_type'],
                'component_index' => (int) ($validated['component_index'] ?? 0),
                'param_index' => $validated['param_index'],
            ],
            [
                'component_sub_type' => $validated['component_sub_type'] ?? null,
                'source_key' => $validated['source_key'],
                'parameter_type' => $validated['parameter_type'],
                'default_value' => $validated['default_value'] ?? null,
                'is_required' => $validated['is_required'],
                'notes' => $validated['notes'] ?? null,
            ]
        );

        return redirect()->route('wa-config.index')->with('success', __('Mapping template berhasil disimpan.'));
    }

    public function updateMapping(Request $request, int $id)
    {
        $validated = $this->validateMapping($request);
        $validated['message_type'] = WaMessageTrigger::normalize($validated['message_type']);
        $mapping = WaTemplateMapping::query()->findOrFail($id);

        $duplicate = WaTemplateMapping::query()
            ->where('template_id', $validated['template_id'])
            ->where('message_type', $validated['message_type'])
            ->where('component_type', $validated['component_type'])
            ->where('component_index', (int) ($validated['component_index'] ?? 0))
            ->where('param_index', $validated['param_index'])
            ->where('id', '!=', $id)
            ->exists();
        if ($duplicate) {
            return redirect()->route('wa-config.index')->with('error', __('Kombinasi mapping sudah ada.'));
        }

        $mapping->update([
            'template_id' => $validated['template_id'],
            'message_type' => $validated['message_type'],
            'component_type' => $validated['component_type'],
            'component_index' => (int) ($validated['component_index'] ?? 0),
            'component_sub_type' => $validated['component_sub_type'] ?? null,
            'param_index' => $validated['param_index'],
            'source_key' => $validated['source_key'],
            'parameter_type' => $validated['parameter_type'],
            'default_value' => $validated['default_value'] ?? null,
            'is_required' => $validated['is_required'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('wa-config.index')->with('success', __('Mapping template berhasil diperbarui.'));
    }

    public function destroyMapping(int $id)
    {
        $mapping = WaTemplateMapping::query()->findOrFail($id);
        $mapping->delete();

        return redirect()->route('wa-config.index')->with('success', __('Mapping template berhasil dihapus.'));
    }

    private function validateMapping(Request $request): array
    {
        return $request->validate([
            'template_id' => 'required|string|max:255',
            'message_type' => 'required|string|max:100',
            'component_type' => 'required|in:header,body,button,carousel',
            'component_index' => 'nullable|integer|min:0',
            'component_sub_type' => 'nullable|in:url,quick_reply,flow,phone_number,copy_code,one_tap,spm',
            'param_index' => 'required|integer|min:1',
            'source_key' => 'required|string|max:255',
            'parameter_type' => 'required|in:text,image,document,video,payload,action,product',
            'default_value' => 'nullable|string',
            'is_required' => 'required|in:Yes,No',
            'notes' => 'nullable|string',
        ]);
    }

    private function sourceKeyOptions(): array
    {
        return [
            'pelanggan.nama' => 'Nama Pelanggan',
            'pelanggan.no_layanan' => 'No Layanan',
            'pelanggan.no_wa' => 'No WhatsApp Pelanggan',
            'pelanggan.alamat' => 'Alamat Pelanggan',
            'pelanggan.email' => 'Email Pelanggan',
            'tagihan.no_tagihan' => 'No Tagihan',
            'tagihan.total_bayar' => 'Total Bayar',
            'tagihan.nominal_bayar' => 'Nominal Bayar',
            'tagihan.potongan_bayar' => 'Potongan Bayar',
            'tagihan.periode' => 'Periode Tagihan',
            'tagihan.metode_bayar' => 'Metode Bayar',
            'tagihan.tanggal_bayar' => 'Tanggal Bayar',
            'tagihan.tanggal_jatuh_tempo' => 'Tanggal Jatuh Tempo Tagihan',
            'tagihan.jatuh_tempo' => 'Jatuh Tempo (hari)',
            'tagihan.id' => 'ID Tagihan',
            'tagihan.link_invoice' => 'Link Invoice',
            'pelanggan.paket_layanan_nama' => 'Nama Paket Layanan',
            'tagihan.paket_layanan_nama' => 'Nama Paket Layanan (Tagihan)',
            'pelanggan.tanggal_jatuh_tempo' => 'Tanggal Jatuh Tempo Pelanggan',
            'request.nama' => 'Nama dari Request',
            'request.no_layanan' => 'No Layanan dari Request',
            'request.pesan' => 'Pesan dari Broadcast',
            'request.raw_message' => 'Pesan Mentah Broadcast',
            'request.broadcast_message' => 'Pesan Broadcast',
            'request.tanggal_bayar' => 'Tanggal Bayar dari Request',
            'request.link_invoice' => 'Link Invoice dari Request',
            'request.unpaid_count' => 'Total Tagihan (jumlah)',
            'request.total_tunggakan' => 'Total Tagihan (nominal)',
            'request.periode_list' => 'Periode Tagihan (list)',
            'request.oldest_periode' => 'Periode Tertua',
            'request.newest_periode' => 'Periode Terbaru',
            'request.jumlah_bulan_tertunggak' => 'Jumlah Bulan Tertunggak',
            'request.jumlah_total_tunggakan' => 'Jumlah Total Tunggakan',
            'setting.nama_perusahaan' => 'Nama Perusahaan',
            'setting.no_wa' => 'No WhatsApp Perusahaan',
            'setting.email' => 'Email Perusahaan',
            'type_pesan' => 'Jenis Pesan (tagihan/bayar/broadcast/dll)',
        ];
    }

    private function messageTypeOptions(): array
    {
        return WaMessageTrigger::options();
    }
}
