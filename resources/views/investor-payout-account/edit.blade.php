@extends('layouts.app')

@section('title', __('Rekening / E-Wallet'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Rekening / E-Wallet') }}</h3>
                    <p class="text-subtitle text-muted">{{ __('Data ini dipakai saat mengajukan request payout.') }}</p>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Rekening / E-Wallet') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <x-alert></x-alert>
            <div class="card">
                <div class="card-body">
                    <form method="post" action="{{ route('investor-payout-account.update') }}" class="row g-3">
                        @csrf
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Tipe') }}</label>
                            <select name="type" class="form-control" required>
                                <option value="bank" @selected(($account->type ?? '') === 'bank')>{{ __('Bank') }}</option>
                                <option value="ewallet" @selected(($account->type ?? '') === 'ewallet')>{{ __('E-Wallet') }}</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Provider') }}</label>
                            <input type="text" name="provider" class="form-control" value="{{ $account->provider ?? '' }}" placeholder="BCA / BRI / DANA / OVO / GoPay">
                        </div>
                        <div class="col-md-4"></div>

                        <div class="col-md-6">
                            <label class="form-label">{{ __('Nama Pemilik') }}</label>
                            <input type="text" name="account_name" class="form-control" required value="{{ $account->account_name ?? '' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Nomor Rekening / Nomor E-Wallet') }}</label>
                            <input type="text" name="account_number" class="form-control" required value="{{ $account->account_number ?? '' }}">
                        </div>

                        <div class="col-12">
                            <button class="btn btn-primary">{{ __('Simpan') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>
@endsection
