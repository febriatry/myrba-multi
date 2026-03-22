@extends('layouts.app')

@section('title', __('Edit Absensi'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Edit Absensi') }}</h3>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="/dashboard">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('hr-attendances.index', ['date' => $row->date]) }}">{{ __('Absensi Harian') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Edit') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <x-alert></x-alert>

            <div class="card">
                <div class="card-body">
                    <form action="{{ route('hr-attendances.update', $row->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('Karyawan') }}</label>
                                <select class="form-select" name="user_id" required>
                                    @foreach ($employees as $e)
                                        <option value="{{ $e->id }}" @selected(old('user_id', $row->user_id) == $e->id)>{{ $e->name }} ({{ $e->email }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">{{ __('Tanggal') }}</label>
                                <input type="date" name="date" class="form-control" value="{{ old('date', $row->date) }}" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">{{ __('Jenis') }}</label>
                                <select class="form-select" name="work_type" required>
                                    <option value="normal" @selected(old('work_type', $row->work_type) === 'normal')>normal</option>
                                    <option value="overtime" @selected(old('work_type', $row->work_type) === 'overtime')>overtime</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('Clock In') }}</label>
                                <input type="datetime-local" name="clock_in_at" class="form-control" value="{{ old('clock_in_at', $row->clock_in_at ? \Carbon\Carbon::parse($row->clock_in_at)->format('Y-m-d\\TH:i') : '') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('Clock Out') }}</label>
                                <input type="datetime-local" name="clock_out_at" class="form-control" value="{{ old('clock_out_at', $row->clock_out_at ? \Carbon\Carbon::parse($row->clock_out_at)->format('Y-m-d\\TH:i') : '') }}">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">{{ __('Lat In') }}</label>
                                <input type="text" name="clock_in_lat" class="form-control" value="{{ old('clock_in_lat', $row->clock_in_lat) }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">{{ __('Lng In') }}</label>
                                <input type="text" name="clock_in_lng" class="form-control" value="{{ old('clock_in_lng', $row->clock_in_lng) }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">{{ __('Lat Out') }}</label>
                                <input type="text" name="clock_out_lat" class="form-control" value="{{ old('clock_out_lat', $row->clock_out_lat) }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">{{ __('Lng Out') }}</label>
                                <input type="text" name="clock_out_lng" class="form-control" value="{{ old('clock_out_lng', $row->clock_out_lng) }}">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('Status') }}</label>
                                <select class="form-select" name="status" required>
                                    <option value="open" @selected(old('status', $row->status) === 'open')>open</option>
                                    <option value="closed" @selected(old('status', $row->status) === 'closed')>closed</option>
                                </select>
                            </div>
                            <div class="col-md-8 mb-3">
                                <label class="form-label">{{ __('Catatan') }}</label>
                                <input type="text" name="notes" class="form-control" value="{{ old('notes', $row->notes) }}">
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button class="btn btn-primary" type="submit">{{ __('Update') }}</button>
                            <a href="{{ route('hr-attendances.index', ['date' => $row->date]) }}" class="btn btn-light">{{ __('Kembali') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>
@endsection

