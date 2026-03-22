@extends('layouts.app')

@section('title', __('Tambah Jadwal Shift'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Tambah Jadwal Shift') }}</h3>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="/dashboard">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('hr-shift-rosters.index') }}">{{ __('Jadwal Shift') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Tambah') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <x-alert></x-alert>

            <div class="card">
                <div class="card-body">
                    <form action="{{ route('hr-shift-rosters.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">{{ __('Karyawan') }}</label>
                            <select class="form-select" name="user_id" required>
                                <option value="">{{ __('Pilih karyawan') }}</option>
                                @foreach ($employees as $e)
                                    <option value="{{ $e->id }}" @selected(old('user_id') == $e->id)>{{ $e->name }} ({{ $e->email }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('Tanggal') }}</label>
                                <input type="date" name="date" class="form-control" value="{{ old('date', now()->toDateString()) }}" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('Shift') }}</label>
                                <select class="form-select" name="shift_id" required>
                                    <option value="">{{ __('Pilih shift') }}</option>
                                    @foreach ($shifts as $s)
                                        <option value="{{ $s->id }}" @selected(old('shift_id') == $s->id)>{{ $s->name }} ({{ $s->start_time }}-{{ $s->end_time }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('Status') }}</label>
                                <select class="form-select" name="status" required>
                                    <option value="planned" @selected(old('status', 'planned') === 'planned')>planned</option>
                                    <option value="swapped" @selected(old('status') === 'swapped')>swapped</option>
                                    <option value="approved" @selected(old('status') === 'approved')>approved</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Catatan') }}</label>
                            <input type="text" name="notes" class="form-control" value="{{ old('notes') }}">
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary" type="submit">{{ __('Simpan') }}</button>
                            <a href="{{ route('hr-shift-rosters.index') }}" class="btn btn-light">{{ __('Batal') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>
@endsection

