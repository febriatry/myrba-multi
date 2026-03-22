@extends('layouts.app')

@section('title', __('Tambah Absensi'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Tambah Absensi') }}</h3>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="/dashboard">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('hr-attendances.index', ['date' => $date]) }}">{{ __('Absensi Harian') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Tambah') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <x-alert></x-alert>

            <div class="card">
                <div class="card-body">
                    <form action="{{ route('hr-attendances.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('Karyawan') }}</label>
                                <select class="form-select" name="user_id" required>
                                    <option value="">{{ __('Pilih karyawan') }}</option>
                                    @foreach ($employees as $e)
                                        <option value="{{ $e->id }}" @selected(old('user_id') == $e->id)>{{ $e->name }} ({{ $e->email }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">{{ __('Tanggal') }}</label>
                                <input type="date" name="date" class="form-control" value="{{ old('date', $date) }}" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">{{ __('Jenis') }}</label>
                                <select class="form-select" name="work_type" required>
                                    <option value="normal" @selected(old('work_type', 'normal') === 'normal')>normal</option>
                                    <option value="overtime" @selected(old('work_type') === 'overtime')>overtime</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('Clock In') }}</label>
                                <input type="datetime-local" name="clock_in_at" class="form-control" value="{{ old('clock_in_at') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('Clock Out') }}</label>
                                <input type="datetime-local" name="clock_out_at" class="form-control" value="{{ old('clock_out_at') }}">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">{{ __('Lat In') }}</label>
                                <input type="text" name="clock_in_lat" class="form-control" value="{{ old('clock_in_lat') }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">{{ __('Lng In') }}</label>
                                <input type="text" name="clock_in_lng" class="form-control" value="{{ old('clock_in_lng') }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">{{ __('Lat Out') }}</label>
                                <input type="text" name="clock_out_lat" class="form-control" value="{{ old('clock_out_lat') }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">{{ __('Lng Out') }}</label>
                                <input type="text" name="clock_out_lng" class="form-control" value="{{ old('clock_out_lng') }}">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('Status') }}</label>
                                <select class="form-select" name="status" required>
                                    <option value="open" @selected(old('status', 'open') === 'open')>open</option>
                                    <option value="closed" @selected(old('status') === 'closed')>closed</option>
                                </select>
                            </div>
                            <div class="col-md-8 mb-3">
                                <label class="form-label">{{ __('Catatan') }}</label>
                                <input type="text" name="notes" class="form-control" value="{{ old('notes') }}">
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button class="btn btn-primary" type="submit">{{ __('Simpan') }}</button>
                            <a href="{{ route('hr-attendances.index', ['date' => $date]) }}" class="btn btn-light">{{ __('Batal') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>
@endsection

