@extends('layouts.app')

@section('title', __('Operasional'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Operasional') }}</h3>
                    <p class="text-subtitle text-muted">{{ __('Operasional manual harian dan setting besaran operasional otomatis.') }}</p>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="/dashboard">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Operasional') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <x-alert></x-alert>

            <ul class="nav nav-tabs mb-3">
                <li class="nav-item">
                    <a class="nav-link @if (($tab ?? 'daily') === 'daily') active @endif" href="{{ route('hr-operational.index', ['tab' => 'daily']) }}">{{ __('Operasional Harian') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link @if (($tab ?? '') === 'rules') active @endif" href="{{ route('hr-operational.index', ['tab' => 'rules']) }}">{{ __('Setting Operasional') }}</a>
                </li>
            </ul>

            @if (($tab ?? 'daily') === 'rules')
                <div class="card mb-3">
                    <div class="card-body">
                        <form class="row g-2" method="POST" action="{{ route('hr-operational-rules.store') }}">
                            @csrf
                            <div class="col-md-2">
                                <select class="form-select" name="scope" required>
                                    <option value="global" @selected(old('scope', 'global') === 'global')>global</option>
                                    <option value="user" @selected(old('scope') === 'user')>user</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="user_id">
                                    <option value="">{{ __('-') }}</option>
                                    @foreach ($employees as $e)
                                        <option value="{{ $e->id }}" @selected(old('user_id') == $e->id)>{{ $e->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" name="mode" required>
                                    <option value="weekday" @selected(old('mode', 'weekday') === 'weekday')>weekday</option>
                                    <option value="date" @selected(old('mode') === 'date')>date</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" name="day_of_week">
                                    <option value="">{{ __('Hari') }}</option>
                                    @foreach ($days as $k => $v)
                                        <option value="{{ $k }}" @selected(old('day_of_week') == $k)>{{ $v }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="date" name="date" class="form-control" value="{{ old('date') }}">
                            </div>
                            <div class="col-md-3">
                                <input type="number" name="amount" class="form-control" value="{{ old('amount', 0) }}" required>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="is_active" required>
                                    <option value="Yes" @selected(old('is_active', 'Yes') === 'Yes')>Yes</option>
                                    <option value="No" @selected(old('is_active') === 'No')>No</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="note" class="form-control" value="{{ old('note') }}" placeholder="Catatan">
                            </div>
                            <div class="col-12 d-grid">
                                <button class="btn btn-primary" type="submit">{{ __('Tambah Rule') }}</button>
                            </div>
                        </form>
                        <div class="text-muted mt-2">
                            {{ __('Prioritas: user+date, global+date, user+weekday, global+weekday, fallback ke operasional_daily_rate di profile.') }}
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <form class="d-flex gap-2" method="GET" action="{{ route('hr-operational.index') }}">
                        <input type="hidden" name="tab" value="rules">
                        <input type="text" name="q" class="form-control" placeholder="Cari..." value="{{ $q ?? '' }}">
                        <button class="btn btn-outline-primary" type="submit">{{ __('Cari') }}</button>
                    </form>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('Scope') }}</th>
                                        <th>{{ __('Tanggal') }}</th>
                                        <th>{{ __('Hari') }}</th>
                                        <th>{{ __('Amount') }}</th>
                                        <th>{{ __('Aktif') }}</th>
                                        <th>{{ __('Note') }}</th>
                                        <th>{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($rows as $row)
                                        <tr>
                                            <td>{{ $row->id }}</td>
                                            <td>{{ $row->user_id ? ($row->user_name ?? ('user#' . $row->user_id)) : 'global' }}</td>
                                            <td>{{ $row->date ?? '-' }}</td>
                                            <td>{{ $row->day_of_week ? ($days[$row->day_of_week] ?? $row->day_of_week) : '-' }}</td>
                                            <td>{{ $row->amount }}</td>
                                            <td>{{ $row->is_active }}</td>
                                            <td>{{ $row->note ?? '-' }}</td>
                                            <td class="d-flex gap-2">
                                                <a class="btn btn-sm btn-outline-primary" href="{{ route('hr-operational-rules.edit', $row->id) }}">{{ __('Edit') }}</a>
                                                <form method="POST" action="{{ route('hr-operational-rules.destroy', $row->id) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus data?')">{{ __('Hapus') }}</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center">{{ __('Tidak ada data') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            {{ $rows->links() }}
                        </div>
                    </div>
                </div>
            @else
                <div class="card mb-3">
                    <div class="card-body">
                        <form class="row g-2" method="POST" action="{{ route('hr-operational-dailies.store') }}">
                            @csrf
                            <div class="col-md-3">
                                <input type="date" name="date" class="form-control" value="{{ old('date', $date ?? now()->toDateString()) }}" required>
                            </div>
                            <div class="col-md-4">
                                <select class="form-select" name="user_id" required>
                                    <option value="">{{ __('Pilih karyawan') }}</option>
                                    @foreach ($employees as $e)
                                        <option value="{{ $e->id }}" @selected(old('user_id') == $e->id)>{{ $e->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="number" name="amount" class="form-control" value="{{ old('amount', 0) }}" required>
                            </div>
                            <div class="col-md-3">
                                <input type="text" name="note" class="form-control" value="{{ old('note') }}" placeholder="Keterangan">
                            </div>
                            <div class="col-12 d-grid">
                                <button class="btn btn-primary" type="submit">{{ __('Tambah') }}</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <form class="d-flex gap-2" method="GET" action="{{ route('hr-operational.index') }}">
                        <input type="hidden" name="tab" value="daily">
                        <input type="date" name="date" class="form-control" value="{{ $date ?? now()->toDateString() }}">
                        <input type="text" name="q" class="form-control" placeholder="Cari..." value="{{ $q ?? '' }}">
                        <button class="btn btn-outline-primary" type="submit">{{ __('Filter') }}</button>
                    </form>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('Karyawan') }}</th>
                                        <th>{{ __('Tanggal') }}</th>
                                        <th>{{ __('Nominal') }}</th>
                                        <th>{{ __('Source') }}</th>
                                        <th>{{ __('Note') }}</th>
                                        <th>{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($rows as $row)
                                        <tr>
                                            <td>{{ $row->id }}</td>
                                            <td>
                                                <div class="fw-bold">{{ $row->user_name }}</div>
                                                <div class="text-muted">{{ $row->user_email }}</div>
                                            </td>
                                            <td>{{ $row->date }}</td>
                                            <td>{{ $row->amount }}</td>
                                            <td>{{ $row->source ?? 'manual' }}</td>
                                            <td>{{ $row->note ?? '-' }}</td>
                                            <td class="d-flex gap-2">
                                                @if (($row->source ?? 'manual') !== 'auto')
                                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('hr-operational-dailies.edit', $row->id) }}">{{ __('Edit') }}</a>
                                                    <form method="POST" action="{{ route('hr-operational-dailies.destroy', $row->id) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus data?')">{{ __('Hapus') }}</button>
                                                    </form>
                                                @else
                                                    <span class="text-muted">{{ __('Auto') }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center">{{ __('Tidak ada data') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            {{ $rows->links() }}
                        </div>
                    </div>
                </div>
            @endif
        </section>
    </div>
@endsection
