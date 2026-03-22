@extends('layouts.app')

@section('title', __('Checklist Pelanggan'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Checklist Pelanggan') }}</h3>
                    <p class="text-subtitle text-muted">{{ __('Rule: :rule - Investor: :user', ['rule' => $rule->rule_type, 'user' => $rule->user_name ?? '-']) }}</p>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('investor-share-rules.index') }}">{{ __('Aturan Bagi Hasil') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Checklist Pelanggan') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <x-alert></x-alert>

            <div class="card mb-3">
                <div class="card-body">
                    <form method="get" action="{{ route('investor-share-rules.customers', $rule->id) }}" class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Cari') }}</label>
                            <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="Nama / No Layanan">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('Area') }}</label>
                            <select name="area_id" class="form-control">
                                <option value="">{{ __('-') }}</option>
                                @foreach ($areas as $a)
                                    <option value="{{ $a->id }}" @selected((string) request('area_id') === (string) $a->id)>{{ $a->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('Paket') }}</label>
                            <select name="package_id" class="form-control">
                                <option value="">{{ __('-') }}</option>
                                @foreach ($packages as $p)
                                    <option value="{{ $p->id }}" @selected((string) request('package_id') === (string) $p->id)>{{ $p->nama_layanan }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button class="btn btn-primary w-100">{{ __('Filter') }}</button>
                        </div>
                    </form>
                </div>
            </div>

            <form method="post" action="{{ route('investor-share-rules.customers.update', $rule->id) }}">
                @csrf
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-light-secondary" id="btnSelectAll">{{ __('Pilih Semua (Halaman Ini)') }}</button>
                                <button type="button" class="btn btn-sm btn-light-secondary" id="btnUnselectAll">{{ __('Batal Pilih Semua') }}</button>
                            </div>
                            <button class="btn btn-success">{{ __('Simpan Checklist') }}</button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th style="width: 60px">{{ __('Pilih') }}</th>
                                        <th>{{ __('No Layanan') }}</th>
                                        <th>{{ __('Nama') }}</th>
                                        <th>{{ __('Area') }}</th>
                                        <th>{{ __('Paket') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($pelanggans as $p)
                                        <tr>
                                            <td class="text-center">
                                                <input type="hidden" name="page_pelanggan_ids[]" value="{{ $p->id }}">
                                                <input type="checkbox" class="ckPelanggan" name="pelanggan_ids[]" value="{{ $p->id }}" @checked(in_array((int) $p->id, $selectedIds, true))>
                                            </td>
                                            <td>{{ $p->no_layanan }}</td>
                                            <td>{{ $p->nama }}</td>
                                            <td>{{ $p->area_nama ?? '-' }}</td>
                                            <td>{{ $p->paket_nama ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center">{{ __('Tidak ada pelanggan.') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            {{ $pelanggans->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                </div>
            </form>
        </section>
    </div>

    <script>
        (function () {
            var btnSelectAll = document.getElementById('btnSelectAll');
            var btnUnselectAll = document.getElementById('btnUnselectAll');
            function setAll(value) {
                var items = document.querySelectorAll('.ckPelanggan');
                for (var i = 0; i < items.length; i++) {
                    items[i].checked = value;
                }
            }
            if (btnSelectAll) btnSelectAll.addEventListener('click', function () { setAll(true); });
            if (btnUnselectAll) btnUnselectAll.addEventListener('click', function () { setAll(false); });
        })();
    </script>
@endsection
