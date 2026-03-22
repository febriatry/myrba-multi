@extends('layouts.app')

@section('title', __('Tambah Hari Libur'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Tambah Hari Libur') }}</h3>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="/dashboard">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('hr-holidays.index') }}">{{ __('Hari Libur') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Tambah') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <x-alert></x-alert>

            <div class="card">
                <div class="card-body">
                    <form action="{{ route('hr-holidays.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">{{ __('Tanggal') }}</label>
                            <input type="date" name="date" class="form-control" value="{{ old('date') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Nama') }}</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('Tipe') }}</label>
                                <select class="form-select" name="type" required>
                                    <option value="national" @selected(old('type', 'national') === 'national')>national</option>
                                    <option value="company" @selected(old('type') === 'company')>company</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('Aktif') }}</label>
                                <select class="form-select" name="is_active" required>
                                    <option value="Yes" @selected(old('is_active', 'Yes') === 'Yes')>Yes</option>
                                    <option value="No" @selected(old('is_active') === 'No')>No</option>
                                </select>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary" type="submit">{{ __('Simpan') }}</button>
                            <a href="{{ route('hr-holidays.index') }}" class="btn btn-light">{{ __('Batal') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>
@endsection

