@extends('layouts.app')

@section('title', __('Audit Keuangan'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Audit Keuangan') }}</h3>
                    <p class="text-subtitle text-muted">
                        {{ __('Ringkasan tunggakan, tagihan belum dibuat, dan status pengiriman tagihan.') }}
                    </p>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="/dashboard">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Audit Keuangan') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <x-alert></x-alert>

            <div class="card mb-3">
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label" for="audit-area">{{ __('Area') }}</label>
                            <select class="form-select" id="audit-area">
                                <option value="">{{ __('Semua Area') }}</option>
                                @foreach ($areaCoverages as $area)
                                    <option value="{{ $area->id }}">{{ $area->kode_area }} - {{ $area->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="audit-periode">{{ __('Periode') }}</label>
                            <input type="month" class="form-control" id="audit-periode" value="{{ $defaultPeriode }}">
                        </div>
                        <div class="col-md-4 d-flex align-items-end gap-2">
                            <button type="button" class="btn btn-primary w-100" id="audit-apply">{{ __('Terapkan Filter') }}</button>
                            @can('audit keuangan export')
                                <div class="dropdown w-100">
                                    <button class="btn btn-success w-100 dropdown-toggle" type="button" id="audit-export"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        {{ __('Export') }}
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="audit-export">
                                        <li>
                                            <a class="dropdown-item" href="#" data-export-type="summary-area" data-export-format="excel">
                                                {{ __('Ringkasan Area (Excel)') }}
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="#" data-export-type="summary-area" data-export-format="pdf">
                                                {{ __('Ringkasan Area (PDF)') }}
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="#" data-export-type="summary-area" data-export-format="csv">
                                                {{ __('Ringkasan Area (CSV)') }}
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item" href="#" data-export-type="pelanggan-tunggak" data-export-format="excel">
                                                {{ __('Pelanggan Menunggak (Excel)') }}
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="#" data-export-type="pelanggan-tunggak" data-export-format="pdf">
                                                {{ __('Pelanggan Menunggak (PDF)') }}
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="#" data-export-type="pelanggan-tunggak" data-export-format="csv">
                                                {{ __('Pelanggan Menunggak (CSV)') }}
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item" href="#" data-export-type="missing-tagihan" data-export-format="excel">
                                                {{ __('Tagihan Belum Dibuat (Excel)') }}
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="#" data-export-type="missing-tagihan" data-export-format="pdf">
                                                {{ __('Tagihan Belum Dibuat (PDF)') }}
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="#" data-export-type="missing-tagihan" data-export-format="csv">
                                                {{ __('Tagihan Belum Dibuat (CSV)') }}
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item" href="#" data-export-type="wa-status" data-export-format="excel">
                                                {{ __('Status Kirim Tagihan (Excel)') }}
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="#" data-export-type="wa-status" data-export-format="pdf">
                                                {{ __('Status Kirim Tagihan (PDF)') }}
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="#" data-export-type="wa-status" data-export-format="csv">
                                                {{ __('Status Kirim Tagihan (CSV)') }}
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>

            <ul class="nav nav-tabs" id="audit-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tab-area-summary" data-bs-toggle="tab" data-bs-target="#pane-area-summary"
                        type="button" role="tab">
                        {{ __('Ringkasan Area') }}
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-pelanggan-tunggak" data-bs-toggle="tab" data-bs-target="#pane-pelanggan-tunggak"
                        type="button" role="tab">
                        {{ __('Pelanggan Menunggak') }}
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-missing-tagihan" data-bs-toggle="tab" data-bs-target="#pane-missing-tagihan"
                        type="button" role="tab">
                        {{ __('Tagihan Belum Dibuat') }}
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-wa-status" data-bs-toggle="tab" data-bs-target="#pane-wa-status"
                        type="button" role="tab">
                        {{ __('Status Kirim Tagihan') }}
                    </button>
                </li>
            </ul>

            <div class="tab-content pt-3">
                <div class="tab-pane fade show active" id="pane-area-summary" role="tabpanel">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive p-1">
                                <table class="table table-striped" id="table-area-summary" width="100%">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>{{ __('Area') }}</th>
                                            <th>{{ __('Pelanggan Menunggak') }}</th>
                                            <th>{{ __('Total Tagihan Belum Bayar') }}</th>
                                            <th>{{ __('Total Tunggakan') }}</th>
                                            <th>{{ __('Maks Bulan Tunggakan') }}</th>
                                            <th>{{ __('1 Bulan') }}</th>
                                            <th>{{ __('2 Bulan') }}</th>
                                            <th>{{ __('3+ Bulan') }}</th>
                                            <th>{{ __('WA Terkirim') }}</th>
                                            <th>{{ __('WA Belum') }}</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="pane-pelanggan-tunggak" role="tabpanel">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive p-1">
                                <table class="table table-striped" id="table-pelanggan-tunggak" width="100%">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>{{ __('Area') }}</th>
                                            <th>{{ __('No Layanan') }}</th>
                                            <th>{{ __('Nama') }}</th>
                                            <th>{{ __('Status') }}</th>
                                            <th>{{ __('Jumlah Bulan Belum Bayar') }}</th>
                                            <th>{{ __('Total Tunggakan') }}</th>
                                            <th>{{ __('Periode Tertua') }}</th>
                                            <th>{{ __('Periode Terbaru') }}</th>
                                            <th>{{ __('Last WA') }}</th>
                                            <th>{{ __('WA Sent') }}</th>
                                            <th>{{ __('WA Unsent') }}</th>
                                            <th>{{ __('Kirim WA?') }}</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="pane-missing-tagihan" role="tabpanel">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive p-1">
                                <table class="table table-striped" id="table-missing-tagihan" width="100%">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>{{ __('Periode') }}</th>
                                            <th>{{ __('Area') }}</th>
                                            <th>{{ __('No Layanan') }}</th>
                                            <th>{{ __('Nama') }}</th>
                                            <th>{{ __('Tanggal Daftar') }}</th>
                                            <th>{{ __('Status') }}</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="pane-wa-status" role="tabpanel">
                    <div class="card">
                        <div class="card-body">
                            <div class="row g-2 mb-2">
                                <div class="col-md-4">
                                    <label class="form-label" for="wa-only-unpaid">{{ __('Tampilkan') }}</label>
                                    <select class="form-select" id="wa-only-unpaid">
                                        <option value="1">{{ __('Hanya Belum Bayar') }}</option>
                                        <option value="0">{{ __('Semua Status') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="wa-only-unsent">{{ __('Filter Pengiriman') }}</label>
                                    <select class="form-select" id="wa-only-unsent">
                                        <option value="0">{{ __('Semua') }}</option>
                                        <option value="1">{{ __('Hanya Belum Terkirim') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="table-responsive p-1">
                                <table class="table table-striped" id="table-wa-status" width="100%">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>{{ __('Periode') }}</th>
                                            <th>{{ __('Area') }}</th>
                                            <th>{{ __('No Tagihan') }}</th>
                                            <th>{{ __('No Layanan') }}</th>
                                            <th>{{ __('Nama') }}</th>
                                            <th>{{ __('Total') }}</th>
                                            <th>{{ __('Status Bayar') }}</th>
                                            <th>{{ __('Kirim WA?') }}</th>
                                            <th>{{ __('Is Send') }}</th>
                                            <th>{{ __('Tanggal Kirim') }}</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('css')
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs5/dt-1.12.0/datatables.min.css" />
@endpush

@push('js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"
        integrity="sha512-894YE6QWD5I59HgZOGReFYm4dnWc1Qt5NtvYSaNcOP+u1T9qYdvdihz0PPSiiqn/+/3e7Jo4EaG7TubfWGUrMQ=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdn.jsdelivr.net/combine/npm/datatables.net@1.12.0,npm/datatables.net-bs5@1.12.0"></script>
    <script>
        (function() {
            function currentFilters() {
                return {
                    area_id: $('#audit-area').val() || '',
                    periode: $('#audit-periode').val() || '',
                };
            }

            function buildExportUrl(type, format) {
                const f = currentFilters();
                const qs = new URLSearchParams();
                if (f.area_id) qs.set('area_id', f.area_id);
                if (f.periode) qs.set('periode', f.periode);
                if (type === 'wa-status') {
                    qs.set('only_unpaid', $('#wa-only-unpaid').val() || '1');
                    qs.set('only_unsent', $('#wa-only-unsent').val() || '0');
                }

                if (type === 'summary-area' && format === 'csv') return "{{ route('audit-keuangan.export.summary-area') }}" + (qs.toString() ? ('?' + qs.toString()) : '');
                if (type === 'pelanggan-tunggak' && format === 'csv') return "{{ route('audit-keuangan.export.pelanggan-tunggak') }}" + (qs.toString() ? ('?' + qs.toString()) : '');
                if (type === 'missing-tagihan' && format === 'csv') return "{{ route('audit-keuangan.export.missing-tagihan') }}" + (qs.toString() ? ('?' + qs.toString()) : '');
                if (type === 'wa-status' && format === 'csv') return "{{ route('audit-keuangan.export.wa-status') }}" + (qs.toString() ? ('?' + qs.toString()) : '');

                if (type === 'summary-area' && format === 'excel') return "{{ route('audit-keuangan.export.summary-area.excel') }}" + (qs.toString() ? ('?' + qs.toString()) : '');
                if (type === 'pelanggan-tunggak' && format === 'excel') return "{{ route('audit-keuangan.export.pelanggan-tunggak.excel') }}" + (qs.toString() ? ('?' + qs.toString()) : '');
                if (type === 'missing-tagihan' && format === 'excel') return "{{ route('audit-keuangan.export.missing-tagihan.excel') }}" + (qs.toString() ? ('?' + qs.toString()) : '');
                if (type === 'wa-status' && format === 'excel') return "{{ route('audit-keuangan.export.wa-status.excel') }}" + (qs.toString() ? ('?' + qs.toString()) : '');

                if (type === 'summary-area' && format === 'pdf') return "{{ route('audit-keuangan.export.summary-area.pdf') }}" + (qs.toString() ? ('?' + qs.toString()) : '');
                if (type === 'pelanggan-tunggak' && format === 'pdf') return "{{ route('audit-keuangan.export.pelanggan-tunggak.pdf') }}" + (qs.toString() ? ('?' + qs.toString()) : '');
                if (type === 'missing-tagihan' && format === 'pdf') return "{{ route('audit-keuangan.export.missing-tagihan.pdf') }}" + (qs.toString() ? ('?' + qs.toString()) : '');
                if (type === 'wa-status' && format === 'pdf') return "{{ route('audit-keuangan.export.wa-status.pdf') }}" + (qs.toString() ? ('?' + qs.toString()) : '');
                return '';
            }

            const areaSummaryTable = $('#table-area-summary').DataTable({
                processing: true,
                serverSide: true,
                searching: false,
                ajax: {
                    url: "{{ route('audit-keuangan.summary-area') }}",
                    data: function(d) {
                        const f = currentFilters();
                        d.area_id = f.area_id;
                    }
                },
                order: [[4, 'desc']],
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'kode_area', name: 'kode_area', render: function(data, type, row) { return (data || '-') + ' - ' + (row.area_nama || '-'); } },
                    { data: 'pelanggan_menunggak', name: 'pelanggan_menunggak' },
                    { data: 'tagihan_belum_bayar', name: 'tagihan_belum_bayar' },
                    { data: 'total_tunggakan', name: 'total_tunggakan' },
                    { data: 'max_bulan_tunggakan', name: 'max_bulan_tunggakan' },
                    { data: 'tunggakan_1_bulan', name: 'tunggakan_1_bulan' },
                    { data: 'tunggakan_2_bulan', name: 'tunggakan_2_bulan' },
                    { data: 'tunggakan_3_plus', name: 'tunggakan_3_plus' },
                    { data: 'wa_terkirim', name: 'wa_terkirim' },
                    { data: 'wa_belum', name: 'wa_belum' },
                ]
            });

            const pelangganTunggakTable = $('#table-pelanggan-tunggak').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('audit-keuangan.pelanggan-tunggak') }}",
                    data: function(d) {
                        const f = currentFilters();
                        d.area_id = f.area_id;
                    }
                },
                order: [[5, 'desc']],
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'kode_area', name: 'kode_area', render: function(data, type, row) { return (data || '-') + ' - ' + (row.area_nama || '-'); } },
                    { data: 'no_layanan', name: 'no_layanan' },
                    { data: 'nama', name: 'nama' },
                    { data: 'status_berlangganan', name: 'status_berlangganan' },
                    { data: 'unpaid_count', name: 'unpaid_count' },
                    { data: 'total_tunggakan', name: 'total_tunggakan' },
                    { data: 'oldest_periode', name: 'oldest_periode' },
                    { data: 'newest_periode', name: 'newest_periode' },
                    { data: 'last_wa_at', name: 'last_wa_at' },
                    { data: 'wa_sent_count', name: 'wa_sent_count' },
                    { data: 'wa_unsent_count', name: 'wa_unsent_count' },
                    { data: 'kirim_tagihan_wa', name: 'kirim_tagihan_wa' },
                ]
            });

            const missingTagihanTable = $('#table-missing-tagihan').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('audit-keuangan.missing-tagihan') }}",
                    data: function(d) {
                        const f = currentFilters();
                        d.area_id = f.area_id;
                        d.periode = f.periode;
                    }
                },
                order: [[3, 'asc']],
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'periode', name: 'periode' },
                    { data: 'kode_area', name: 'kode_area', render: function(data, type, row) { return (data || '-') + ' - ' + (row.area_nama || '-'); } },
                    { data: 'no_layanan', name: 'no_layanan' },
                    { data: 'nama', name: 'nama' },
                    { data: 'tanggal_daftar', name: 'tanggal_daftar' },
                    { data: 'status_berlangganan', name: 'status_berlangganan' },
                ]
            });

            const waStatusTable = $('#table-wa-status').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('audit-keuangan.wa-status') }}",
                    data: function(d) {
                        const f = currentFilters();
                        d.area_id = f.area_id;
                        d.periode = f.periode;
                        d.only_unpaid = $('#wa-only-unpaid').val() || '1';
                        d.only_unsent = $('#wa-only-unsent').val() || '0';
                    }
                },
                order: [[10, 'asc']],
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'periode', name: 'periode' },
                    { data: 'kode_area', name: 'kode_area', render: function(data, type, row) { return (data || '-') + ' - ' + (row.area_nama || '-'); } },
                    { data: 'no_tagihan', name: 'no_tagihan' },
                    { data: 'no_layanan', name: 'no_layanan' },
                    { data: 'nama', name: 'nama' },
                    { data: 'total_bayar', name: 'total_bayar' },
                    { data: 'status_bayar', name: 'status_bayar' },
                    { data: 'kirim_tagihan_wa', name: 'kirim_tagihan_wa' },
                    { data: 'is_send', name: 'is_send' },
                    { data: 'tanggal_kirim_notif_wa', name: 'tanggal_kirim_notif_wa' },
                ]
            });

            function reloadAll() {
                areaSummaryTable.ajax.reload();
                pelangganTunggakTable.ajax.reload();
                missingTagihanTable.ajax.reload();
                waStatusTable.ajax.reload();
            }

            $('#audit-apply').on('click', function() {
                reloadAll();
            });
            $('#wa-only-unpaid, #wa-only-unsent').on('change', function() {
                waStatusTable.ajax.reload();
            });
            $(document).on('click', '[data-export-type]', function(e) {
                e.preventDefault();
                const type = $(this).data('export-type');
                const format = $(this).data('export-format') || 'csv';
                const url = buildExportUrl(type, format);
                if (!url) return;
                window.open(url, '_blank');
            });
        })();
    </script>
@endpush
