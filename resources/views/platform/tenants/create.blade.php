@extends('layouts.app')

@section('title', 'Tambah Tenant')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Tambah Tenant</h3>
        <a href="{{ route('platform.tenants.index') }}" class="btn btn-outline-secondary">Kembali</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('platform.tenants.store') }}" class="row g-2">
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
                        <option value="suspended" @selected(old('status') === 'suspended')>suspended</option>
                    </select>
                </div>
                <div class="col-12 col-md-8">
                    <label class="form-label">Paket</label>
                    <select name="plan_id" class="form-select" required>
                        @foreach ($plans as $p)
                            <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->code }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12">
                    <hr>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label">Admin Tenant (Nama)</label>
                    <input type="text" name="admin_name" class="form-control" value="{{ old('admin_name') }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">Admin Tenant (Email)</label>
                    <input type="email" name="admin_email" class="form-control" value="{{ old('admin_email') }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">Admin Tenant (Password)</label>
                    <input type="password" name="admin_password" class="form-control" required>
                </div>

                <div class="col-12">
                    <button class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
@endsection

