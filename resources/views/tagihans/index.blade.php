@extends('layouts.app')

@section('title', __('Tagihan'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Tagihan') }}</h3>
                    <p class="text-subtitle text-muted">
                        {{ __('Berikut adalah daftar semua Tagihan.') }}
                    </p>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="/dashboard">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Tagihan') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <x-alert></x-alert>

            @can('tagihan create')
                <div class="d-flex justify-content-end">
                    <button class="btn btn-success mb-3" id="sendWaBtn" disabled>
                        <i class="ace-icon bi bi-whatsapp"></i> Kirim Notif Wa
                    </button>&nbsp;
                    <a href="{{ route('tagihans.create') }}" class="btn btn-primary mb-3">
                        <i class="fas fa-plus"></i>
                        {{ __('Tambah tagihan') }}
                    </a>
                </div>
            @endcan

            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="row g-3">
                                        <div class="col-12 col-md-2">
                                            <input type="month" value="{{ $thisMonth }}" name="tanggal" id="tanggal"
                                                class="form-control" />
                                        </div>
                                        <div class="col-6 col-md-2">
                                            <input type="month" value="{{ $fromMonth ?? '' }}" name="from_month" id="from_month"
                                                class="form-control" placeholder="Dari bulan" />
                                        </div>
                                        <div class="col-6 col-md-2">
                                            <input type="month" value="{{ $toMonth ?? '' }}" name="to_month" id="to_month"
                                                class="form-control" placeholder="Sampai bulan" />
                                        </div>
                                        <div class="col-12 col-md-2">
                                            <select name="pelanggan_id" id="pelanggan_id"
                                                class="form-control  js-example-basic-single">
                                                <option value="All">All Pelanggan</option>
                                                @foreach ($pelanggans as $row)
                                                    <option value="{{ $row->id }}"
                                                        {{ $selectedPelanggan == $row->id ? 'selected' : '' }}>
                                                        {{ $row->nama }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-12 col-md-3">
                                            <select name="area_coverage" id="area_coverage"
                                                class="form-control  js-example-basic-single">
                                                <option value="All">All Area Coverage
                                                </option>
                                                @foreach ($areaCoverages as $row)
                                                    <option value="{{ $row->id }}"
                                                        {{ $selectedAreaCoverage == $row->id ? 'selected' : '' }}>
                                                        {{ $row->nama }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-12 col-md-2">
                                            <select name="metode_bayar" id="metode_bayar"
                                                class="form-control  js-example-basic-single">
                                                <option value="All">All Metode Bayar</option>
                                                <option value="Cash"
                                                    {{ $selectedMetodeBayar == 'Cash' ? 'selected' : '' }}>Cash</option>
                                                <option value="Transfer Bank"
                                                    {{ $selectedMetodeBayar == 'Transfer Bank' ? 'selected' : '' }}>
                                                    Transfer Bank</option>
                                                <option value="Payment Tripay"
                                                    {{ $selectedMetodeBayar == 'Payment Tripay' ? 'selected' : '' }}>
                                                    Payment Tripay</option>
                                            </select>
                                        </div>

                                        <div class="col-12 col-md-2">
                                            <select name="status_bayar" id="status_bayar"
                                                class="form-control  js-example-basic-single">
                                                <option value="All">All Status Bayar
                                                </option>
                                                <option value="Sudah Bayar"
                                                    {{ $selectedStatusBayar == 'Sudah Bayar' ? 'selected' : '' }}>Sudah
                                                    Bayar</option>
                                                <option value="Waiting Review"
                                                    {{ $selectedStatusBayar == 'Waiting Review' ? 'selected' : '' }}>
                                                    Waiting Review</option>
                                                <option value="Belum Bayar"
                                                    {{ $selectedStatusBayar == 'Belum Bayar' ? 'selected' : '' }}>Belum
                                                    Bayar</option>
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-2">
                                            <select name="kirim_tagihan" id="kirim_tagihan"
                                                class="form-control  js-example-basic-single">
                                                <option value="All">All Kirim Tagihan
                                                </option>
                                                <option value="Yes" {{ $isSend == 'Yes' ? 'selected' : '' }}>
                                                    Sudah Kirim</option>
                                                <option value="No" {{ $isSend == 'No' ? 'selected' : '' }}>
                                                    Belum Kirim</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <hr>

                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <div class="card border-start border-0 border-3 border-success">
                                        <div class="card-body">
                                            <p class="mb-0 text-secondary">Jumlah Terbayar</p>
                                            <h4 class="my-1 text-success" id="paid-count">0</h4>
                                            <p class="mb-0 text-secondary">Total Terbayar</p>
                                            <h6 class="my-1 text-success" id="paid-sum">Rp 0</h6>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card border-start border-0 border-3 border-danger">
                                        <div class="card-body">
                                            <p class="mb-0 text-secondary">Jumlah Belum Bayar</p>
                                            <h4 class="my-1 text-danger" id="unpaid-count">0</h4>
                                            <p class="mb-0 text-secondary">Total Belum Bayar</p>
                                            <h6 class="my-1 text-danger" id="unpaid-sum">Rp 0</h6>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card border-start border-0 border-3 border-secondary">
                                        <div class="card-body">
                                            <p class="mb-0 text-secondary">Waiting Review</p>
                                            <h4 class="my-1 text-secondary" id="waiting-count">0</h4>
                                            <p class="mb-0 text-secondary">Total Waiting Review</p>
                                            <h6 class="my-1 text-secondary" id="waiting-sum">Rp 0</h6>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive p-1">
                                <table class="table table-striped" id="data-table" width="100%">
                                    <thead>
                                        <tr>
                                            <th><input type="checkbox" id="checkAll"></th>
                                            <th></th>
                                            <th>{{ __('ID Pelanggan') }}</th>
                                            <th>{{ __('Nama Pelanggan') }}</th>
                                            <th>{{ __('No Tagihan') }}</th>
                                            <th>{{ __('Total Bayar') }}</th>
                                            <th>{{ __('Status Bayar') }}</th>
                                            <th>{{ __('Sudah Kirim Tagihan ?') }}</th>
                                            <th>{{ __('Collector') }}</th>
                                            <th>{{ __('User Review') }}</th>
                                            <th>{{ __('Action') }}</th>
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css"
        integrity="sha512-KfkfwYDsLkIlwQp6LFnl8zNdLGxu9YAA1QvwINks4PhcElQSvqcyVLLD9aMhXd13uQjoXtEKNosOWaZqXgel0g=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs5/dt-1.12.0/r-2.3.0/datatables.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.3.0/css/responsive.bootstrap5.min.css" />
    <style>
        @media (max-width: 576px) {
            #data-table { font-size: 12px; }
            .table.table-striped td, .table.table-striped th { padding: 4px 6px; }
            .btn { padding: 4px 6px; font-size: 12px; }
            .form-control, .form-select { padding: 4px 6px; font-size: 12px; }
            .select2-container .select2-selection--single { height: 32px; }
            .select2-container .select2-selection__rendered { line-height: 32px; font-size: 12px; }
            .select2-container .select2-selection__arrow { height: 32px; }
        }
    </style>
@endpush

@push('js')
    <script src="https://cdn.jsdelivr.net/combine/npm/datatables.net@1.12.0,npm/datatables.net-bs5@1.12.0"></script>
    <script src="https://cdn.datatables.net/responsive/2.3.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.3.0/js/responsive.bootstrap5.min.js"></script>
    <script>
        function format(d) {
            return '<table cellpadding="5" cellspacing="0" border="0" style="padding-left:50px;">' +
                '<tr>' +
                '<td>Periode</td>' +
                '<td>' + d.periode + '</td>' +
                '</tr>' +
                '<tr>' +
                '<td>Metode Bayar</td>' +
                '<td>' + d.metode_bayar + '</td>' +
                '</tr>' +
                '<tr>' +
                '<td>Nominal Bayar</td>' +
                '<td>' + d.nominal_bayar + '</td>' +
                '</tr>' +
                '<tr>' +
                '<td>Potongan Bayar</td>' +
                '<td>' + d.potongan_bayar + '</td>' +
                '</tr>' +
                '<tr>' +
                '<td>PPN</td>' +
                '<td>' + d.nominal_ppn + '</td>' +
                '</tr>' +
                '<tr>' +
                '<td>Total Bayar</td>' +
                '<td>' + d.total_bayar + '</td>' +
                '</tr>' +
                '<tr>' +
                '<td>User Verifikasi</td>' +
                '<td>' + d.user + '</td>' +
                '</tr>' +
                '</table>' +
                '<div style="padding-left:50px; margin-top:8px;">' +
                '<div><strong>Actions</strong></div>' +
                '<div>' + (d.action ?? '') + '</div>' +
                '</div>';
        }

        $('#data-table').on('click', 'tbody td.dt-control', function() {
            var tr = $(this).closest('tr');
            var row = table.row(tr);

            if (row.child.isShown()) {
                // This row is already open - close it
                row.child.hide();
            } else {
                // Open this row
                row.child(format(row.data())).show();
            }
        });

        $('#data-table').on('requestChild.dt', function(e, row) {
            row.child(format(row.data())).show();
        })

        let columns = [{
                data: 'id',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    return '<input type="checkbox" class="checkbox" value="' + data + '">';
                }
            },
            {
                "className": 'dt-control',
                "orderable": false,
                "data": null,
                "defaultContent": ''
            },
            {
                data: 'no_layanan',
                name: 'no_layanan',
            },
            {
                data: 'pelanggan',
            },
            {
                data: 'no_tagihan',
                name: 'no_tagihan',
            },
            {
                data: 'total_bayar',
                name: 'total_bayar',
            },
            {
                data: 'status_bayar_tagihan',
                name: 'status_bayar_tagihan',
                orderable: false
            },
            {
                data: 'is_send',
                name: 'is_send',
                orderable: false
            },
            {
                data: 'user_input',
                name: 'user_input',
            },
            {
                data: 'user_review',
                name: 'user_review',
            },
            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false
            },
        ];

        let table = $('#data-table').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            scrollX: true,
            autoWidth: false,
            ajax: {
                url: '{{ route('tagihans.index') }}',
                data: function(d) {
                    d.tanggal = $('#tanggal').val();
                    d.pelanggan_id = $('#pelanggan_id').val();
                    d.area_coverage = $('#area_coverage').val();
                    d.metode_bayar = $('#metode_bayar').val();
                    d.status_bayar = $('#status_bayar').val();
                    d.kirim_tagihan = $('#kirim_tagihan').val();
                    d.from_month = $('#from_month').val();
                    d.to_month = $('#to_month').val();
                }
            },
            columns: columns,
            columnDefs: [
                { targets: 10, responsivePriority: 100 }, // Action moved to detail in mobile
                { targets: 2, responsivePriority: 2 },  // ID Pelanggan
                { targets: 3, responsivePriority: 3 }   // Nama Pelanggan
            ]
        });

        $('.js-example-basic-single').select2({
            theme: "bootstrap-5"
        });

        function replaceURLParams() {
            var params = new URLSearchParams();

            var tanggal = $("#tanggal").val();
            var from_month = $("#from_month").val();
            var to_month = $("#to_month").val();
            var pelanggan_id = $('select[name=pelanggan_id]').val();
            var area_coverage = $('select[name=area_coverage]').val();
            var metode_bayar = $('select[name=metode_bayar]').val();
            var status_bayar = $('select[name=status_bayar]').val();
            var kirim_tagihan = $('select[name=kirim_tagihan]').val();

            if (tanggal) params.set('tanggal', tanggal);
            if (from_month) params.set('from_month', from_month);
            if (to_month) params.set('to_month', to_month);
            if (pelanggan_id) params.set('pelanggan_id', pelanggan_id);
            if (area_coverage) params.set('area_coverage', area_coverage);
            if (metode_bayar) params.set('metode_bayar', metode_bayar);
            if (status_bayar) params.set('status_bayar', status_bayar);
            if (kirim_tagihan) params.set('kirim_tagihan', kirim_tagihan);

            var newURL = "{{ route('tagihans.index') }}" + '?' + params.toString();
            history.replaceState(null, null, newURL);
        }
        function updateSummary() {
            var d = {
                tanggal: $("#tanggal").val(),
                pelanggan_id: $('select[name=pelanggan_id]').val(),
                area_coverage: $('select[name=area_coverage]').val(),
                metode_bayar: $('select[name=metode_bayar]').val(),
                status_bayar: $('select[name=status_bayar]').val(),
                kirim_tagihan: $('select[name=kirim_tagihan]').val(),
                from_month: $('#from_month').val(),
                to_month: $('#to_month').val(),
            };
            $.get("{{ route('tagihans.summary') }}", d, function (res) {
                $('#paid-count').text(res.paid_count ?? 0);
                $('#paid-sum').text(res.paid_sum ?? 'Rp 0');
                $('#unpaid-count').text(res.unpaid_count ?? 0);
                $('#unpaid-sum').text(res.unpaid_sum ?? 'Rp 0');
                $('#waiting-count').text(res.waiting_count ?? 0);
                $('#waiting-sum').text(res.waiting_sum ?? 'Rp 0');
            });
        }
        updateSummary();

        $('#tanggal').change(function() {
            table.draw();
            replaceURLParams()
            updateSummary();
        })
        $('#from_month').change(function() {
            table.draw();
            replaceURLParams()
            updateSummary();
        })
        $('#to_month').change(function() {
            table.draw();
            replaceURLParams()
            updateSummary();
        })

        $('#pelanggan_id').change(function() {
            table.draw();
            replaceURLParams()
            updateSummary();
        })

        $('#area_coverage').change(function() {
            table.draw();
            replaceURLParams()
            updateSummary();
        })

        $('#metode_bayar').change(function() {
            table.draw();
            replaceURLParams()
            updateSummary();
        })
        $('#status_bayar').change(function() {
            table.draw();
            replaceURLParams()
            updateSummary();
        })
        $('#kirim_tagihan').change(function() {
            table.draw();
            replaceURLParams()
            updateSummary();
        })


        $('#checkAll').change(function() {
            var checkboxes = $('.checkbox');
            checkboxes.prop('checked', $(this).prop('checked'));
            updateSendWaButtonState();
        });

        $('#data-table tbody').on('change', '.checkbox', function() {
            var checkAll = $('#checkAll');
            var checkboxes = $('.checkbox');
            checkAll.prop('checked', checkboxes.length === checkboxes.filter(':checked').length);
            updateSendWaButtonState();
        });

        function updateSendWaButtonState() {
            var sendWaBtn = $('#sendWaBtn');
            var checkedCheckboxes = $('.checkbox:checked');
            sendWaBtn.prop('disabled', checkedCheckboxes.length === 0);
        }

        $('#sendWaBtn').click(function() {
            var checkedIds = $('.checkbox:checked').map(function() {
                return $(this).val();
            }).get();

            if (checkedIds.length === 0) {
                return;
            }

            if (!confirm('Apakah Anda yakin ingin mengirim tagihan WA untuk pelanggan yang dipilih?')) {
                return;
            }

            $.ajax({
                url: '{{ route('tagihans.sendWa') }}',
                method: 'POST',
                data: {
                    ids: checkedIds,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    alert(response.message);
                    table.draw();
                },
                error: function(xhr) {
                    if (xhr.status === 400 && xhr.responseJSON.message === 'Gateway WA tidak aktif.') {
                        alert('Gateway WA tidak aktif.');
                    } else {
                        alert('Terjadi kesalahan. Mohon coba lagi.');
                    }
                }
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).on('click', '.btn-validasi-tagihan', function() {
            let id = $(this).data('id');
            let noTagihan = $(this).data('no-tagihan');
            let namaPelanggan = $(this).data('nama-pelanggan');
            let nominal = $(this).data('nominal');

            Swal.fire({
                title: 'Validasi Tagihan',
                html: `Apakah Anda ingin validasi pembayaran tagihan?<br><br>
                       <strong>No Tagihan:</strong> ${noTagihan}<br>
                       <strong>Nama Pelanggan:</strong> ${namaPelanggan}<br>
                       <strong>Nominal:</strong> ${nominal}`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Validasi',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('validasiTagihan') }}",
                        method: 'POST',
                        data: {
                            _token: "{{ csrf_token() }}",
                            id: id
                        },
                        success: function(res) {
                            Swal.fire('Berhasil!', res.message, 'success');
                            $('#data-table').DataTable().ajax.reload(null, false);
                        },
                        error: function() {
                            Swal.fire('Gagal!', 'Terjadi kesalahan saat memvalidasi.', 'error');
                        }
                    });
                }
            });
        });
    </script>
@endpush
