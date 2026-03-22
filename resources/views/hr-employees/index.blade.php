@extends('layouts.app')

@section('title', __('Master Karyawan'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Master Karyawan') }}</h3>
                    <p class="text-subtitle text-muted">{{ __('Pilih user yang termasuk karyawan, atur jabatan dan skema jam kerja.') }}</p>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="/dashboard">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Master Karyawan') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <x-alert></x-alert>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <form class="d-flex gap-2" method="GET" action="{{ route('hr-employees.index') }}">
                    <input type="text" name="q" class="form-control" placeholder="Cari nama/email/kode..." value="{{ $q }}">
                    <button class="btn btn-outline-primary" type="submit">{{ __('Cari') }}</button>
                </form>
                <a href="{{ route('hr-employees.create') }}" class="btn btn-primary">
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
                                    <th>{{ __('User') }}</th>
                                    <th>{{ __('Kode') }}</th>
                                    <th>{{ __('Jabatan') }}</th>
                                    <th>{{ __('Skema') }}</th>
                                    <th>{{ __('Aktif') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rows as $row)
                                    <tr>
                                        <td>{{ $row->id }}</td>
                                        <td>
                                            <div class="fw-bold">{{ $row->user_name }}</div>
                                            <div class="text-muted">{{ $row->user_email }}</div>
                                        </td>
                                        <td>{{ $row->employee_code ?? '-' }}</td>
                                        <td>{{ $row->jabatan_name ?? '-' }}</td>
                                        <td>{{ $row->scheme_name ?? '-' }}</td>
                                        <td>{{ $row->is_active }}</td>
                                        <td class="d-flex gap-2">
                                            <a class="btn btn-sm btn-outline-primary" href="{{ route('hr-employees.edit', $row->id) }}">{{ __('Edit') }}</a>
                                            <form method="POST" action="{{ route('hr-employees.destroy', $row->id) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus data?')">{{ __('Hapus') }}</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">{{ __('Tidak ada data') }}</td>
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

