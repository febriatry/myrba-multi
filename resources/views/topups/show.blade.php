@extends('layouts.app')

@section('title', __('Detail of Topup'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Topup') }}</h3>
                    <p class="text-subtitle text-muted">
                        {{ __('Detail of topup.') }}
                    </p>
                </div>

                <x-breadcrumb>
                    <li class="breadcrumb-item">
                        <a href="/">{{ __('Dashboard') }}</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('topups.index') }}">{{ __('Topup') }}</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        {{ __('Detail') }}
                    </li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped">
                                    <tr>
                                            <td class="fw-bold">{{ __('No Topup') }}</td>
                                            <td>{{ $topup->no_topup }}</td>
                                        </tr>
									<tr>
                                        <td class="fw-bold">{{ __('Pelanggan') }}</td>
                                        <td>{{ $topup->pelanggan ? $topup->pelanggan->coverage_area : '' }}</td>
                                    </tr>
									<tr>
                                            <td class="fw-bold">{{ __('Tanggal Topup') }}</td>
                                            <td>{{ isset($topup->tanggal_topup) ? $topup->tanggal_topup->format('d/m/Y H:i') : ''  }}</td>
                                        </tr>
									<tr>
                                            <td class="fw-bold">{{ __('Nominal') }}</td>
                                            <td>{{ $topup->nominal }}</td>
                                        </tr>
									<tr>
                                            <td class="fw-bold">{{ __('Status') }}</td>
                                            <td>{{ $topup->status }}</td>
                                        </tr>
									<tr>
                                            <td class="fw-bold">{{ __('Metode Topup') }}</td>
                                            <td>{{ $topup->metode_topup }}</td>
                                        </tr>
									<tr>
                                            <td class="fw-bold">{{ __('Payload Tripay') }}</td>
                                            <td>{{ $topup->payload_tripay }}</td>
                                        </tr>
									<tr>
                                            <td class="fw-bold">{{ __('Tanggal Callback Tripay') }}</td>
                                            <td>{{ isset($topup->tanggal_callback_tripay) ? $topup->tanggal_callback_tripay->format('d/m/Y H:i') : ''  }}</td>
                                        </tr>
                                    <tr>
                                        <td class="fw-bold">{{ __('Created at') }}</td>
                                        <td>{{ $topup->created_at->format('d/m/Y H:i') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">{{ __('Updated at') }}</td>
                                        <td>{{ $topup->updated_at->format('d/m/Y H:i') }}</td>
                                    </tr>
                                </table>
                            </div>

                            <a href="{{ url()->previous() }}" class="btn btn-secondary">{{ __('Back') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
