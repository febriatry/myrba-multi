@extends('layouts.app')

@section('title', __('Operasional Harian'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Operasional Harian') }}</h3>
                    <p class="text-subtitle text-muted">{{ __('Tambahan operasional manual per hari (di luar operasional auto dari profile).') }}</p>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="/dashboard">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Operasional Harian') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <x-alert></x-alert>

            <div class="card mb-3">
                <div class="card-body">
                    <form class="row g-2" method="POST" action="{{ route('hr-operational-dailies.store') }}">
                        @csrf
                        <div class="col-md-3">
                            <input type="date" name="date" class="form-control" value="{{ old('date', $date) }}" required>
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
                <form class="d-flex gap-2" method="GET" action="{{ route('hr-operational-dailies.index') }}">
                    <input type="date" name="date" class="form-control" value="{{ $date }}">
                    <input type="text" name="q" class="form-control" placeholder="Cari..." value="{{ $q }}">
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
        </section>
    </div>
@endsection
