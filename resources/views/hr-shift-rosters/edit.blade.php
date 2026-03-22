@extends('layouts.app')

@section('title', __('Edit Jadwal Shift'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Edit Jadwal Shift') }}</h3>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="/dashboard">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('hr-shift-rosters.index') }}">{{ __('Jadwal Shift') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Edit') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <x-alert></x-alert>

            <div class="card">
                <div class="card-body">
                    <form action="{{ route('hr-shift-rosters.update', $row->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="form-label">{{ __('Karyawan') }}</label>
                            <select class="form-select" name="user_id" required>
                                @foreach ($employees as $e)
                                    <option value="{{ $e->id }}" @selected(old('user_id', $row->user_id) == $e->id)>{{ $e->name }} ({{ $e->email }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('Tanggal') }}</label>
                                <input type="date" name="date" class="form-control" value="{{ old('date', $row->date) }}" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('Shift') }}</label>
                                <select class="form-select" name="shift_id" required>
                                    @foreach ($shifts as $s)
                                        <option value="{{ $s->id }}" @selected(old('shift_id', $row->shift_id) == $s->id)>{{ $s->name }} ({{ $s->start_time }}-{{ $s->end_time }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('Status') }}</label>
                                <select class="form-select" name="status" required>
                                    <option value="planned" @selected(old('status', $row->status) === 'planned')>planned</option>
                                    <option value="swapped" @selected(old('status', $row->status) === 'swapped')>swapped</option>
                                    <option value="approved" @selected(old('status', $row->status) === 'approved')>approved</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Catatan') }}</label>
                            <input type="text" name="notes" class="form-control" value="{{ old('notes', $row->notes) }}">
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary" type="submit">{{ __('Update') }}</button>
                            <a href="{{ route('hr-shift-rosters.index', ['date' => $row->date]) }}" class="btn btn-light">{{ __('Kembali') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>
@endsection

