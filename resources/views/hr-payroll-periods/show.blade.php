@extends('layouts.app')

@section('title', __('Detail Payroll'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Detail Payroll') }}</h3>
                    <p class="text-subtitle text-muted">{{ $row->label }} | {{ $row->period_start }} - {{ $row->period_end }}</p>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="/dashboard">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('hr-payroll-periods.index') }}">{{ __('Payroll') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Detail') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <x-alert></x-alert>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <div class="fw-bold">{{ __('Status') }}: {{ $row->status }}</div>
                    <div class="text-muted">{{ __('Generated') }}: {{ $row->generated_at ?? '-' }}</div>
                    <div class="text-muted">{{ __('Posting Keuangan') }}: {{ $row->posted_at ?? '-' }}</div>
                </div>
                <div class="d-flex gap-2">
                    <a class="btn btn-outline-secondary" href="{{ route('hr-payroll-periods.export-pdf', $row->id) }}">{{ __('Export PDF') }}</a>
                    @if (empty($row->finance_pengeluaran_id))
                        <form method="POST" action="{{ route('hr-payroll-periods.post-keuangan', $row->id) }}">
                            @csrf
                            <button class="btn btn-outline-primary" onclick="return confirm('Posting payroll ini ke laporan keuangan sebagai pengeluaran?')">{{ __('Post Keuangan') }}</button>
                        </form>
                    @else
                        <span class="text-muted">{{ __('pengeluaran #') }}{{ $row->finance_pengeluaran_id }}</span>
                    @endif
                    @if ($row->status !== 'locked')
                        <form method="POST" action="{{ route('hr-payroll-periods.generate', $row->id) }}">
                            @csrf
                            <button class="btn btn-primary" onclick="return confirm('Generate payroll untuk periode ini?')">{{ __('Generate') }}</button>
                        </form>
                        <form method="POST" action="{{ route('hr-payroll-periods.lock', $row->id) }}">
                            @csrf
                            <button class="btn btn-outline-danger" onclick="return confirm('Kunci periode payroll?')">{{ __('Lock') }}</button>
                        </form>
                    @endif
                    <a class="btn btn-light" href="{{ route('hr-payroll-periods.index') }}">{{ __('Kembali') }}</a>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3"><div class="fw-bold">{{ __('Karyawan') }}</div><div>{{ $summary['employees'] }}</div></div>
                        <div class="col-md-3"><div class="fw-bold">{{ __('Hadir (hari)') }}</div><div>{{ $summary['present_days'] }}</div></div>
                        <div class="col-md-3"><div class="fw-bold">{{ __('Total Dibayar') }}</div><div>{{ $summary['grand_total'] }}</div></div>
                        <div class="col-md-3"><div class="fw-bold">{{ __('Potongan') }}</div><div>{{ $summary['mandatory_total'] + $summary['sanction_total'] + ($summary['other_deduction_total'] ?? 0) + ($summary['kasbon_deduction_total'] ?? 0) }}</div></div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>{{ __('Karyawan') }}</th>
                                    <th>{{ __('Jabatan') }}</th>
                                    <th>{{ __('Hadir') }}</th>
                                    <th>{{ __('Lembur (m)') }}</th>
                                    <th>{{ __('Gaji Pokok') }}</th>
                                    <th>{{ __('Lembur') }}</th>
                                    <th>{{ __('Operasional') }}</th>
                                    <th>{{ __('Pot Wajib') }}</th>
                                    <th>{{ __('Sanksi') }}</th>
                                    <th>{{ __('Potongan') }}</th>
                                    <th>{{ __('Kasbon') }}</th>
                                    <th>{{ __('Total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($items as $it)
                                    @php
                                        $meta = [];
                                        if (!empty($it->meta)) {
                                            $meta = json_decode($it->meta, true) ?: [];
                                        }
                                        $opAuto = (int) ($meta['operational_auto'] ?? 0);
                                        $opManual = (int) ($meta['operational_manual'] ?? 0);
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-bold">{{ $it->user_name }}</div>
                                            <div class="text-muted">{{ $it->user_email }}</div>
                                        </td>
                                        <td>{{ $it->jabatan_name ?? '-' }}</td>
                                        <td>{{ $it->present_days }}</td>
                                        <td>{{ $it->overtime_minutes }}</td>
                                        <td>{{ $it->base_amount }}</td>
                                        <td>{{ $it->overtime_amount }}</td>
                                        <td>
                                            <div>{{ $it->operational_amount }}</div>
                                            <div class="text-muted">auto: {{ $opAuto }} | manual: {{ $opManual }}</div>
                                        </td>
                                        <td>{{ $it->mandatory_deduction_amount }}</td>
                                        <td>{{ $it->sanction_deduction_amount }}</td>
                                        <td>{{ $it->other_deduction_amount ?? 0 }}</td>
                                        <td>{{ $it->kasbon_deduction_amount ?? 0 }}</td>
                                        <td class="fw-bold">{{ $it->total_amount }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="12" class="text-center">{{ __('Belum ada item payroll. Klik Generate.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
