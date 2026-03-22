@extends('layouts.app')

@section('title', __('Edit Setting Operasional'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Edit Setting Operasional') }}</h3>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="/dashboard">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('hr-operational-rules.index') }}">{{ __('Setting Operasional') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Edit') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <x-alert></x-alert>

            <div class="card">
                <div class="card-body">
                    <form action="{{ route('hr-operational-rules.update', $row->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label">{{ __('Scope (optional)') }}</label>
                            <select class="form-select" name="user_id">
                                <option value="">{{ __('global') }}</option>
                                @foreach ($employees as $e)
                                    <option value="{{ $e->id }}" @selected(old('user_id', $row->user_id) == $e->id)>{{ $e->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('Tanggal (optional)') }}</label>
                                <input type="date" name="date" class="form-control" value="{{ old('date', $row->date) }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('Hari (optional)') }}</label>
                                <select class="form-select" name="day_of_week">
                                    <option value="">{{ __('-') }}</option>
                                    @foreach ($days as $k => $v)
                                        <option value="{{ $k }}" @selected(old('day_of_week', $row->day_of_week) == $k)>{{ $v }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('Amount') }}</label>
                                <input type="number" name="amount" class="form-control" value="{{ old('amount', $row->amount) }}" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('Aktif') }}</label>
                                <select class="form-select" name="is_active" required>
                                    <option value="Yes" @selected(old('is_active', $row->is_active) === 'Yes')>Yes</option>
                                    <option value="No" @selected(old('is_active', $row->is_active) === 'No')>No</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('Note') }}</label>
                                <input type="text" name="note" class="form-control" value="{{ old('note', $row->note) }}">
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button class="btn btn-primary" type="submit">{{ __('Update') }}</button>
                            <a href="{{ route('hr-operational-rules.index') }}" class="btn btn-light">{{ __('Kembali') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>
@endsection

