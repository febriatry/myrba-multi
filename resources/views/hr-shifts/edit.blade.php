@extends('layouts.app')

@section('title', __('Edit Shift'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Edit Shift') }}</h3>
                    <p class="text-subtitle text-muted">{{ $row->name }}</p>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="/dashboard">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('hr-shifts.index') }}">{{ __('Master Shift') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Edit') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <x-alert></x-alert>

            <div class="card">
                <div class="card-body">
                    <form action="{{ route('hr-shifts.update', $row->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="form-label">{{ __('Nama Shift') }}</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $row->name) }}" required>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('Start') }}</label>
                                <input type="time" name="start_time" class="form-control" value="{{ old('start_time', $row->start_time) }}" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('End') }}</label>
                                <input type="time" name="end_time" class="form-control" value="{{ old('end_time', $row->end_time) }}" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('Break (menit)') }}</label>
                                <input type="number" name="break_minutes" class="form-control" value="{{ old('break_minutes', $row->break_minutes) }}">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Aktif') }}</label>
                            <select class="form-select" name="is_active" required>
                                <option value="Yes" @selected(old('is_active', $row->is_active) === 'Yes')>Yes</option>
                                <option value="No" @selected(old('is_active', $row->is_active) === 'No')>No</option>
                            </select>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary" type="submit">{{ __('Update') }}</button>
                            <a href="{{ route('hr-shifts.index') }}" class="btn btn-light">{{ __('Kembali') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>
@endsection

