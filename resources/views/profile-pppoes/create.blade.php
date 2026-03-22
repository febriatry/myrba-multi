@extends('layouts.app')

@section('title', __('Create Profile PPP'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Profile PPP') }}</h3>
                    <p class="text-subtitle text-muted">
                        {{ __('Tambah profile PPP ke router tertentu.') }}
                    </p>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="/dashboard">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('profile-pppoes.index') }}">{{ __('Profile PPP') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Create') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('profile-pppoes.store') }}" method="POST">
                                @csrf

                                <div class="row mb-2">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="router_id">{{ __('Router') }}</label>
                                            <select class="form-select @error('router_id') is-invalid @enderror" name="router_id"
                                                id="router_id" required>
                                                <option value="" selected disabled>-- {{ __('Pilih Router') }} --</option>
                                                @foreach (($routers ?? []) as $router)
                                                    <option value="{{ $router->id }}"
                                                        {{ old('router_id') == $router->id ? 'selected' : '' }}>
                                                        {{ $router->identitas_router }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('router_id')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="name">{{ __('Name') }}</label>
                                            <input type="text" name="name" id="name"
                                                class="form-control @error('name') is-invalid @enderror"
                                                value="{{ old('name') }}" placeholder="{{ __('Name') }}" required />
                                            @error('name')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="local_address">{{ __('Local Address') }}</label>
                                            <input type="text" name="local_address" id="local_address"
                                                class="form-control @error('local_address') is-invalid @enderror"
                                                value="{{ old('local_address') }}" placeholder="{{ __('Local Address') }}" />
                                            @error('local_address')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="remote_address">{{ __('Remote Address') }}</label>
                                            <input type="text" name="remote_address" id="remote_address"
                                                class="form-control @error('remote_address') is-invalid @enderror"
                                                value="{{ old('remote_address') }}" placeholder="{{ __('Remote Address') }}" />
                                            @error('remote_address')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="rate_limit">{{ __('Rate Limit') }}</label>
                                            <input type="text" name="rate_limit" id="rate_limit"
                                                class="form-control @error('rate_limit') is-invalid @enderror"
                                                value="{{ old('rate_limit') }}" placeholder="10M/10M" />
                                            @error('rate_limit')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="parent_queue">{{ __('Parent Queue') }}</label>
                                            <input type="text" name="parent_queue" id="parent_queue"
                                                class="form-control @error('parent_queue') is-invalid @enderror"
                                                value="{{ old('parent_queue') }}" placeholder="{{ __('Parent Queue') }}" />
                                            @error('parent_queue')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <a href="{{ url()->previous() }}" class="btn btn-secondary">{{ __('Back') }}</a>
                                <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

