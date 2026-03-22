@extends('layouts.app')

@section('title', __('Payroll'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Payroll') }}</h3>
                    <p class="text-subtitle text-muted">{{ __('Generate gaji, lembur, operasional, potongan wajib, dan potongan sanksi per periode.') }}</p>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="/dashboard">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Payroll') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <x-alert></x-alert>

            <div class="d-flex justify-content-end mb-3">
                <a href="{{ route('hr-payroll-periods.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> {{ __('Buat Periode') }}
                </a>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('Label') }}</th>
                                    <th>{{ __('Periode') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Generated') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rows as $row)
                                    <tr>
                                        <td>{{ $row->id }}</td>
                                        <td>{{ $row->label }}</td>
                                        <td>{{ $row->period_start }} - {{ $row->period_end }}</td>
                                        <td>{{ $row->status }}</td>
                                        <td>{{ $row->generated_at ?? '-' }}</td>
                                        <td>
                                            <a class="btn btn-sm btn-outline-primary" href="{{ route('hr-payroll-periods.show', $row->id) }}">{{ __('Detail') }}</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">{{ __('Tidak ada data') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $rows->links() }}
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

