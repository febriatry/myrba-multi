@extends('layouts.app')

@section('title', __('Edit Skema Jam Kerja'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Edit Skema Jam Kerja') }}</h3>
                    <p class="text-subtitle text-muted">{{ $row->name }} ({{ $row->type }})</p>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="/dashboard">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('hr-work-schemes.index') }}">{{ __('Skema Jam Kerja') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Edit') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <x-alert></x-alert>

            <div class="card">
                <div class="card-body">
                    <form action="{{ route('hr-work-schemes.update', $row->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('Nama') }}</label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $row->name) }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('Tipe') }}</label>
                                <select class="form-select" name="type" required>
                                    <option value="fixed" @selected(old('type', $row->type) === 'fixed')>fixed</option>
                                    <option value="flexible" @selected(old('type', $row->type) === 'flexible')>flexible</option>
                                    <option value="shift" @selected(old('type', $row->type) === 'shift')>shift</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">{{ __('Grace (menit)') }}</label>
                                <input type="number" name="grace_minutes" class="form-control" value="{{ old('grace_minutes', $row->grace_minutes) }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">{{ __('Break (menit)') }}</label>
                                <input type="number" name="break_minutes_default" class="form-control" value="{{ old('break_minutes_default', $row->break_minutes_default) }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">{{ __('Min kerja/hari (menit)') }}</label>
                                <input type="number" name="min_work_minutes_per_day" class="form-control" value="{{ old('min_work_minutes_per_day', $row->min_work_minutes_per_day) }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">{{ __('Threshold lembur (menit)') }}</label>
                                <input type="number" name="overtime_threshold_minutes" class="form-control" value="{{ old('overtime_threshold_minutes', $row->overtime_threshold_minutes) }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Aktif') }}</label>
                            <select class="form-select" name="is_active" required>
                                <option value="Yes" @selected(old('is_active', $row->is_active) === 'Yes')>Yes</option>
                                <option value="No" @selected(old('is_active', $row->is_active) === 'No')>No</option>
                            </select>
                        </div>

                        <div class="card mt-4">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="fw-bold">{{ __('Rule per hari') }}</div>
                                    <div class="d-flex gap-2">
                                        <form method="POST" action="{{ route('hr-work-schemes.weekend-off', [$row->id, 6]) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-secondary" onclick="return confirm('Set Sabtu sebagai libur (hapus jam kerja Sabtu)?')">{{ __('Libur Sabtu') }}</button>
                                        </form>
                                        <form method="POST" action="{{ route('hr-work-schemes.weekend-off', [$row->id, 7]) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-secondary" onclick="return confirm('Set Minggu sebagai libur (hapus jam kerja Minggu)?')">{{ __('Libur Minggu') }}</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Hari') }}</th>
                                                <th>{{ __('Start') }}</th>
                                                <th>{{ __('End') }}</th>
                                                <th>{{ __('Mulai Lembur') }}</th>
                                                <th>{{ __('Window Start') }}</th>
                                                <th>{{ __('Window End') }}</th>
                                                <th>{{ __('Core Start') }}</th>
                                                <th>{{ __('Core End') }}</th>
                                                <th>{{ __('Break (m)') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($days as $dayNo => $dayName)
                                                @php
                                                    $r = $rules[$dayNo] ?? null;
                                                @endphp
                                                <tr>
                                                    <td class="fw-bold">{{ $dayName }}</td>
                                                    <td><input class="form-control form-control-sm" name="rules[{{ $dayNo }}][start_time]" value="{{ old('rules.' . $dayNo . '.start_time', $r->start_time ?? '') }}"></td>
                                                    <td><input class="form-control form-control-sm" name="rules[{{ $dayNo }}][end_time]" value="{{ old('rules.' . $dayNo . '.end_time', $r->end_time ?? '') }}"></td>
                                                    <td><input class="form-control form-control-sm" name="rules[{{ $dayNo }}][overtime_start_time]" value="{{ old('rules.' . $dayNo . '.overtime_start_time', $r->overtime_start_time ?? '') }}"></td>
                                                    <td><input class="form-control form-control-sm" name="rules[{{ $dayNo }}][flex_window_start]" value="{{ old('rules.' . $dayNo . '.flex_window_start', $r->flex_window_start ?? '') }}"></td>
                                                    <td><input class="form-control form-control-sm" name="rules[{{ $dayNo }}][flex_window_end]" value="{{ old('rules.' . $dayNo . '.flex_window_end', $r->flex_window_end ?? '') }}"></td>
                                                    <td><input class="form-control form-control-sm" name="rules[{{ $dayNo }}][core_start]" value="{{ old('rules.' . $dayNo . '.core_start', $r->core_start ?? '') }}"></td>
                                                    <td><input class="form-control form-control-sm" name="rules[{{ $dayNo }}][core_end]" value="{{ old('rules.' . $dayNo . '.core_end', $r->core_end ?? '') }}"></td>
                                                    <td><input type="number" class="form-control form-control-sm" name="rules[{{ $dayNo }}][break_minutes]" value="{{ old('rules.' . $dayNo . '.break_minutes', $r->break_minutes ?? '') }}"></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-muted mt-2">
                                    {{ __('Untuk fixed: gunakan Start/End. Untuk flexible: Window/Core opsional. Untuk shift: atur via Master Shift dan Jadwal Shift.') }}
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-3">
                            <button class="btn btn-primary" type="submit">{{ __('Update') }}</button>
                            <a href="{{ route('hr-work-schemes.index') }}" class="btn btn-light">{{ __('Kembali') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>
@endsection
