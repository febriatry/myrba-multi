@extends('layouts.app')

@section('title', 'Owner Settings')

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>Owner Settings</h3>
                    <p class="text-subtitle text-muted">Pengaturan global Tripay dan WA broadcast (berlaku untuk tenant yang menggunakan mode Owner).</p>
                </div>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">Tripay API</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('platform.settings.update.tripay') }}" class="row g-2">
                    @csrf
                    <div class="col-12 col-md-6">
                        <label class="form-label">Base URL</label>
                        <input type="text" name="url_tripay" class="form-control" value="{{ old('url_tripay', $setting->url_tripay ?? '') }}" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Merchant Code</label>
                        <input type="text" name="kode_merchant" class="form-control" value="{{ old('kode_merchant', $setting->kode_merchant ?? '') }}" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">API Key</label>
                        <input type="text" name="api_key_tripay" class="form-control" value="{{ old('api_key_tripay', $setting->api_key_tripay ?? '') }}" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Private Key</label>
                        <input type="text" name="private_key" class="form-control" value="{{ old('private_key', $setting->private_key ?? '') }}" required>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary">Simpan Tripay</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">WA Broadcast Settings</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('platform.settings.update.wa') }}" class="row g-2">
                    @csrf
                    <div class="col-12 col-md-6">
                        <label class="form-label">Broadcast WA</label>
                        <select name="is_wa_broadcast_active" class="form-select">
                            <option value="Yes" @selected(($setting->is_wa_broadcast_active ?? 'Yes') === 'Yes')>Yes</option>
                            <option value="No" @selected(($setting->is_wa_broadcast_active ?? 'Yes') === 'No')>No</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">WA Billing</label>
                        <select name="is_wa_billing_active" class="form-select">
                            <option value="Yes" @selected(($setting->is_wa_billing_active ?? 'Yes') === 'Yes')>Yes</option>
                            <option value="No" @selected(($setting->is_wa_billing_active ?? 'Yes') === 'No')>No</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">WA Payment</label>
                        <select name="is_wa_payment_active" class="form-select">
                            <option value="Yes" @selected(($setting->is_wa_payment_active ?? 'Yes') === 'Yes')>Yes</option>
                            <option value="No" @selected(($setting->is_wa_payment_active ?? 'Yes') === 'No')>No</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">WA Welcome</label>
                        <select name="is_wa_welcome_active" class="form-select">
                            <option value="Yes" @selected(($setting->is_wa_welcome_active ?? 'Yes') === 'Yes')>Yes</option>
                            <option value="No" @selected(($setting->is_wa_welcome_active ?? 'Yes') === 'No')>No</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary">Simpan WA Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection

