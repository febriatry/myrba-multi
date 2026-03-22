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
                    $owWhatsapp = array_key_exists('whatsapp', $features) ? ($features['whatsapp'] ? '1' : '0') : '';
                    $owPayment = array_key_exists('payment_gateway', $features) ? ($features['payment_gateway'] ? '1' : '0') : '';
                    $owInventory = array_key_exists('inventory', $features) ? ($features['inventory'] ? '1' : '0') : '';
                    $owHr = array_key_exists('hr', $features) ? ($features['hr'] ? '1' : '0') : '';
                @endphp
                <div class="col-12">
                    <hr>
                </div>
                <div class="col-12">
                    <div class="fw-bold mb-2">Override Fitur (opsional)</div>
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
