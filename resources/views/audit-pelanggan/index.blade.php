@extends('layouts.app')

@section('title', __('Audit Pelanggan'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Audit Pelanggan') }}</h3>
                    <p class="text-subtitle text-muted">
                        {{ __('Rekonsiliasi pelanggan aplikasi vs PPP Secret/Active di Mikrotik untuk mendeteksi anomali statistik.') }}
                    </p>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Audit Pelanggan') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <x-alert></x-alert>

            <div class="card mb-3">
                <div class="card-body">
                    <form method="get" action="{{ route('audit-pelanggan.index') }}" class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Router') }}</label>
                            <select name="router_id" class="form-control">
                                <option value="">{{ __('Semua Router') }}</option>
                                @foreach ($routers as $r)
                                    <option value="{{ $r->id }}" @selected((string) $routerId === (string) $r->id)>{{ $r->identitas_router }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button class="btn btn-primary w-100">{{ __('Terapkan') }}</button>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            @can('audit pelanggan export')
                                <a class="btn btn-success w-100" href="{{ route('audit-pelanggan.export.pdf', ['router_id' => $routerId]) }}">{{ __('Export PDF') }}</a>
                            @endcan
                        </div>
                    </form>
                </div>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="text-muted">{{ __('Pelanggan Total') }}</div>
                            <div class="h4">{{ (int) $summary['pelanggan_total'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="text-muted">{{ __('Pelanggan Aktif (DB)') }}</div>
                            <div class="h4">{{ (int) $summary['pelanggan_aktif'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="text-muted">{{ __('PPP Active (Mikrotik)') }}</div>
                            <div class="h4">{{ (int) $summary['ppp_active_total'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="text-muted">{{ __('PPP Secret Total (Mikrotik)') }}</div>
                            <div class="h4">{{ (int) $summary['ppp_secret_total'] }}</div>
                            <div class="text-muted">{{ __('PPP Non Active: :n', ['n' => (int) $summary['ppp_non_active_total']]) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="alert alert-info">
                <div><strong>{{ __('Panduan') }}</strong></div>
                <div>{{ __('PPP Secret Total adalah jumlah akun PPPoE di router (bukan jumlah pelanggan aktif DB). Selisih biasanya berasal dari orphan/missing/mismatch.') }}</div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <h4>{{ __('Anomali') }}</h4>
                </div>
                <div class="card-body">
                    <div class="accordion" id="auditAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="h1">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#c1">
                                    {{ __('1) PPP Secret tanpa Pelanggan (Orphan)') }} ({{ count($anomali['orphan_secrets']) }})
                                </button>
                            </h2>
                            <div id="c1" class="accordion-collapse collapse show" data-bs-parent="#auditAccordion">
                                <div class="accordion-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>{{ __('Router') }}</th>
                                                    <th>{{ __('User') }}</th>
                                                    <th>{{ __('Disabled') }}</th>
                                                    <th>{{ __('Profile') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($anomali['orphan_secrets'] as $r)
                                                    <tr>
                                                        <td>{{ $r['router_name'] }}</td>
                                                        <td>{{ $r['name'] }}</td>
                                                        <td>{{ $r['disabled'] }}</td>
                                                        <td>{{ $r['profile'] }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="4" class="text-center">{{ __('Tidak ada.') }}</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="text-muted">{{ __('Maks 200 baris ditampilkan.') }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="h2">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c2">
                                    {{ __('2) Pelanggan PPOE tanpa Secret (Missing Secret)') }} ({{ count($anomali['missing_secrets']) }})
                                </button>
                            </h2>
                            <div id="c2" class="accordion-collapse collapse" data-bs-parent="#auditAccordion">
                                <div class="accordion-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>{{ __('Nama') }}</th>
                                                    <th>{{ __('No Layanan') }}</th>
                                                    <th>{{ __('Status') }}</th>
                                                    <th>{{ __('Router') }}</th>
                                                    <th>{{ __('User PPPoE') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($anomali['missing_secrets'] as $r)
                                                    <tr>
                                                        <td>{{ $r['pelanggan_id'] }}</td>
                                                        <td>{{ $r['nama'] }}</td>
                                                        <td>{{ formatNoLayananTenant($r['no_layanan'], (int) (auth()->user()->tenant_id ?? 0)) }}</td>
                                                        <td>{{ $r['status'] }}</td>
                                                        <td>{{ $r['router_id'] }}</td>
                                                        <td>{{ $r['user_pppoe'] }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="6" class="text-center">{{ __('Tidak ada.') }}</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="text-muted">{{ __('Maks 200 baris ditampilkan.') }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="h3">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c3">
                                    {{ __('3) PPP Active Mismatch') }} ({{ count($anomali['active_mismatch']) }})
                                </button>
                            </h2>
                            <div id="c3" class="accordion-collapse collapse" data-bs-parent="#auditAccordion">
                                <div class="accordion-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>{{ __('Type') }}</th>
                                                    <th>{{ __('Router') }}</th>
                                                    <th>{{ __('User') }}</th>
                                                    <th>{{ __('Pelanggan') }}</th>
                                                    <th>{{ __('Status') }}</th>
                                                    <th>IP</th>
                                                    <th>Uptime</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($anomali['active_mismatch'] as $r)
                                                    <tr>
                                                        <td>{{ $r['type'] }}</td>
                                                        <td>{{ $r['router_name'] }}</td>
                                                        <td>{{ $r['user_pppoe'] }}</td>
                                                        <td>{{ $r['pelanggan_id'] ?? '-' }} {{ $r['nama'] ?? '' }}</td>
                                                        <td>{{ $r['status'] ?? '-' }}</td>
                                                        <td>{{ $r['address'] ?? '-' }}</td>
                                                        <td>{{ $r['uptime'] ?? '-' }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="7" class="text-center">{{ __('Tidak ada.') }}</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="text-muted">{{ __('Maks 200 baris ditampilkan.') }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="h4">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c4">
                                    {{ __('4) Duplikasi user_pppoe di Database') }} ({{ count($anomali['duplicates']) }})
                                </button>
                            </h2>
                            <div id="c4" class="accordion-collapse collapse" data-bs-parent="#auditAccordion">
                                <div class="accordion-body">
                                    @forelse ($anomali['duplicates'] as $d)
                                        <div class="mb-3">
                                            <div><strong>Router ID:</strong> {{ $d['router_id'] }} | <strong>User:</strong> {{ $d['user_pppoe'] }}</div>
                                            <div class="table-responsive">
                                                <table class="table table-bordered table-sm">
                                                    <thead>
                                                        <tr>
                                                            <th>ID</th>
                                                            <th>{{ __('Nama') }}</th>
                                                            <th>{{ __('No Layanan') }}</th>
                                                            <th>{{ __('Status') }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($d['rows'] as $r)
                                                            <tr>
                                                                <td>{{ $r->id }}</td>
                                                                <td>{{ $r->nama }}</td>
                                                                <td>{{ formatNoLayananTenant($r->no_layanan, (int) (auth()->user()->tenant_id ?? 0)) }}</td>
                                                                <td>{{ $r->status_berlangganan }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="text-center">{{ __('Tidak ada.') }}</div>
                                    @endforelse
                                    <div class="text-muted">{{ __('Maks 200 grup ditampilkan.') }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="h5">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c5">
                                    {{ __('5) Router Error') }} ({{ count($routerErrors) }})
                                </button>
                            </h2>
                            <div id="c5" class="accordion-collapse collapse" data-bs-parent="#auditAccordion">
                                <div class="accordion-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Router</th>
                                                    <th>Error</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($routerErrors as $e)
                                                    <tr>
                                                        <td>{{ $e['router_name'] }}</td>
                                                        <td>{{ $e['error'] }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="2" class="text-center">{{ __('Tidak ada.') }}</td>
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
        </section>
    </div>
@endsection
