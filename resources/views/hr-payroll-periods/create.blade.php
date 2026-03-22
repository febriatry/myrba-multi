@extends('layouts.app')

@section('title', __('Buat Periode Payroll'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Buat Periode Payroll') }}</h3>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="/dashboard">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('hr-payroll-periods.index') }}">{{ __('Payroll') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Buat Periode') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <x-alert></x-alert>

            <div class="card">
                <div class="card-body">
                    <form action="{{ route('hr-payroll-periods.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">{{ __('Bulan (YYYY-MM)') }}</label>
                            <input type="month" name="month" class="form-control" value="{{ old('month', now()->format('Y-m')) }}" required>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary" type="submit">{{ __('Simpan') }}</button>
                            <a href="{{ route('hr-payroll-periods.index') }}" class="btn btn-light">{{ __('Batal') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>
@endsection

