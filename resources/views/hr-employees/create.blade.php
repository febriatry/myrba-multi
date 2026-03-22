@extends('layouts.app')

@section('title', __('Tambah Karyawan'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Tambah Karyawan') }}</h3>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="/dashboard">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('hr-employees.index') }}">{{ __('Master Karyawan') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Tambah') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <x-alert></x-alert>

            <div class="card">
                <div class="card-body">
                    <form class="mb-3" method="GET" action="{{ route('hr-employees.create') }}">
                        <div class="d-flex gap-2">
                            <input type="text" name="q" class="form-control" placeholder="Cari user..." value="{{ $q }}">
                            <button class="btn btn-outline-primary" type="submit">{{ __('Cari') }}</button>
                        </div>
                    </form>

                    <form action="{{ route('hr-employees.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">{{ __('User') }}</label>
                            <select class="form-select" name="user_id" required>
                                <option value="">{{ __('Pilih user') }}</option>
                                @foreach ($users as $u)
                                    <option value="{{ $u->id }}" @selected(old('user_id') == $u->id)>{{ $u->name }} ({{ $u->email }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Kode Karyawan') }}</label>
                            <input type="text" name="employee_code" class="form-control" value="{{ old('employee_code') }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Jabatan') }}</label>
                            <select class="form-select" name="jabatan_id">
                                <option value="">{{ __('-') }}</option>
                                @foreach ($jabatans as $j)
                                    <option value="{{ $j->id }}" @selected(old('jabatan_id') == $j->id)>{{ $j->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Skema Jam Kerja') }}</label>
                            <select class="form-select" name="work_scheme_id">
                                <option value="">{{ __('-') }}</option>
                                @foreach ($schemes as $s)
                                    <option value="{{ $s->id }}" @selected(old('work_scheme_id') == $s->id)>{{ $s->name }} ({{ $s->type }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header">
                                <div class="fw-bold">{{ __('Hari Libur Mingguan') }}</div>
                            </div>
                            <div class="card-body">
                                <div class="text-muted mb-2">{{ __('Untuk karyawan shift yang hari liburnya berbeda-beda (mis. Sabtu/Minggu).') }}</div>
                                @php
                                    $offDays = old('weekly_off_days', []);
                                    $days = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
                                @endphp
                                <div class="d-flex flex-wrap gap-3">
                                    @foreach ($days as $k => $v)
                                        <label class="form-check">
                                            <input class="form-check-input" type="checkbox" name="weekly_off_days[]" value="{{ $k }}" @checked(in_array($k, (array) $offDays))>
                                            <span class="form-check-label">{{ $v }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Tanggal Masuk') }}</label>
                            <input type="date" name="joined_at" class="form-control" value="{{ old('joined_at') }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Aktif') }}</label>
                            <select class="form-select" name="is_active" required>
                                <option value="Yes" @selected(old('is_active', 'Yes') === 'Yes')>Yes</option>
                                <option value="No" @selected(old('is_active') === 'No')>No</option>
                            </select>
                        </div>

                        <div class="card mt-3">
                            <div class="card-header">
                                <div class="fw-bold">{{ __('Payroll') }}</div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">{{ __('Tipe Gaji') }}</label>
                                        <select class="form-select" name="salary_type" required>
                                            <option value="monthly" @selected(old('salary_type', 'monthly') === 'monthly')>monthly</option>
                                            <option value="daily" @selected(old('salary_type') === 'daily')>daily</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">{{ __('Gaji Bulanan') }}</label>
                                        <input type="number" name="monthly_salary" class="form-control" value="{{ old('monthly_salary', 0) }}">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">{{ __('Gaji Harian') }}</label>
                                        <input type="number" name="daily_salary" class="form-control" value="{{ old('daily_salary', 0) }}">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">{{ __('Rate Lembur / Jam') }}</label>
                                        <input type="number" name="overtime_rate_per_hour" class="form-control" value="{{ old('overtime_rate_per_hour', 0) }}">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">{{ __('Operasional Harian (auto)') }}</label>
                                        <input type="number" name="operational_daily_rate" class="form-control" value="{{ old('operational_daily_rate', 0) }}">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">{{ __('Potongan Wajib') }}</label>
                                        <div class="input-group">
                                            <select class="form-select" name="mandatory_deduction_type" required>
                                                <option value="fixed" @selected(old('mandatory_deduction_type', 'fixed') === 'fixed')>fixed</option>
                                                <option value="percent" @selected(old('mandatory_deduction_type') === 'percent')>percent</option>
                                            </select>
                                            <input type="number" name="mandatory_deduction_value" class="form-control" value="{{ old('mandatory_deduction_value', 0) }}">
                                        </div>
                                        <div class="text-muted mt-1">{{ __('Jika percent: dihitung dari gaji pokok (base).') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button class="btn btn-primary" type="submit">{{ __('Simpan') }}</button>
                            <a href="{{ route('hr-employees.index') }}" class="btn btn-light">{{ __('Batal') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>
@endsection
