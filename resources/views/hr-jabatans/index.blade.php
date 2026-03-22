@extends('layouts.app')

@section('title', __('Master Jabatan'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Master Jabatan') }}</h3>
                    <p class="text-subtitle text-muted">{{ __('Kelola jabatan dan urutan jabatan.') }}</p>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="/dashboard">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Master Jabatan') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <x-alert></x-alert>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <form class="d-flex gap-2" method="GET" action="{{ route('hr-jabatans.index') }}">
                    <input type="text" name="q" class="form-control" placeholder="Cari..." value="{{ $q }}">
                    <button class="btn btn-outline-primary" type="submit">{{ __('Cari') }}</button>
                </form>
                <a href="{{ route('hr-jabatans.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> {{ __('Tambah') }}
                </a>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('Nama') }}</th>
                                    <th>{{ __('Urutan') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rows as $row)
                                    <tr>
                                        <td>{{ $row->id }}</td>
                                        <td>{{ $row->name }}</td>
                                        <td>{{ $row->rank_order }}</td>
                                        <td class="d-flex gap-2">
                                            <a class="btn btn-sm btn-outline-primary" href="{{ route('hr-jabatans.edit', $row->id) }}">{{ __('Edit') }}</a>
                                            <form method="POST" action="{{ route('hr-jabatans.destroy', $row->id) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus data?')">{{ __('Hapus') }}</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center">{{ __('Tidak ada data') }}</td>
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

