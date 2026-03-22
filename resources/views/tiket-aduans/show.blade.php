@extends('layouts.app')

@section('title', __('Detail of Tiket Aduan'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Tiket Aduan') }}</h3>
                    <p class="text-subtitle text-muted">
                        {{ __('Detail of tiket aduan.') }}
                    </p>
                </div>

                <x-breadcrumb>
                    <li class="breadcrumb-item">
                        <a href="/dashboard">{{ __('Dashboard') }}</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('tiket-aduans.index') }}">{{ __('Tiket Aduan') }}</a>
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
                                            <td class="fw-bold">{{ __('Nomor Tiket') }}</td>
                                            <td>{{ $tiketAduan->nomor_tiket }}</td>
                                        </tr>
									<tr>
                                        <td class="fw-bold">{{ __('Pelanggan') }}</td>
                                        <td>{{ $tiketAduan->pelanggan ? $tiketAduan->pelanggan->nama : '' }}</td>
                                    </tr>
									<tr>
                                            <td class="fw-bold">{{ __('Deskripsi Aduan') }}</td>
                                            <td>{{ $tiketAduan->deskripsi_aduan }}</td>
                                        </tr>
									<tr>
                                            <td class="fw-bold">{{ __('Tanggal Aduan') }}</td>
                                            <td>{{ isset($tiketAduan->tanggal_aduan) ? $tiketAduan->tanggal_aduan->format('d/m/Y H:i') : ''  }}</td>
                                        </tr>
									<tr>
                                            <td class="fw-bold">{{ __('Status') }}</td>
                                            <td>{{ $tiketAduan->status }}</td>
                                        </tr>
									<tr>
                                            <td class="fw-bold">{{ __('Prioritas') }}</td>
                                            <td>{{ $tiketAduan->prioritas }}</td>
                                        </tr>
									<tr>
                                        <td class="fw-bold">{{ __('Lampiran') }}</td>
                                        <td>
                                            @if ($tiketAduan->lampiran == null)
                                            <img src="https://dummyimage.com/350x350/cccccc/000000&text=No+Image" alt="Lampiran"  class="rounded" width="200" height="150" style="object-fit: cover">
                                            @else
                                                <img src="{{ asset('storage/uploads/lampirans/' . $tiketAduan->lampiran) }}" alt="Lampiran" class="rounded" width="200" height="150" style="object-fit: cover">
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">{{ __('Created at') }}</td>
                                        <td>{{ $tiketAduan->created_at->format('d/m/Y H:i') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">{{ __('Updated at') }}</td>
                                        <td>{{ $tiketAduan->updated_at->format('d/m/Y H:i') }}</td>
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
