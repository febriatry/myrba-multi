@extends('layouts.app')

@section('title', 'Super Admin Tenant')

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>Super Admin Tenant</h3>
                    <p class="text-subtitle text-muted">Kelola user Super Admin untuk tenant {{ $tenant->name }} ({{ $tenant->code }}).</p>
                </div>
                <div class="col-12 col-md-4 order-md-2 order-first text-md-end">
                    <a href="{{ route('platform.tenants.index') }}" class="btn btn-outline-secondary">Kembali</a>
                </div>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="row g-3">
            <div class="col-12 col-lg-5">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Tambah Super Admin</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('platform.tenants.super-admins.store', $tenant->id) }}" class="row g-2">
                            @csrf
                            <div class="col-12">
                                <label class="form-label">Nama</label>
                                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">No WA</label>
                                <input type="text" name="no_wa" class="form-control" value="{{ old('no_wa') }}" placeholder="628xxxxxxxxxx" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <button class="btn btn-primary">Tambah</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-7">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Daftar Super Admin</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nama</th>
                                        <th>Email</th>
                                        <th>No WA</th>
                                        <th style="width: 120px">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($admins as $a)
                                        <tr>
                                            <td>{{ (int) $a->id }}</td>
                                            <td class="fw-bold">{{ $a->name }}</td>
                                            <td>{{ $a->email }}</td>
                                            <td>{{ $a->no_wa }}</td>
                                            <td>
                                                <form method="POST" action="{{ route('platform.tenants.super-admins.destroy', [$tenant->id, (int) $a->id]) }}" onsubmit="return confirm('Hapus role Super Admin dari user ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">Belum ada Super Admin</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

