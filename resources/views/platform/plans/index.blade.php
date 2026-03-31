@extends('layouts.app')

@section('title', 'Paket Tenant')

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>Paket Tenant</h3>
                    <p class="text-subtitle text-muted">Daftar paket tenant.</p>
                </div>
                <div class="col-12 col-md-4 order-md-2 order-first text-md-end">
                    <a href="{{ route('platform.settings.index') }}" class="btn btn-outline-secondary">Owner Settings</a>
                    <a href="{{ route('platform.wa-usage.index') }}" class="btn btn-outline-secondary">WA Usage</a>
                    <a href="{{ route('platform.plans.create') }}" class="btn btn-primary">Tambah Paket</a>
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
                                <th style="width:200px">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($plans as $p)
                                <tr>
                                    <td>{{ $p->id }}</td>
                                    <td class="fw-bold">{{ $p->name }}</td>
                                    <td>{{ $p->code }}</td>
                                    <td>{{ $p->status }}</td>
                                    <td class="d-flex gap-2">
                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('platform.plans.edit', $p->id) }}">Edit</a>
                                        <form method="POST" action="{{ route('platform.plans.destroy', $p->id) }}" onsubmit="return confirm('Hapus paket ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Belum ada paket</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
@endsection
