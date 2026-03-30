@extends('layouts.app')

@section('title', 'Pengaturan Payment Gateway')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Pengaturan Payment Gateway</h3>
        <a href="{{ route('tenant.dashboard') }}" class="btn btn-outline-secondary">Kembali</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('tenant.payment.settings.update') }}" class="row g-2">
                @csrf
                <div class="col-12 col-md-6">
                    <label class="form-label">Mode Tripay</label>
                    <select name="tripay_provider_mode" class="form-select" required>
                        <option value="owner" @selected(old('tripay_provider_mode', $tenant->tripay_provider_mode ?? 'owner') === 'owner')>Gunakan Tripay Owner</option>
                        <option value="tenant" @selected(old('tripay_provider_mode', $tenant->tripay_provider_mode ?? 'owner') === 'tenant')>Gunakan Tripay Tenant</option>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Tripay Base URL</label>
                    <input type="text" name="tripay_base_url" class="form-control" value="{{ old('tripay_base_url', $tenant->tripay_base_url) }}">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Tripay Merchant Code</label>
                    <input type="text" name="tripay_merchant_code" class="form-control" value="{{ old('tripay_merchant_code', $tenant->tripay_merchant_code) }}">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Tripay API Key</label>
                    <input type="text" name="tripay_api_key" class="form-control" value="{{ old('tripay_api_key', $tenant->tripay_api_key) }}">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Tripay Private Key</label>
                    <input type="text" name="tripay_private_key" class="form-control" value="{{ old('tripay_private_key', $tenant->tripay_private_key) }}">
                </div>
                <div class="col-12">
                    <button class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
@endsection
