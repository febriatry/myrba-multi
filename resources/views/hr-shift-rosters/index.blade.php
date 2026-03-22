@extends('layouts.app')

@section('title', __('Jadwal Shift'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Jadwal Shift') }}</h3>
                    <p class="text-subtitle text-muted">{{ __('Atur shift per karyawan per tanggal.') }}</p>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="/dashboard">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Jadwal Shift') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <x-alert></x-alert>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <form class="d-flex gap-2" method="GET" action="{{ route('hr-shift-rosters.index') }}">
                    <input type="date" name="date" class="form-control" value="{{ $date }}">
                    <select class="form-select" name="user_id">
                        <option value="0">{{ __('Semua karyawan') }}</option>
                        @foreach ($employees as $e)
                            <option value="{{ $e->id }}" @selected((int) $userId === (int) $e->id)>{{ $e->name }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-outline-primary" type="submit">{{ __('Filter') }}</button>
                </form>
                <a href="{{ route('hr-shift-rosters.create') }}" class="btn btn-primary">
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
                                    <th>{{ __('Tanggal') }}</th>
                                    <th>{{ __('Karyawan') }}</th>
                                    <th>{{ __('Shift') }}</th>
                                    <th>{{ __('Jam') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rows as $row)
                                    <tr>
                                        <td>{{ $row->id }}</td>
                                        <td>{{ $row->date }}</td>
                                        <td>{{ $row->user_name }}</td>
                                        <td>{{ $row->shift_name }}</td>
                                        <td>{{ $row->start_time }} - {{ $row->end_time }}</td>
                                        <td>{{ $row->status }}</td>
                                        <td class="d-flex gap-2">
                                            <a class="btn btn-sm btn-outline-primary" href="{{ route('hr-shift-rosters.edit', $row->id) }}">{{ __('Edit') }}</a>
                                            <form method="POST" action="{{ route('hr-shift-rosters.destroy', $row->id) }}">
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

