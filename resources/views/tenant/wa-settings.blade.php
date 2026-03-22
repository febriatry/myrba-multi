@extends('layouts.app')

@section('title', 'Pengaturan WhatsApp Tenant')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Pengaturan WhatsApp Tenant</h3>
        <a href="{{ route('tenant.dashboard') }}" class="btn btn-outline-secondary">Kembali</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('tenant.wa.settings.update') }}" class="row g-2">
                @csrf

                <div class="col-12">
                    <label class="form-label">Mode</label>
                    <select name="wa_provider_mode" class="form-select" required>
                        <option value="developer" @selected(old('wa_provider_mode', $tenant->wa_provider_mode) === 'developer')>Pakai layanan developer</option>
                        <option value="tenant" @selected(old('wa_provider_mode', $tenant->wa_provider_mode) === 'tenant')>Pakai API sendiri (Ivosight)</option>
                    </select>
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label">Ivosight Base URL</label>
                    <input type="text" name="wa_ivosight_base_url" class="form-control" value="{{ old('wa_ivosight_base_url', $tenant->wa_ivosight_base_url) }}">
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label">Ivosight Sender ID</label>
                    <input type="text" name="wa_ivosight_sender_id" class="form-control" value="{{ old('wa_ivosight_sender_id', $tenant->wa_ivosight_sender_id) }}">
                </div>

                <div class="col-12">
                    <label class="form-label">Ivosight API Key</label>
                    <input type="text" name="wa_ivosight_api_key" class="form-control" value="{{ old('wa_ivosight_api_key', $tenant->wa_ivosight_api_key) }}">
                </div>

                <div class="col-12">
                    <button class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
@endsection

