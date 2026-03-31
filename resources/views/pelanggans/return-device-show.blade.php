@extends('layouts.app')

@section('title', __('Detail Return Perangkat'))

@section('content')
    <div class="page-body">
        <div class="container-fluid">
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-6">
                        <h3>{{ __('Detail Return Perangkat') }}</h3>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('pelanggans.index') }}">{{ __('Pelanggan') }}</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('pelanggans.show', (int) $pelanggan->id) }}">{{ __('Detail') }}</a></li>
                            <li class="breadcrumb-item active">{{ __('Detail Return') }}</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid">
            <x-alert></x-alert>
            <div class="card">
                <div class="card-body">
                    <div class="mb-3">
                        <div><strong>{{ __('Pelanggan') }}</strong>: {{ $pelanggan->nama }} ({{ formatNoLayananTenant($pelanggan->no_layanan, (int) (auth()->user()->tenant_id ?? 0)) }})</div>
                        <div><strong>{{ __('Return ID') }}</strong>: {{ (int) $row->id }}</div>
                        <div><strong>{{ __('Status Return') }}</strong>: {{ $row->status_return }}</div>
                        <div><strong>{{ __('Dibuat') }}</strong>: {{ \Carbon\Carbon::parse($row->created_at)->format('d-m-Y H:i') }} {{ $createdByName ? 'oleh ' . $createdByName : '' }}</div>
                        @if (($row->is_cancelled ?? 'No') === 'Yes')
                            <div><strong>{{ __('Dibatalkan') }}</strong>: {{ \Carbon\Carbon::parse($row->cancelled_at)->format('d-m-Y H:i') }} {{ $cancelledByName ? 'oleh ' . $cancelledByName : '' }}</div>
                            <div><strong>{{ __('Alasan') }}</strong>: {{ $row->cancel_reason ?? '-' }}</div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <div><strong>{{ __('Transaksi IN') }}</strong>:
                            @if (!empty($row->transaksi_in_id))
                                <a href="{{ route('transaksi-stock-in.show', (int) $row->transaksi_in_id) }}">{{ $transaksiKode ?? ('#' . (int) $row->transaksi_in_id) }}</a>
                            @else
                                -
                            @endif
                        </div>
                        <div><strong>{{ __('Catatan') }}</strong>: {{ $row->notes ?? '-' }}</div>
                    </div>

                    <div class="table-responsive mb-3">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>{{ __('Barang') }}</th>
                                    <th style="width:120px">{{ __('Qty') }}</th>
                                    <th style="width:120px">{{ __('Kondisi') }}</th>
                                    <th style="width:160px">{{ __('Pemilik') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($items as $it)
                                    <tr>
                                        <td>{{ $it['nama_barang'] ?? '-' }}</td>
                                        <td class="text-center">{{ (int) ($it['qty'] ?? 0) }}</td>
                                        <td class="text-center">{{ $it['condition'] ?? '-' }}</td>
                                        <td class="text-center">{{ ($it['owner_type'] ?? 'office') === 'investor' ? 'Investor' : 'Kantor' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center">{{ __('Tidak ada item.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="{{ route('pelanggans.show', (int) $pelanggan->id) }}" class="btn btn-secondary">{{ __('Kembali') }}</a>
                        @can('pelanggan return device cancel')
                            @if (($row->is_cancelled ?? 'No') !== 'Yes')
                                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#cancelModal">{{ __('Batalkan Return') }}</button>
                            @endif
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="cancelModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="{{ route('pelanggans.return-device.cancel', [(int) $pelanggan->id, (int) $row->id]) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('Batalkan Return') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-label">{{ __('Alasan Pembatalan') }}</label>
                            <input type="text" name="cancel_reason" class="form-control" maxlength="255" required>
                        </div>
                        <div class="text-muted">{{ __('Pembatalan maksimal 24 jam dan hanya jika stok masih mencukupi untuk dibalikkan.') }}</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Tutup') }}</button>
                        <button class="btn btn-danger">{{ __('Batalkan') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
