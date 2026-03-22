@extends('layouts.app')

@section('title', __('Bank'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Bank') }}</h3>
                    <p class="text-subtitle text-muted">{{ __('Master bank dan rekening bank.') }}</p>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="/dashboard">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Bank') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <x-alert></x-alert>

            <ul class="nav nav-tabs mb-3">
                @can('bank account view')
                    <li class="nav-item">
                        <a class="nav-link @if (($tab ?? 'accounts') === 'accounts') active @endif" href="{{ route('finance-bank.index', ['tab' => 'accounts']) }}">{{ __('Bank Account') }}</a>
                    </li>
                @endcan
                @can('bank view')
                    <li class="nav-item">
                        <a class="nav-link @if (($tab ?? '') === 'banks') active @endif" href="{{ route('finance-bank.index', ['tab' => 'banks']) }}">{{ __('Bank') }}</a>
                    </li>
                @endcan
            </ul>

            <div class="card">
                <div class="card-body">
                    @if (($tab ?? 'accounts') === 'banks')
                        @can('bank view')
                            <iframe src="{{ route('banks.index', ['embed' => 1]) }}" style="width:100%; height: 80vh; border:0;"></iframe>
                        @else
                            <div class="text-muted">{{ __('Tidak memiliki akses bank.') }}</div>
                        @endcan
                    @else
                        @can('bank account view')
                            <iframe src="{{ route('bank-accounts.index', ['embed' => 1]) }}" style="width:100%; height: 80vh; border:0;"></iframe>
                        @else
                            <div class="text-muted">{{ __('Tidak memiliki akses bank account.') }}</div>
                        @endcan
                    @endif
                </div>
            </div>
        </section>
    </div>
@endsection
