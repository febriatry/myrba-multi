@extends('layouts.app')

@section('title', __('Tambah Titik Absensi'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Tambah Titik Absensi') }}</h3>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="/dashboard">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('hr-attendance-sites.index') }}">{{ __('Titik Absensi') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Tambah') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <x-alert></x-alert>

            <div class="card">
                <div class="card-body">
                    <form action="{{ route('hr-attendance-sites.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">{{ __('Nama') }}</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('Lat') }}</label>
                                <input type="text" name="lat" class="form-control" value="{{ old('lat') }}" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('Lng') }}</label>
                                <input type="text" name="lng" class="form-control" value="{{ old('lng') }}" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('Radius (meter)') }}</label>
                                <input type="number" name="radius_m" class="form-control" value="{{ old('radius_m', 100) }}" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Aktif') }}</label>
                            <select class="form-select" name="is_active" required>
                                <option value="Yes" @selected(old('is_active', 'Yes') === 'Yes')>Yes</option>
                                <option value="No" @selected(old('is_active') === 'No')>No</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Catatan') }}</label>
                            <input type="text" name="notes" class="form-control" value="{{ old('notes') }}">
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary" type="submit">{{ __('Simpan') }}</button>
                            <a href="{{ route('hr-attendance-sites.index') }}" class="btn btn-light">{{ __('Batal') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>
@endsection

