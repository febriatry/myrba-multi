@extends('layouts.app')

@section('title', __('Topup'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Topup') }}</h3>
                    <p class="text-subtitle text-muted">
                        {{ __('Di bawah ini adalah daftar semua Topup.') }}
                    </p>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="/">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Topup') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <x-alert></x-alert>

            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive p-1">
                                <table class="table table-striped" id="data-table" width="100%">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>{{ __('No Topup') }}</th>
                                            <th>{{ __('Pelanggan') }}</th>
                                            <th>{{ __('Nominal') }}</th>
                                            <th>{{ __('Metode') }}</th>
                                            <th>{{ __('Bank/Channel') }}</th>
                                            <th>{{ __('Status') }}</th>
                                            <th>{{ __('Tanggal') }}</th>
                                            <th>{{ __('Aksi') }}</th>
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

    <div class="modal fade" id="confirmTopupModal" tabindex="-1" role="dialog" aria-labelledby="confirmTopupModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmTopupModalLabel">Konfirmasi Top Up</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Apakah Anda yakin ingin menyetujui top up ini? Saldo pelanggan akan bertambah dan transaksi akan
                    tercatat sebagai pemasukan.
                    <input type="hidden" id="topup_id_to_confirm">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-success" id="confirmTopupButton">Ya, Konfirmasi</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css"
        integrity="sha512-KfkfwYDsLkIlwQp6LFnl8zNdLGxu9YAA1QvwINks4PhcElQSvqcyVLLD9aMhXd13uQjoXtEKNosOWaZqXgel0g=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs5/dt-1.12.0/datatables.min.css" />
@endpush

@push('js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"
        integrity="sha512-894YE6QWD5I59HgZOGReFYm4dnWc1Qt5NtvYSaNcOP+u1T9qYdvdihz0PPSiiqn/+/3e7Jo4EaG7TubfWGUrMQ=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdn.jsdelivr.net/combine/npm/datatables.net@1.12.0,npm/datatables.net-bs5@1.12.0"></script>
    <script>
        $(document).ready(function() {
            var table = $('#data-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('topups.index') }}",
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    }, {
                        data: 'no_topup',
                        name: 'no_topup'
                    },
                    {
                        data: 'pelanggan',
                        name: 'pelanggan.nama'
                    },
                    {
                        data: 'nominal',
                        name: 'nominal'
                    },
                    {
                        data: 'metode',
                        name: 'metode'
                    },
                    {
                        data: 'bank',
                        name: 'bankAccount.bank.nama_bank',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'status',
                        name: 'status'
                    },
                    {
                        data: 'tanggal_topup',
                        name: 'tanggal_topup'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }
                ]
            });

            // Fungsi untuk membuka modal konfirmasi
            window.showConfirmModal = function(id) {
                $('#topup_id_to_confirm').val(id);
                $('#confirmTopupModal').modal('show');
            }

            // Aksi saat tombol konfirmasi di modal diklik
            $('#confirmTopupButton').on('click', function() {
                var topupId = $('#topup_id_to_confirm').val();
                var url = "{{ route('topups.approve') }}"; // URL ini sekarang sudah didefinisikan
                var button = $(this);

                button.prop('disabled', true).text('Memproses...');

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        id: topupId
                    },
                    success: function(response) {
                        $('#confirmTopupModal').modal('hide');
                        table.ajax.reload();
                        // Idealnya gunakan SweetAlert atau Toastr di sini
                        alert(response.message);
                    },
                    error: function(xhr) {
                        alert(xhr.responseJSON.message || 'Terjadi kesalahan.');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('Ya, Konfirmasi');
                    }
                });
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).on('click', '.btn-delete-topup', function() {
            let id = $(this).data('id');
            let noTopup = $(this).data('no-topup');

            Swal.fire({
                title: 'Konfirmasi Hapus',
                html: `Anda yakin ingin menghapus data top up dengan nomor:<br><strong>${noTopup}</strong>?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Submit formulir yang sesuai dengan ID
                    $('#delete-form-' + id).submit();
                }
            });
        });
        $(document).on('click', '.btn-konfirmasi-topup', function() {
            let id = $(this).data('id');
            let noTopup = $(this).data('no-topup');
            let pelanggan = $(this).data('pelanggan');
            let nominal = $(this).data('nominal');

            Swal.fire({
                title: 'Konfirmasi Top Up',
                html: `Harap periksa detail top up berikut sebelum melanjutkan:<br><br>
                       <strong>No. Top Up:</strong> ${noTopup}<br>
                       <strong>Pelanggan:</strong> ${pelanggan}<br>
                       <strong>Nominal:</strong> ${nominal}`,
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Lanjutkan',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Jika dikonfirmasi, buka modal approval yang sudah ada
                    var approveModal = new bootstrap.Modal(document.getElementById('approveModal-' + id));
                    approveModal.show();
                }
            });
        });
    </script>
@endpush
