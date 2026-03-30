@extends('layouts.app')

@section('title', 'Edit Paket Tenant')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Edit Paket Tenant</h3>
        <a href="{{ route('platform.plans.index') }}" class="btn btn-outline-secondary">Kembali</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('platform.plans.update', $plan->id) }}" class="row g-2">
                @csrf
                @method('PUT')
                <div class="col-12 col-md-6">
                    <label class="form-label">Nama</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $plan->name) }}" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Code</label>
                    <input type="text" name="code" class="form-control" value="{{ old('code', $plan->code) }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" required>
                        <option value="active" @selected(old('status', $plan->status) === 'active')>active</option>
                        <option value="inactive" @selected(old('status', $plan->status) === 'inactive')>inactive</option>
                    </select>
                </div>
                @php
                    $features = is_array($plan->features_json) ? $plan->features_json : [];
                    $quota = is_array($plan->quota_json) ? $plan->quota_json : [];
                @endphp

                <div class="col-12">
                    <hr>
                </div>
                <div class="col-12">
                    <div class="fw-bold mb-2">Fitur</div>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">WhatsApp</label>
                    <select name="feature_whatsapp" class="form-select">
                        <option value="1" @selected((string) old('feature_whatsapp', isset($features['whatsapp']) && $features['whatsapp'] ? '1' : '0') === '1')>On</option>
                        <option value="0" @selected((string) old('feature_whatsapp', isset($features['whatsapp']) && $features['whatsapp'] ? '1' : '0') === '0')>Off</option>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Payment Gateway</label>
                    <select name="feature_payment_gateway" class="form-select">
                        <option value="1" @selected((string) old('feature_payment_gateway', isset($features['payment_gateway']) && $features['payment_gateway'] ? '1' : '0') === '1')>On</option>
                        <option value="0" @selected((string) old('feature_payment_gateway', isset($features['payment_gateway']) && $features['payment_gateway'] ? '1' : '0') === '0')>Off</option>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Inventory</label>
                    <select name="feature_inventory" class="form-select">
                        <option value="1" @selected((string) old('feature_inventory', isset($features['inventory']) && $features['inventory'] ? '1' : '0') === '1')>On</option>
                        <option value="0" @selected((string) old('feature_inventory', isset($features['inventory']) && $features['inventory'] ? '1' : '0') === '0')>Off</option>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">HR</label>
                    <select name="feature_hr" class="form-select">
                        <option value="1" @selected((string) old('feature_hr', isset($features['hr']) && $features['hr'] ? '1' : '0') === '1')>On</option>
                        <option value="0" @selected((string) old('feature_hr', isset($features['hr']) && $features['hr'] ? '1' : '0') === '0')>Off</option>
                    </select>
                </div>

                <div class="col-12 mt-2">
                    <div class="fw-bold mb-2">Kuota</div>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Max Users</label>
                    <input type="number" name="max_users" class="form-control" value="{{ old('max_users', $quota['max_users'] ?? '') }}" min="1">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Max Pelanggan</label>
                    <input type="number" name="max_pelanggans" class="form-control" value="{{ old('max_pelanggans', $quota['max_pelanggans'] ?? '') }}" min="1">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Max WA / Bulan</label>
                    <input type="number" name="max_wa_messages_monthly" class="form-control" value="{{ old('max_wa_messages_monthly', $quota['max_wa_messages_monthly'] ?? '') }}" min="1">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">WA Gratis / Bulan (Owner)</label>
                    <input type="number" name="wa_free_messages_monthly" class="form-control" value="{{ old('wa_free_messages_monthly', $quota['wa_free_messages_monthly'] ?? '') }}" min="0">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Harga WA / Pesan</label>
                    <input type="number" name="wa_price_per_message" class="form-control" value="{{ old('wa_price_per_message', $quota['wa_price_per_message'] ?? '') }}" min="0" step="0.01">
                </div>
                <div class="col-12">
                    <button class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
@endsection
