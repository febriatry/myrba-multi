@extends('layouts.app')

@section('title', 'Tambah Paket Tenant')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Tambah Paket Tenant</h3>
        <a href="{{ route('platform.plans.index') }}" class="btn btn-outline-secondary">Kembali</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('platform.plans.store') }}" class="row g-2">
                @csrf
                <div class="col-12 col-md-6">
                    <label class="form-label">Nama</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Code</label>
                    <input type="text" name="code" class="form-control" value="{{ old('code') }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" required>
                        <option value="active" @selected(old('status', 'active') === 'active')>active</option>
                        <option value="inactive" @selected(old('status') === 'inactive')>inactive</option>
                    </select>
                </div>
                <div class="col-12">
                    <hr>
                </div>
                <div class="col-12">
                    <div class="fw-bold mb-2">Fitur</div>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">WhatsApp</label>
                    <select name="feature_whatsapp" class="form-select">
                        <option value="1" @selected(old('feature_whatsapp', '0') === '1')>On</option>
                        <option value="0" @selected(old('feature_whatsapp', '0') === '0')>Off</option>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Payment Gateway</label>
                    <select name="feature_payment_gateway" class="form-select">
                        <option value="1" @selected(old('feature_payment_gateway', '0') === '1')>On</option>
                        <option value="0" @selected(old('feature_payment_gateway', '0') === '0')>Off</option>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Inventory</label>
                    <select name="feature_inventory" class="form-select">
                        <option value="1" @selected(old('feature_inventory', '0') === '1')>On</option>
                        <option value="0" @selected(old('feature_inventory', '0') === '0')>Off</option>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">HR</label>
                    <select name="feature_hr" class="form-select">
                        <option value="1" @selected(old('feature_hr', '0') === '1')>On</option>
                        <option value="0" @selected(old('feature_hr', '0') === '0')>Off</option>
                    </select>
                </div>

                <div class="col-12 mt-2">
                    <div class="fw-bold mb-2">Kuota</div>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Max Users</label>
                    <input type="number" name="max_users" class="form-control" value="{{ old('max_users') }}" min="1">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Max Pelanggan</label>
                    <input type="number" name="max_pelanggans" class="form-control" value="{{ old('max_pelanggans') }}" min="1">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Max WA / Bulan</label>
                    <input type="number" name="max_wa_messages_monthly" class="form-control" value="{{ old('max_wa_messages_monthly') }}" min="1">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">WA Gratis / Bulan (Owner)</label>
                    <input type="number" name="wa_free_messages_monthly" class="form-control" value="{{ old('wa_free_messages_monthly') }}" min="0">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Harga WA / Pesan</label>
                    <input type="number" name="wa_price_per_message" class="form-control" value="{{ old('wa_price_per_message') }}" min="0" step="0.01">
                </div>
                <div class="col-12">
                    <button class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
@endsection
