@extends('layouts.app')

@section('title', __('Detail of Informasi Management'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Informasi Management') }}</h3>
                    <p class="text-subtitle text-muted">
                        {{ __('Detail of informasi management.') }}
                    </p>
                </div>

                <x-breadcrumb>
                    <li class="breadcrumb-item">
                        <a href="/dashboard">{{ __('Dashboard') }}</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('informasi-managements.index') }}">{{ __('Informasi Management') }}</a>
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
                                            <td class="fw-bold">{{ __('Judul') }}</td>
                                            <td>{{ $informasiManagement->judul }}</td>
                                        </tr>
									<tr>
                                            <td class="fw-bold">{{ __('Deskripsi') }}</td>
                                            <td>{{ $informasiManagement->deskripsi }}</td>
                                        </tr>
									<tr>
                                        <td class="fw-bold">{{ __('Thumbnail') }}</td>
                                        <td>
                                            @if ($informasiManagement->thumbnail == null)
                                            <img src="https://via.placeholder.com/350?text=No+Image+Avaiable" alt="Thumbnail"  class="rounded" width="200" height="150" style="object-fit: cover">
                                            @else
                                                <img src="{{ asset('storage/uploads/thumbnails/' . $informasiManagement->thumbnail) }}" alt="Thumbnail" class="rounded" width="200" height="150" style="object-fit: cover">
                                            @endif
                                        </td>
                                    </tr>
									<tr>
                                            <td class="fw-bold">{{ __('Is Aktif') }}</td>
                                            <td>{{ $informasiManagement->is_aktif }}</td>
                                        </tr>
                                    <tr>
                                        <td class="fw-bold">{{ __('Created at') }}</td>
                                        <td>{{ $informasiManagement->created_at->format('d/m/Y H:i') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">{{ __('Updated at') }}</td>
                                        <td>{{ $informasiManagement->updated_at->format('d/m/Y H:i') }}</td>
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
