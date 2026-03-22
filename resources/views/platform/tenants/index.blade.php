@extends('layouts.app')

@section('title', 'Tenant')

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>Tenant</h3>
                    <p class="text-subtitle text-muted">Daftar tenant.</p>
                </div>
                <div class="col-12 col-md-4 order-md-2 order-first text-md-end">
                    <a href="{{ route('platform.tenants.create') }}" class="btn btn-primary">Tambah Tenant</a>
                </div>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama</th>
                                <th>Code</th>
                                <th>Status</th>
                                <th>Paket</th>
                                <th style="width:200px">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($tenants as $t)
                                <tr>
                                    <td>{{ $t->id }}</td>
                                    <td class="fw-bold">{{ $t->name }}</td>
                                    <td>{{ $t->code }}</td>
                                    <td>{{ $t->status }}</td>
                                    <td>{{ $t->plan?->name ?? '-' }}</td>
                                    <td class="d-flex gap-2">
                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('platform.tenants.edit', $t->id) }}">Edit</a>
                                        <form method="POST" action="{{ route('platform.tenants.destroy', $t->id) }}" onsubmit="return confirm('Hapus tenant ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Belum ada tenant</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
@endsection

