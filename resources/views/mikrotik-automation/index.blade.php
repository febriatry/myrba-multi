@extends('layouts.app')

@section('title', __('Mikrotik Automation'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Mikrotik Automation') }}</h3>
                    <p class="text-subtitle text-muted">{{ __('Konfigurasi isolir otomatis dan eksekusi manual isolir/buka isolir.') }}</p>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Mikrotik Automation') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <x-alert></x-alert>
            <div class="row">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h4>{{ __('Pengaturan Isolir Otomatis') }}</h4>
                        </div>
                        <div class="card-body">
                            <form method="post" action="{{ route('mikrotik-automation.settings') }}">
                                @csrf
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label">{{ __('Auto Isolir Aktif') }}</label>
                                        <select class="form-control" name="is_enabled">
                                            <option value="No" @selected(($settings['is_enabled'] ?? 'No') === 'No')>No</option>
                                            <option value="Yes" @selected(($settings['is_enabled'] ?? 'No') === 'Yes')>Yes</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">{{ __('Pakai Flag Pelanggan auto_isolir') }}</label>
                                        <select class="form-control" name="respect_pelanggan_auto_isolir">
                                            <option value="Yes" @selected(($settings['respect_pelanggan_auto_isolir'] ?? 'Yes') === 'Yes')>Yes</option>
                                            <option value="No" @selected(($settings['respect_pelanggan_auto_isolir'] ?? 'Yes') === 'No')>No</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">{{ __('Min Tagihan Belum Bayar') }}</label>
                                        <input type="number" name="min_unpaid_invoices" class="form-control" min="1" max="20"
                                            value="{{ (int) ($settings['min_unpaid_invoices'] ?? 1) }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">{{ __('Hanya Overdue') }}</label>
                                        <select class="form-control" name="overdue_only">
                                            <option value="Yes" @selected(($settings['overdue_only'] ?? 'Yes') === 'Yes')>Yes</option>
                                            <option value="No" @selected(($settings['overdue_only'] ?? 'Yes') === 'No')>No</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">{{ __('Include Waiting Review') }}</label>
                                        <select class="form-control" name="include_waiting_review">
                                            <option value="Yes" @selected(($settings['include_waiting_review'] ?? 'Yes') === 'Yes')>Yes</option>
                                            <option value="No" @selected(($settings['include_waiting_review'] ?? 'Yes') === 'No')>No</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">{{ __('Scope') }}</label>
                                        <select class="form-control" name="scope_type" id="scope_type">
                                            <option value="All" @selected(($settings['scope_type'] ?? 'All') === 'All')>{{ __('Semua Wilayah') }}</option>
                                            <option value="AreaCoverage" @selected(($settings['scope_type'] ?? 'All') === 'AreaCoverage')>{{ __('Wilayah Tertentu') }}</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6" id="scopeAreaWrap" style="display:none;">
                                        <label class="form-label">{{ __('Pilih Wilayah') }}</label>
                                        <select class="form-control" name="scope_area_ids[]" multiple>
                                            @foreach ($areaCoverages as $a)
                                                <option value="{{ $a->id }}" @selected(in_array((int) $a->id, (array) ($settings['scope_area_ids'] ?? []), true))>
                                                    {{ $a->nama }} ({{ $a->kode_area }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">{{ __('Max Execute per Run') }}</label>
                                        <input type="number" name="max_execute_per_run" class="form-control" min="1" max="2000"
                                            value="{{ (int) ($settings['max_execute_per_run'] ?? 200) }}">
                                    </div>
                                </div>

                                <div class="mt-3 d-flex gap-2">
                                    <button class="btn btn-primary">{{ __('Simpan Pengaturan') }}</button>
                                    <a class="btn btn-outline-secondary" href="{{ route('mikrotik-automation.logs') }}">{{ __('Lihat Log') }}</a>
                                </div>
                            </form>

                            <hr>

                            <div class="d-flex gap-2">
                                <form method="post" action="{{ route('mikrotik-automation.run-now') }}">
                                    @csrf
                                    <input type="hidden" name="dry_run" value="Yes">
                                    <button class="btn btn-outline-primary" @disabled(($settings['is_enabled'] ?? 'No') !== 'Yes')>{{ __('Simulasi Auto Isolir') }}</button>
                                </form>
                                <form method="post" action="{{ route('mikrotik-automation.run-now') }}">
                                    @csrf
                                    <input type="hidden" name="dry_run" value="No">
                                    <button class="btn btn-danger" @disabled(($settings['is_enabled'] ?? 'No') !== 'Yes')>{{ __('Jalankan Auto Isolir Sekarang') }}</button>
                                </form>
                            </div>
                            @if (($settings['is_enabled'] ?? 'No') !== 'Yes')
                                <div class="mt-2 text-muted">{{ __('Auto isolir sedang nonaktif. Aktifkan dulu agar bisa dijalankan.') }}</div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h4>{{ __('Eksekusi Manual (Massal)') }}</h4>
                        </div>
                        <div class="card-body">
                            <form method="get" action="{{ route('mikrotik-automation.index') }}" class="row g-2 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">{{ __('Wilayah') }}</label>
                                    <select name="area_coverage" class="form-control">
                                        <option value="">{{ __('Semua') }}</option>
                                        @foreach ($areaCoverages as $a)
                                            <option value="{{ $a->id }}" @selected((string) $filters['area_coverage'] === (string) $a->id)>{{ $a->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">{{ __('Router') }}</label>
                                    <select name="router" class="form-control">
                                        <option value="">{{ __('Semua') }}</option>
                                        @foreach ($routers as $r)
                                            <option value="{{ $r->id }}" @selected((string) $filters['router'] === (string) $r->id)>{{ $r->identitas_router }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">{{ __('Min Unpaid') }}</label>
                                    <input type="number" class="form-control" name="min_unpaid" min="1" max="20" value="{{ (int) $filters['min_unpaid'] }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('Overdue Only') }}</label>
                                    <select name="overdue_only" class="form-control">
                                        <option value="Yes" @selected((string) $filters['overdue_only'] === 'Yes')>Yes</option>
                                        <option value="No" @selected((string) $filters['overdue_only'] === 'No')>No</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('Include Waiting Review') }}</label>
                                    <select name="include_waiting_review" class="form-control">
                                        <option value="Yes" @selected((string) $filters['include_waiting_review'] === 'Yes')>Yes</option>
                                        <option value="No" @selected((string) $filters['include_waiting_review'] === 'No')>No</option>
                                    </select>
                                </div>
                                <div class="col-12 text-end">
                                    <button class="btn btn-outline-primary">{{ __('Filter') }}</button>
                                </div>
                            </form>

                            <form method="post" action="{{ route('mikrotik-automation.manual-execute') }}">
                                @csrf
                                <div class="d-flex gap-2 mb-2">
                                    <button type="submit" name="action" value="isolate" class="btn btn-danger">{{ __('Isolir Terpilih') }}</button>
                                    <button type="submit" name="action" value="unisolate" class="btn btn-success">{{ __('Buka Isolir Terpilih') }}</button>
                                    <a class="btn btn-outline-secondary" href="{{ route('mikrotik-automation.logs') }}">{{ __('Log') }}</a>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered">
                                        <thead>
                                            <tr>
                                                <th style="width:36px"></th>
                                                <th>{{ __('Pelanggan') }}</th>
                                                <th>{{ __('Wilayah') }}</th>
                                                <th>{{ __('Unpaid') }}</th>
                                                <th>{{ __('Overdue') }}</th>
                                                <th>{{ __('Mode') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($candidates as $c)
                                                <tr>
                                                    <td class="text-center">
                                                        <input type="checkbox" name="pelanggan_ids[]" value="{{ (int) $c->pelanggan_id }}">
                                                    </td>
                                                    <td>
                                                        <div>{{ $c->nama }}</div>
                                                        <div class="text-muted">{{ formatNoLayananTenant($c->no_layanan, (int) (auth()->user()->tenant_id ?? 0)) }}</div>
                                                    </td>
                                                    <td>{{ $c->area_nama ?? '-' }}</td>
                                                    <td>{{ (int) $c->unpaid_count }}</td>
                                                    <td>{{ (int) $c->overdue_count }}</td>
                                                    <td>{{ $c->mode_user }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="text-center">{{ __('Tidak ada kandidat.') }}</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-muted">{{ __('Maksimal 300 kandidat ditampilkan.') }}</div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <script>
        (function () {
            var scopeType = document.getElementById('scope_type');
            var wrap = document.getElementById('scopeAreaWrap');
            function toggle() {
                if (!scopeType || !wrap) return;
                wrap.style.display = scopeType.value === 'AreaCoverage' ? '' : 'none';
            }
            if (scopeType) {
                scopeType.addEventListener('change', toggle);
            }
            toggle();
        })();
    </script>
@endsection
