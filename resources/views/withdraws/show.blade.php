@extends('layouts.app')

@section('title', __('Detail Withdraw'))

@section('content')
    <div class="page-body">
        <div class="container-fluid">
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-6">
                        <h3>{{ __('Detail Withdraw') }}</h3>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('withdraws.index') }}">{{ __('Withdraw') }}</a></li>
                            <li class="breadcrumb-item active">{{ __('Detail') }}</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-7">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped">
                                    <tr>
                                        <td class="fw-bold">{{ __('Pelanggan') }}</td>
                                        <td>: {{ $withdraw->pelanggan->nama }} ({{ $withdraw->pelanggan->no_layanan }})</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">{{ __('Nominal Withdraw') }}</td>
                                        <td>: {{ rupiah($withdraw->nominal_wd) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">{{ __('Tanggal Pengajuan') }}</td>
                                        <td>: {{ $withdraw->tanggal_wd->translatedFormat('l, d F Y H:i') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">{{ __('Status') }}</td>
                                        <td>:
                                            @php $colors = ['Pending' => 'warning', 'Approved' => 'success', 'Rejected' => 'danger']; @endphp
                                            <span
                                                class="badge bg-{{ $colors[$withdraw->status] }}">{{ $withdraw->status }}</span>
                                        </td>
                                    </tr>
                                    @if ($withdraw->status != 'Pending')
                                        <tr>
                                            <td class="fw-bold">{{ __('Diproses oleh') }}</td>
                                            <td>: {{ $withdraw->approver->name ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">{{ __('Tanggal Proses') }}</td>
                                            <td>: {{ $withdraw->updated_at->translatedFormat('l, d F Y H:i') }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">{{ __('Catatan') }}</td>
                                            <td>: {{ $withdraw->catatan_user_approved }}</td>
                                        </tr>
                                    @endif
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                @can('withdraw approval')
                    @if ($withdraw->status == 'Pending')
                        <div class="col-md-5">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Formulir Persetujuan</h5>
                                </div>
                                <div class="card-body">
                                    <form action="{{ route('withdraws.approve', $withdraw->id) }}" method="POST">
                                        @csrf
                                        <div class="form-group">
                                            <label for="catatan">Catatan <span class="text-danger">*</span></label>
                                            <textarea name="catatan" id="catatan" class="form-control @error('catatan') is-invalid @enderror" required>{{ old('catatan') }}</textarea>
                                            @error('catatan')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="mt-3 d-flex justify-content-end">
                                            <button type="submit" name="action" value="reject"
                                                class="btn btn-danger me-2">Tolak</button>
                                            <button type="submit" name="action" value="approve"
                                                class="btn btn-success">Setujui</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif
                @endcan

            </div>
            <a href="{{ route('withdraws.index') }}" class="btn btn-secondary">{{ __('Kembali') }}</a>
        </div>
    </div>
@endsection
