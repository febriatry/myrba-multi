@extends('layouts.app')

@section('title', 'Edit Tenant')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Edit Tenant</h3>
        <a href="{{ route('platform.tenants.index') }}" class="btn btn-outline-secondary">Kembali</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('platform.tenants.update', $tenant->id) }}" class="row g-2">
                @csrf
                @method('PUT')
                <div class="col-12 col-md-6">
                    <label class="form-label">Nama</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $tenant->name) }}" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Code</label>
                    <input type="text" name="code" class="form-control" value="{{ old('code', $tenant->code) }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" required>
                        <option value="active" @selected(old('status', $tenant->status) === 'active')>active</option>
                        <option value="suspended" @selected(old('status', $tenant->status) === 'suspended')>suspended</option>
                    </select>
                </div>
                <div class="col-12 col-md-8">
                    <label class="form-label">Paket</label>
                    <select name="plan_id" class="form-select" required>
                        @foreach ($plans as $p)
                            <option value="{{ $p->id }}" @selected((int) old('plan_id', $tenant->plan_id) === (int) $p->id)>{{ $p->name }} ({{ $p->code }})</option>
                        @endforeach
                    </select>
                </div>
                @php
                    $features = is_array($tenant->features_json) ? $tenant->features_json : [];
                    $quota = is_array($tenant->quota_json) ? $tenant->quota_json : [];
                    $owFinance = array_key_exists('finance', $features) ? ($features['finance'] ? '1' : '0') : '';
                    $owPelanggan = array_key_exists('pelanggan', $features) ? ($features['pelanggan'] ? '1' : '0') : '';
                    $owLayanan = array_key_exists('layanan', $features) ? ($features['layanan'] ? '1' : '0') : '';
                    $owNetwork = array_key_exists('network', $features) ? ($features['network'] ? '1' : '0') : '';
                    $owPppoe = array_key_exists('pppoe', $features) ? ($features['pppoe'] ? '1' : '0') : '';
                    $owHotspot = array_key_exists('hotspot', $features) ? ($features['hotspot'] ? '1' : '0') : '';
                    $owInvestor = array_key_exists('investor', $features) ? ($features['investor'] ? '1' : '0') : '';
                    $owCms = array_key_exists('cms', $features) ? ($features['cms'] ? '1' : '0') : '';
                    $owSettings = array_key_exists('settings', $features) ? ($features['settings'] ? '1' : '0') : '';
                    $owWhatsapp = array_key_exists('whatsapp', $features) ? ($features['whatsapp'] ? '1' : '0') : '';
                    $owPayment = array_key_exists('payment_gateway', $features) ? ($features['payment_gateway'] ? '1' : '0') : '';
                    $owInventory = array_key_exists('inventory', $features) ? ($features['inventory'] ? '1' : '0') : '';
                    $owHr = array_key_exists('hr', $features) ? ($features['hr'] ? '1' : '0') : '';
                    $owOlt = array_key_exists('olt', $features) ? ($features['olt'] ? '1' : '0') : '';
                    $owAudit = array_key_exists('audit', $features) ? ($features['audit'] ? '1' : '0') : '';
                @endphp
                <div class="col-12">
                    <hr>
                </div>
                <div class="col-12">
                    <div class="fw-bold mb-2">Override Fitur (opsional)</div>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Keuangan</label>
                    <select name="override_feature_finance" class="form-select">
                        <option value="" @selected((string) old('override_feature_finance', $owFinance) === '')>Inherit</option>
                        <option value="1" @selected((string) old('override_feature_finance', $owFinance) === '1')>On</option>
                        <option value="0" @selected((string) old('override_feature_finance', $owFinance) === '0')>Off</option>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Pelanggan</label>
                    <select name="override_feature_pelanggan" class="form-select">
                        <option value="" @selected((string) old('override_feature_pelanggan', $owPelanggan) === '')>Inherit</option>
                        <option value="1" @selected((string) old('override_feature_pelanggan', $owPelanggan) === '1')>On</option>
                        <option value="0" @selected((string) old('override_feature_pelanggan', $owPelanggan) === '0')>Off</option>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Kelola Layanan</label>
                    <select name="override_feature_layanan" class="form-select">
                        <option value="" @selected((string) old('override_feature_layanan', $owLayanan) === '')>Inherit</option>
                        <option value="1" @selected((string) old('override_feature_layanan', $owLayanan) === '1')>On</option>
                        <option value="0" @selected((string) old('override_feature_layanan', $owLayanan) === '0')>Off</option>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Network Ops</label>
                    <select name="override_feature_network" class="form-select">
                        <option value="" @selected((string) old('override_feature_network', $owNetwork) === '')>Inherit</option>
                        <option value="1" @selected((string) old('override_feature_network', $owNetwork) === '1')>On</option>
                        <option value="0" @selected((string) old('override_feature_network', $owNetwork) === '0')>Off</option>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">PPPoE</label>
                    <select name="override_feature_pppoe" class="form-select">
                        <option value="" @selected((string) old('override_feature_pppoe', $owPppoe) === '')>Inherit</option>
                        <option value="1" @selected((string) old('override_feature_pppoe', $owPppoe) === '1')>On</option>
                        <option value="0" @selected((string) old('override_feature_pppoe', $owPppoe) === '0')>Off</option>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Hotspot</label>
                    <select name="override_feature_hotspot" class="form-select">
                        <option value="" @selected((string) old('override_feature_hotspot', $owHotspot) === '')>Inherit</option>
                        <option value="1" @selected((string) old('override_feature_hotspot', $owHotspot) === '1')>On</option>
                        <option value="0" @selected((string) old('override_feature_hotspot', $owHotspot) === '0')>Off</option>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Investor</label>
                    <select name="override_feature_investor" class="form-select">
                        <option value="" @selected((string) old('override_feature_investor', $owInvestor) === '')>Inherit</option>
                        <option value="1" @selected((string) old('override_feature_investor', $owInvestor) === '1')>On</option>
                        <option value="0" @selected((string) old('override_feature_investor', $owInvestor) === '0')>Off</option>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">CMS</label>
                    <select name="override_feature_cms" class="form-select">
                        <option value="" @selected((string) old('override_feature_cms', $owCms) === '')>Inherit</option>
                        <option value="1" @selected((string) old('override_feature_cms', $owCms) === '1')>On</option>
                        <option value="0" @selected((string) old('override_feature_cms', $owCms) === '0')>Off</option>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Settings</label>
                    <select name="override_feature_settings" class="form-select">
                        <option value="" @selected((string) old('override_feature_settings', $owSettings) === '')>Inherit</option>
                        <option value="1" @selected((string) old('override_feature_settings', $owSettings) === '1')>On</option>
                        <option value="0" @selected((string) old('override_feature_settings', $owSettings) === '0')>Off</option>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">WhatsApp</label>
                    <select name="override_feature_whatsapp" class="form-select">
                        <option value="" @selected((string) old('override_feature_whatsapp', $owWhatsapp) === '')>Inherit</option>
                        <option value="1" @selected((string) old('override_feature_whatsapp', $owWhatsapp) === '1')>On</option>
                        <option value="0" @selected((string) old('override_feature_whatsapp', $owWhatsapp) === '0')>Off</option>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Payment Gateway</label>
                    <select name="override_feature_payment_gateway" class="form-select">
                        <option value="" @selected((string) old('override_feature_payment_gateway', $owPayment) === '')>Inherit</option>
                        <option value="1" @selected((string) old('override_feature_payment_gateway', $owPayment) === '1')>On</option>
                        <option value="0" @selected((string) old('override_feature_payment_gateway', $owPayment) === '0')>Off</option>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Inventory</label>
                    <select name="override_feature_inventory" class="form-select">
                        <option value="" @selected((string) old('override_feature_inventory', $owInventory) === '')>Inherit</option>
                        <option value="1" @selected((string) old('override_feature_inventory', $owInventory) === '1')>On</option>
                        <option value="0" @selected((string) old('override_feature_inventory', $owInventory) === '0')>Off</option>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">HR</label>
                    <select name="override_feature_hr" class="form-select">
                        <option value="" @selected((string) old('override_feature_hr', $owHr) === '')>Inherit</option>
                        <option value="1" @selected((string) old('override_feature_hr', $owHr) === '1')>On</option>
                        <option value="0" @selected((string) old('override_feature_hr', $owHr) === '0')>Off</option>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">OLT</label>
                    <select name="override_feature_olt" class="form-select">
                        <option value="" @selected((string) old('override_feature_olt', $owOlt) === '')>Inherit</option>
                        <option value="1" @selected((string) old('override_feature_olt', $owOlt) === '1')>On</option>
                        <option value="0" @selected((string) old('override_feature_olt', $owOlt) === '0')>Off</option>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Audit</label>
                    <select name="override_feature_audit" class="form-select">
                        <option value="" @selected((string) old('override_feature_audit', $owAudit) === '')>Inherit</option>
                        <option value="1" @selected((string) old('override_feature_audit', $owAudit) === '1')>On</option>
                        <option value="0" @selected((string) old('override_feature_audit', $owAudit) === '0')>Off</option>
                    </select>
                </div>

                <div class="col-12 mt-2">
                    <div class="fw-bold mb-2">Override Kuota (opsional)</div>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Max Users</label>
                    <input type="number" name="override_max_users" class="form-control" value="{{ old('override_max_users', $quota['max_users'] ?? '') }}" min="1">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Max Pelanggan</label>
                    <input type="number" name="override_max_pelanggans" class="form-control" value="{{ old('override_max_pelanggans', $quota['max_pelanggans'] ?? '') }}" min="1">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Max WA / Bulan</label>
                    <input type="number" name="override_max_wa_messages_monthly" class="form-control" value="{{ old('override_max_wa_messages_monthly', $quota['max_wa_messages_monthly'] ?? '') }}" min="1">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Max Router</label>
                    <input type="number" name="override_max_routers" class="form-control" value="{{ old('override_max_routers', $quota['max_routers'] ?? '') }}" min="1">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Harga WA / Pesan</label>
                    <input type="number" name="override_wa_price_per_message" class="form-control" value="{{ old('override_wa_price_per_message', $quota['wa_price_per_message'] ?? '') }}" min="0" step="0.01">
                </div>
                <div class="col-12">
                    <button class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
@endsection
