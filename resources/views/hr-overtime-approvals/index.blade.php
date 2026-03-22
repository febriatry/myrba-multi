@extends('layouts.app')

@section('title', __('ACC Lembur'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('ACC Lembur') }}</h3>
                    <p class="text-subtitle text-muted">{{ __('Hanya lembur yang di-ACC yang masuk ke perhitungan payroll.') }}</p>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="/dashboard">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('ACC Lembur') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <x-alert></x-alert>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <form class="d-flex gap-2" method="GET" action="{{ route('hr-overtime-approvals.index') }}">
                    <input type="date" name="date" class="form-control" value="{{ $date }}">
                    <select name="status" class="form-select">
                        <option value="pending" @selected($status === 'pending')>{{ __('Pending') }}</option>
                        <option value="approved" @selected($status === 'approved')>{{ __('Approved') }}</option>
                        <option value="rejected" @selected($status === 'rejected')>{{ __('Rejected') }}</option>
                    </select>
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
                                    <th>{{ __('Jabatan') }}</th>
                                    <th>{{ __('Tanggal') }}</th>
                                    <th>{{ __('In') }}</th>
                                    <th>{{ __('Out') }}</th>
                                    <th>{{ __('Lembur (hitung)') }}</th>
                                    <th>{{ __('ACC (menit)') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Catatan') }}</th>
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
                                        <td>{{ $row->date }}</td>
                                        <td>{{ $row->clock_in_at ?? '-' }}</td>
                                        <td>{{ $row->clock_out_at ?? '-' }}</td>
                                        <td>{{ (int) $row->overtime_minutes }} m</td>
                                        <td>{{ (int) ($row->overtime_approved_minutes ?? 0) }} m</td>
                                        <td>{{ $row->overtime_review_status ?? '-' }}</td>
                                        <td>{{ $row->overtime_review_note ?? '-' }}</td>
                                        <td class="d-flex gap-2 align-items-center">
                                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('hr-attendances.show', $row->id) }}">{{ __('Detail') }}</a>
                                            <form method="POST" action="{{ route('hr-overtime-approvals.approve', $row->id) }}" class="d-flex gap-2 align-items-center">
                                                @csrf
                                                <input type="number" name="approved_minutes" class="form-control form-control-sm" style="width:120px" min="0" max="{{ (int) $row->overtime_minutes }}" value="{{ (int) (($row->overtime_review_status ?? '') === 'approved' ? ($row->overtime_approved_minutes ?? 0) : $row->overtime_minutes) }}">
                                                <input type="text" name="note" class="form-control form-control-sm" style="width:180px" placeholder="{{ __('Catatan') }}" value="{{ (string) ($row->overtime_review_note ?? '') }}">
                                                <button class="btn btn-sm btn-success">{{ (($row->overtime_review_status ?? '') === 'approved') ? __('Update') : __('ACC') }}</button>
                                            </form>
                                            <form method="POST" action="{{ route('hr-overtime-approvals.reject', $row->id) }}">
                                                @csrf
                                                <input type="text" name="note" class="form-control form-control-sm" style="width:160px" placeholder="{{ __('Catatan') }}" value="{{ (string) ($row->overtime_review_note ?? '') }}">
                                                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Tolak lembur?')">{{ __('Tolak') }}</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center">{{ __('Tidak ada data') }}</td>
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
