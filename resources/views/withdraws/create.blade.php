@extends('layouts.app')

@section('title', __('Buat Permintaan Withdraw'))

@section('content')
    <div class="page-body">
        <div class="container-fluid">
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-6">
                        <h3>{{ __('Withdraw') }}</h3>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('withdraws.index') }}">{{ __('Withdraw') }}</a></li>
                            <li class="breadcrumb-item active" aria-current="page">{{ __('Buat') }}</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('withdraws.store') }}" method="POST">
                                @csrf
                                @include('withdraws.include.form')
                                <div class="mt-3">
                                    <a href="{{ route('withdraws.index') }}"
                                        class="btn btn-secondary">{{ __('Batal') }}</a>
                                    <button type-="submit" id="saveBtn"
                                        class="btn btn-primary">{{ __('Simpan') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
