@extends('layouts.app')

@section('title', __('Edit Titik Absensi'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Edit Titik Absensi') }}</h3>
                    <p class="text-subtitle text-muted">{{ $row->name }}</p>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="/dashboard">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('hr-attendance-sites.index') }}">{{ __('Titik Absensi') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Edit') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <x-alert></x-alert>

            <div class="card">
                <div class="card-body">
                    <form action="{{ route('hr-attendance-sites.update', $row->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="form-label">{{ __('Nama') }}</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $row->name) }}" required>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('Lat') }}</label>
                                <input type="text" name="lat" class="form-control" value="{{ old('lat', $row->lat) }}" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('Lng') }}</label>
                                <input type="text" name="lng" class="form-control" value="{{ old('lng', $row->lng) }}" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('Radius (meter)') }}</label>
                                <input type="number" name="radius_m" class="form-control" value="{{ old('radius_m', $row->radius_m) }}" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Aktif') }}</label>
                            <select class="form-select" name="is_active" required>
                                <option value="Yes" @selected(old('is_active', $row->is_active) === 'Yes')>Yes</option>
                                <option value="No" @selected(old('is_active', $row->is_active) === 'No')>No</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Catatan') }}</label>
                            <input type="text" name="notes" class="form-control" value="{{ old('notes', $row->notes) }}">
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary" type="submit">{{ __('Update') }}</button>
                            <a href="{{ route('hr-attendance-sites.index') }}" class="btn btn-light">{{ __('Kembali') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>
@endsection

