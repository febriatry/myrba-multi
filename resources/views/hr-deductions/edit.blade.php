@extends('layouts.app')

@section('title', __('Edit Potongan'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Edit Potongan') }}</h3>
                    <p class="text-subtitle text-muted">{{ $row->user_name }} ({{ $row->user_email }})</p>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="/dashboard">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('hr-deductions.index', ['date' => $row->date]) }}">{{ __('Potongan') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Edit') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <x-alert></x-alert>

            <div class="card">
                <div class="card-body">
                    <form action="{{ route('hr-deductions.update', $row->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">{{ __('Tanggal') }}</label>
                                <input type="date" name="date" class="form-control" value="{{ old('date', $row->date) }}" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">{{ __('Nominal') }}</label>
                                <input type="number" name="amount" class="form-control" value="{{ old('amount', $row->amount) }}" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">{{ __('Tipe') }}</label>
                                <input type="text" name="type" class="form-control" value="{{ old('type', $row->type) }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">{{ __('Note') }}</label>
                                <input type="text" name="note" class="form-control" value="{{ old('note', $row->note) }}">
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary" type="submit">{{ __('Update') }}</button>
                            <a href="{{ route('hr-deductions.index', ['date' => $row->date]) }}" class="btn btn-light">{{ __('Kembali') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>
@endsection

