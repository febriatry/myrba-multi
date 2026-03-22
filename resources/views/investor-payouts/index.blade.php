@extends('layouts.app')

@section('title', __('Request Payout'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Request Payout') }}</h3>
                </div>
            </div>
        </div>
        <section class="section">
            <x-alert></x-alert>
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted">{{ __('Saldo') }}</h6>
                            <h4 class="mb-0">{{ number_format($balance, 0, ',', '.') }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted">{{ __('Pending Request') }}</h6>
                            <h4 class="mb-0">{{ number_format($pendingAmount ?? 0, 0, ',', '.') }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted">{{ __('Saldo Tersedia') }}</h6>
                            <h4 class="mb-0">{{ number_format($available ?? 0, 0, ',', '.') }}</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted">{{ __('Tujuan Payout') }}</div>
                            @if (!empty($account))
                                <div>
                                    {{ strtoupper($account->type ?? '-') }}
                                    @if (!empty($account->provider))
                                        - {{ $account->provider }}
                                    @endif
                                    - {{ $account->account_number ?? '-' }}
                                    ({{ $account->account_name ?? '-' }})
                                </div>
                            @else
                                <div class="text-danger">{{ __('Belum diisi') }}</div>
                            @endif
                        </div>
                        <a href="{{ route('investor-payout-account.index') }}" class="btn btn-secondary">{{ __('Ubah Rekening/E-Wallet') }}</a>
                    </div>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-body">
                    <form method="post" action="{{ route('investor-payouts.store') }}" class="row g-2">
                        @csrf
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Nominal') }}</label>
                            <input type="number" step="0.01" name="amount" class="form-control" required>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button class="btn btn-primary w-100">{{ __('Request') }}</button>
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
                                    <th>#</th>
                                    <th>{{ __('Tujuan') }}</th>
                                    <th>{{ __('Amount') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Requested At') }}</th>
                                    <th>{{ __('Approved At') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($requests as $r)
                                    <tr>
                                        <td>{{ $r->id }}</td>
                                        <td>
                                            @if (!empty($r->payout_account_number))
                                                {{ strtoupper($r->payout_type ?? '-') }}
                                                @if (!empty($r->payout_provider))
                                                    - {{ $r->payout_provider }}
                                                @endif
                                                <div class="text-muted">{{ $r->payout_account_number }} ({{ $r->payout_account_name }})</div>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ number_format((float) $r->amount, 0, ',', '.') }}</td>
                                        <td>{{ $r->status }}</td>
                                        <td>{{ $r->requested_at }}</td>
                                        <td>{{ $r->approved_at }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">{{ __('Belum ada request.') }}</td>
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
