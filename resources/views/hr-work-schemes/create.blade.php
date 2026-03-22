@extends('layouts.app')

@section('title', __('Tambah Skema Jam Kerja'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Tambah Skema Jam Kerja') }}</h3>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="/dashboard">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('hr-work-schemes.index') }}">{{ __('Skema Jam Kerja') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Tambah') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <x-alert></x-alert>

            <div class="card">
                <div class="card-body">
                    <form action="{{ route('hr-work-schemes.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">{{ __('Nama') }}</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Tipe') }}</label>
                            <select class="form-select" name="type" required>
                                <option value="fixed" @selected(old('type') === 'fixed')>fixed</option>
                                <option value="flexible" @selected(old('type') === 'flexible')>flexible</option>
                                <option value="shift" @selected(old('type') === 'shift')>shift</option>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">{{ __('Grace (menit)') }}</label>
                                <input type="number" name="grace_minutes" class="form-control" value="{{ old('grace_minutes', 10) }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">{{ __('Break (menit)') }}</label>
                                <input type="number" name="break_minutes_default" class="form-control" value="{{ old('break_minutes_default', 60) }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">{{ __('Min kerja/hari (menit)') }}</label>
                                <input type="number" name="min_work_minutes_per_day" class="form-control" value="{{ old('min_work_minutes_per_day', 480) }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">{{ __('Threshold lembur (menit)') }}</label>
                                <input type="number" name="overtime_threshold_minutes" class="form-control" value="{{ old('overtime_threshold_minutes', 0) }}">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Aktif') }}</label>
                            <select class="form-select" name="is_active" required>
                                <option value="Yes" @selected(old('is_active', 'Yes') === 'Yes')>Yes</option>
                                <option value="No" @selected(old('is_active') === 'No')>No</option>
                            </select>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary" type="submit">{{ __('Simpan') }}</button>
                            <a href="{{ route('hr-work-schemes.index') }}" class="btn btn-light">{{ __('Batal') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>
@endsection

