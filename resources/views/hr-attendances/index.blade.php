@extends('layouts.app')

@section('title', __('Absensi Harian'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Absensi Harian') }}</h3>
                    <p class="text-subtitle text-muted">{{ __('Lihat dan input absensi. Perhitungan telat/lembur mengikuti skema jam kerja.') }}</p>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="/dashboard">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Absensi Harian') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <x-alert></x-alert>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <form class="d-flex gap-2" method="GET" action="{{ route('hr-attendances.index') }}">
                    <input type="date" name="date" class="form-control" value="{{ $date }}">
                    <input type="text" name="q" class="form-control" placeholder="Cari..." value="{{ $q }}">
                    <button class="btn btn-outline-primary" type="submit">{{ __('Filter') }}</button>
                </form>
                @can('attendance manage')
                    <a href="{{ route('hr-attendances.create', ['date' => $date]) }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> {{ __('Tambah') }}
                    </a>
                @endcan
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('Karyawan') }}</th>
                                    <th>{{ __('Jabatan') }}</th>
                                    <th>{{ __('In') }}</th>
                                    <th>{{ __('Out') }}</th>
                                    <th>{{ __('Telat') }}</th>
                                    <th>{{ __('Kerja') }}</th>
                                    <th>{{ __('Lembur') }}</th>
                                    <th>{{ __('ACC Lembur') }}</th>
                                    <th>{{ __('Valid') }}</th>
                                    <th>{{ __('Status') }}</th>
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
                                        <td>{{ $row->jabatan_name ?? '-' }}</td>
                                        <td>{{ $row->clock_in_at ?? '-' }}</td>
                                        <td>{{ $row->clock_out_at ?? '-' }}</td>
                                        <td>{{ $row->late_minutes }} m</td>
                                        <td>{{ $row->work_minutes }} m</td>
                                        <td>{{ $row->overtime_minutes }} m</td>
                                        <td>{{ (int) ($row->overtime_approved_minutes ?? 0) }} m ({{ $row->overtime_review_status ?? '-' }})</td>
                                        <td>{{ $row->review_status ?? '-' }}</td>
                                        <td>{{ $row->status }}</td>
                                        <td class="d-flex gap-2">
                                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('hr-attendances.show', $row->id) }}">{{ __('Detail') }}</a>
                                            @can('attendance manage')
                                                <a class="btn btn-sm btn-outline-primary" href="{{ route('hr-attendances.edit', $row->id) }}">{{ __('Edit') }}</a>
                                                <form method="POST" action="{{ route('hr-attendances.destroy', $row->id) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus data?')">{{ __('Hapus') }}</button>
                                                </form>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="12" class="text-center">{{ __('Tidak ada data') }}</td>
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
