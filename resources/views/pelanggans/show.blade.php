@extends('layouts.app')

@section('title', __('Detail Pelanggan'))

@section('content')
    <div class="page-body">
        <div class="container-fluid">
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-6">
                        <h3>{{ __('Detail Pelanggan') }}</h3>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('pelanggans.index') }}">{{ __('Pelanggan') }}</a>
                            </li>
                            <li class="breadcrumb-item active">{{ __('Detail') }}</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-4 col-md-5">
                    <div class="card">
                        <div class="card-body">
                            <div class="text-center">
                                <img src="{{ $pelanggan->photo_ktp ? asset('storage/uploads/photo_ktps/' . $pelanggan->photo_ktp) : 'https://via.placeholder.com/350?text=No+Image+Avaiable' }}"
                                    alt="Photo KTP" class="img-fluid rounded" style="max-height: 300px; object-fit: cover;">
                                <h4 class="mt-3 mb-1">{{ $pelanggan->nama }}</h4>
                                <p class="text-muted">No. Layanan: {{ $pelanggan->no_layanan }}</p>
                                <span
                                    class="badge {{ $pelanggan->status_berlangganan == 'Aktif' ? 'bg-success' : 'bg-danger' }}">
                                    {{ $pelanggan->status_berlangganan }}
                                </span>
                            </div>
                            <hr>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between">
                                    <strong>Email:</strong>
                                    <span>{{ $pelanggan->email }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <strong>No. WhatsApp:</strong>
                                    <span>{{ $pelanggan->no_wa }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <strong>Tanggal Daftar:</strong>
                                    <span>{{ \Carbon\Carbon::parse($pelanggan->tanggal_daftar)->format('d F Y') }}</span>
                                </li>
                            </ul>
                        </div>
                        {{-- REVISI 1: Tombol Kembali dipindahkan ke sini --}}
                        <div class="card-footer text-center">
                            @can('pelanggan return device')
                                @if (($pelanggan->status_berlangganan ?? '') === 'Non Aktif')
                                    <a href="{{ route('pelanggans.return-device.create', (int) $pelanggan->id) }}" class="btn btn-primary w-100 mb-2">{{ __('Return Perangkat') }}</a>
                                @endif
                            @endcan
                            <a href="{{ route('pelanggans.index') }}"
                                class="btn btn-secondary w-100">{{ __('Kembali') }}</a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8 col-md-7">
                    <div class="card">
                        <div class="card-header">
                            <ul class="nav nav-tabs card-header-tabs" id="detailTab" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="layanan-tab" data-bs-toggle="tab" href="#layanan"
                                        role="tab" aria-controls="layanan" aria-selected="true">Data Layanan</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="keuangan-tab" data-bs-toggle="tab" href="#keuangan"
                                        role="tab" aria-controls="keuangan" aria-selected="false">Keuangan &
                                        Referral</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="pribadi-tab" data-bs-toggle="tab" href="#pribadi" role="tab"
                                        aria-controls="pribadi" aria-selected="false">Data Pribadi</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="return-tab" data-bs-toggle="tab" href="#return" role="tab"
                                        aria-controls="return" aria-selected="false">Return Perangkat</a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            {{-- REVISI 2: Menambahkan class mt-4 untuk memberi jarak --}}
                            <div class="tab-content mt-4" id="detailTabContent">
                                <div class="tab-pane fade show active" id="layanan" role="tabpanel"
                                    aria-labelledby="layanan-tab">
                                    <h5 class="mb-3">Data Layanan & Jaringan</h5>
                                    <table class="table table-hover table-striped">
                                        <tr>
                                            <td class="fw-bold" width="40%">Paket Layanan</td>
                                            <td>{{ $pelanggan->nama_layanan }} ({{ rupiah($pelanggan->harga) }})</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Router</td>
                                            <td>{{ $pelanggan->identitas_router }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Mode User</td>
                                            <td>{{ $pelanggan->mode_user }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">User PPPOE</td>
                                            <td>{{ $pelanggan->user_pppoe ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">User Static</td>
                                            <td>{{ $pelanggan->user_static ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Area Coverage</td>
                                            <td>{{ $pelanggan->kode_area }} - {{ $pelanggan->nama_area }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">ODC</td>
                                            <td>{{ $pelanggan->kode_odc }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">ODP</td>
                                            <td>{{ $pelanggan->kode_odp }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">No Port ODP</td>
                                            <td>{{ $pelanggan->no_port_odp }}</td>
                                        </tr>
                                    </table>
                                </div>

                                <div class="tab-pane fade" id="keuangan" role="tabpanel" aria-labelledby="keuangan-tab">
                                    <h5 class="mb-3">Informasi Keuangan</h5>
                                    <table class="table table-hover table-striped">
                                        <tr>
                                            <td class="fw-bold" width="40%">Jatuh Tempo</td>
                                            <td>Setiap tanggal {{ $pelanggan->jatuh_tempo }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">PPN 11%</td>
                                            <td>{{ $pelanggan->ppn }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Auto Isolir</td>
                                            <td>{{ $pelanggan->auto_isolir }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Kirim Tagihan WA</td>
                                            <td>{{ $pelanggan->kirim_tagihan_wa }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Saldo Saat Ini</td>
                                            <td>{{ rupiah($pelanggan->balance) }}</td>
                                        </tr>
                                    </table>

                                    <h5 class="mb-3 mt-4">Informasi Referral</h5>
                                    <table class="table table-hover table-striped">
                                        <tr>
                                            <td class="fw-bold" width="40%">Kode Referral Anda</td>
                                            <td><strong>{{ $pelanggan->no_layanan }}</strong></td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Total Pendapatan Referral</td>
                                            <td>{{ rupiah($totalPendapatanReferral) }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Total Pelanggan Direferensikan</td>
                                            <td>{{ $jumlahPenggunaReferral }} Orang</td>
                                        </tr>
                                    </table>
                                </div>

                                <div class="tab-pane fade" id="pribadi" role="tabpanel" aria-labelledby="pribadi-tab">
                                    <h5 class="mb-3">Data Pribadi</h5>
                                    <table class="table table-hover table-striped">
                                        <tr>
                                            <td class="fw-bold" width="40%">No. KTP</td>
                                            <td>{{ $pelanggan->no_ktp }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Alamat Lengkap</td>
                                            <td>{{ $pelanggan->alamat }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Latitude</td>
                                            <td>{{ $pelanggan->latitude }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Longitude</td>
                                            <td>{{ $pelanggan->longitude }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Dibuat pada</td>
                                            <td>{{ \Carbon\Carbon::parse($pelanggan->created_at)->translatedFormat('l, d F Y H:i') }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">Diperbarui pada</td>
                                            <td>{{ \Carbon\Carbon::parse($pelanggan->updated_at)->translatedFormat('l, d F Y H:i') }}
                                            </td>
                                        </tr>
                                    </table>
                                </div>

                                <div class="tab-pane fade" id="return" role="tabpanel" aria-labelledby="return-tab">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="mb-0">Riwayat Return Perangkat</h5>
                                        @can('pelanggan return device')
                                            @if (($pelanggan->status_berlangganan ?? '') === 'Non Aktif')
                                                <a href="{{ route('pelanggans.return-device.create', (int) $pelanggan->id) }}" class="btn btn-primary">{{ __('Return Perangkat') }}</a>
                                            @endif
                                        @endcan
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Waktu</th>
                                                    <th>Status</th>
                                                    <th>Items</th>
                                                    <th>Transaksi IN</th>
                                                    <th>Dibuat Oleh</th>
                                                    <th>Catatan</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse (($deviceReturns ?? []) as $r)
                                                    @php
                                                        $items = [];
                                                        if (!empty($r->items)) {
                                                            $decoded = json_decode($r->items, true);
                                                            if (is_array($decoded)) {
                                                                $items = $decoded;
                                                            }
                                                        }
                                                    @endphp
                                                    <tr>
                                                        <td>{{ (int) $r->id }}</td>
                                                        <td>{{ \Carbon\Carbon::parse($r->created_at)->format('d-m-Y H:i') }}</td>
                                                        <td>
                                                            {{ $r->status_return }}
                                                            @if (($r->is_cancelled ?? 'No') === 'Yes')
                                                                <div class="text-muted">{{ __('Dibatalkan') }}</div>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if (empty($items))
                                                                -
                                                            @else
                                                                @foreach ($items as $it)
                                                                    {{ $it['nama_barang'] ?? '-' }} ({{ (int) ($it['qty'] ?? 0) }}) - {{ $it['condition'] ?? '-' }}<br>
                                                                @endforeach
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if (!empty($r->transaksi_in_id))
                                                                <a href="{{ route('transaksi-stock-in.show', (int) $r->transaksi_in_id) }}">{{ $r->transaksi_kode ?? ('#' . (int) $r->transaksi_in_id) }}</a>
                                                            @else
                                                                -
                                                            @endif
                                                        </td>
                                                        <td>{{ $r->created_by_name ?? '-' }}</td>
                                                        <td>{{ $r->notes ?? '-' }}</td>
                                                        <td>
                                                            @can('pelanggan return device view')
                                                                <a class="btn btn-sm btn-outline-primary" href="{{ route('pelanggans.return-device.show', [(int) $pelanggan->id, (int) $r->id]) }}">{{ __('Lihat Form Return') }}</a>
                                                            @endcan
                                                            @can('pelanggan return device cancel')
                                                                @if (($r->is_cancelled ?? 'No') !== 'Yes')
                                                                    <a class="btn btn-sm btn-outline-danger" href="{{ route('pelanggans.return-device.show', [(int) $pelanggan->id, (int) $r->id]) }}">{{ __('Batalkan') }}</a>
                                                                @endif
                                                            @endcan
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="8" class="text-center">Belum ada riwayat return.</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
