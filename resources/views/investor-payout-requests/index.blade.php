@extends('layouts.app')

@section('title', __('Approve Payout Investor/Mitra'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Approve Payout Investor/Mitra') }}</h3>
                </div>
            </div>
        </div>
        <section class="section">
            <x-alert></x-alert>
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('User') }}</th>
                                    <th>{{ __('Tujuan') }}</th>
                                    <th>{{ __('Amount') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Requested At') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($requests as $r)
                                    <tr>
                                        <td>{{ $r->id }}</td>
                                        <td>{{ $r->user_name }}<div class="text-muted">{{ $r->user_email }}</div></td>
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
                                        <td class="d-flex gap-2">
                                            @if ($r->status === 'Pending')
                                                <form method="post" action="{{ route('investor-payout-requests.approve', $r->id) }}">
                                                    @csrf
                                                    <input type="hidden" name="action" value="approve">
                                                    <button class="btn btn-sm btn-success" onclick="return confirm('Approve request ini?')">{{ __('Approve') }}</button>
                                                </form>
                                                <form method="post" action="{{ route('investor-payout-requests.approve', $r->id) }}">
                                                    @csrf
                                                    <input type="hidden" name="action" value="reject">
                                                    <button class="btn btn-sm btn-danger" onclick="return confirm('Tolak request ini?')">{{ __('Reject') }}</button>
                                                </form>
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">{{ __('Belum ada request.') }}</td>
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
