@extends('layouts.guest')

@section('title', __('Cek Tagihan'))

@section('content')
    <div class="container py-4">
        <h3>{{ __('Cek Tagihan') }}</h3>
        <p class="text-muted">{{ __('Masukkan Tenant ID dan No Tagihan atau No Layanan untuk melihat status tagihan.') }}</p>

        <x-alert></x-alert>

        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('public.tagihan') }}" class="row g-2 align-items-end">
                    <div class="col-12 col-md-3">
                        <label class="form-label">{{ __('Tenant ID') }}</label>
                        <input type="number" name="tid" class="form-control" value="{{ $tenantId ?? '' }}" required>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">{{ __('No Tagihan') }}</label>
                        <input type="text" name="no_tagihan" class="form-control" value="{{ $noTagihan ?? '' }}">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">{{ __('No Layanan') }}</label>
                        <input type="text" name="no_layanan" class="form-control" value="{{ $noLayanan ?? '' }}">
                    </div>
                    <div class="col-12 col-md-1 d-grid">
                        <button class="btn btn-primary" type="submit">{{ __('Cari') }}</button>
                    </div>
                    <div class="col-12">
                        <small class="text-muted">{{ __('Isi salah satu: No Tagihan atau No Layanan') }}</small>
                    </div>
                </form>
            </div>
        </div>

        @if (!empty($rows))
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>{{ __('No Tagihan') }}</th>
                                    <th>{{ __('Periode') }}</th>
                                    <th>{{ __('Pelanggan') }}</th>
                                    <th>{{ __('No Layanan') }}</th>
                                    <th>{{ __('Paket') }}</th>
                                    <th class="text-end">{{ __('Total') }}</th>
                                    <th>{{ __('Status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rows as $r)
                                    <tr>
                                        <td>{{ $r->no_tagihan }}</td>
                                        <td>{{ $r->periode }}</td>
                                        <td>{{ $r->pelanggan_nama }}</td>
                                        <td>{{ formatNoLayananTenant($r->no_layanan, $tenantId) }}</td>
                                        <td>{{ $r->paket_layanan_nama ?? '-' }}</td>
                                        <td class="text-end">{{ rupiah((int) ($r->total_bayar ?? 0)) }}</td>
                                        <td>{{ $r->status_bayar }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">{{ __('Tidak ada data') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
