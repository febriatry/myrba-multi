@extends('layouts.app')

@section('title', __('Monitor Status WA'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Monitor Status WA') }}</h3>
                    <p class="text-subtitle text-muted">
                        {{ __('Pantau status pengiriman WhatsApp dari webhook Ivosight.') }}
                    </p>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Monitor Status WA') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted">{{ __('Total Log') }}</h6>
                            <h4 class="mb-0">{{ number_format($summary['total']) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted">{{ __('Sukses') }}</h6>
                            <h4 class="mb-0 text-success">{{ number_format($summary['success']) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted">{{ __('Gagal') }}</h6>
                            <h4 class="mb-0 text-danger">{{ number_format($summary['failed']) }}</h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" action="{{ route('wa-status-logs.index') }}">
                        <div class="row g-2">
                            <div class="col-md-3">
                                <label class="form-label">{{ __('Status') }}</label>
                                <select class="form-select" name="status">
                                    <option value="">{{ __('Semua Status') }}</option>
                                    @foreach ($statusOptions as $opt)
                                        <option value="{{ $opt }}" {{ ($filters['status'] ?? '') === $opt ? 'selected' : '' }}>
                                            {{ strtoupper($opt) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('Nomor Penerima') }}</label>
                                <input type="text" name="recipient_id" class="form-control" value="{{ $filters['recipient_id'] ?? '' }}"
                                    placeholder="{{ __('Cari nomor') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('Message ID') }}</label>
                                <input type="text" name="message_id" class="form-control" value="{{ $filters['message_id'] ?? '' }}"
                                    placeholder="{{ __('Cari message id') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('Tanggal Mulai') }}</label>
                                <input type="date" name="start_date" class="form-control" value="{{ $filters['start_date'] ?? '' }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('Tanggal Akhir') }}</label>
                                <input type="date" name="end_date" class="form-control" value="{{ $filters['end_date'] ?? '' }}">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">{{ __('Filter') }}</button>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <a href="{{ route('wa-status-logs.index') }}" class="btn btn-light-secondary w-100">{{ __('Reset') }}</a>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <a href="{{ route('wa-status-logs.export-csv', request()->query()) }}"
                                    class="btn btn-success w-100">{{ __('Export CSV') }}</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>{{ __('Waktu') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Nomor') }}</th>
                                    <th>{{ __('Message ID') }}</th>
                                    <th>{{ __('Type') }}</th>
                                    <th>{{ __('Error') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($logs as $log)
                                    @php
                                        $badgeClass = match ($log->status) {
                                            'failed' => 'bg-danger',
                                            'sent' => 'bg-info',
                                            'delivered' => 'bg-primary',
                                            'read' => 'bg-success',
                                            default => 'bg-secondary',
                                        };
                                    @endphp
                                    <tr>
                                        <td>{{ optional($log->status_at)->format('Y-m-d H:i:s') ?? '-' }}</td>
                                        <td><span class="badge {{ $badgeClass }}">{{ strtoupper($log->status) }}</span></td>
                                        <td>{{ $log->recipient_id ?? '-' }}</td>
                                        <td>{{ $log->message_id }}</td>
                                        <td>{{ $log->type ?? '-' }}</td>
                                        <td>
                                            @if (is_array($log->errors) && count($log->errors) > 0)
                                                {{ $log->errors[0]['title'] ?? json_encode($log->errors) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">{{ __('Belum ada data status WA.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        <style>
                            .pagination .page-link {
                                padding: .25rem .5rem;
                                font-size: .875rem;
                                line-height: 1.1;
                            }
                        </style>
                        {{ $logs->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
