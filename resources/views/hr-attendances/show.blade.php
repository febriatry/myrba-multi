@extends('layouts.app')

@section('title', __('Detail Absensi'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Detail Absensi') }}</h3>
                    <p class="text-subtitle text-muted">{{ $row->user_name }} | {{ $row->date }}</p>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="/dashboard">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('hr-attendances.index', ['date' => $row->date]) }}">{{ __('Absensi Harian') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Detail') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <x-alert></x-alert>

            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-2"><span class="fw-bold">{{ __('Karyawan') }}:</span> {{ $row->user_name }} ({{ $row->user_email }})</div>
                            <div class="mb-2"><span class="fw-bold">{{ __('Jabatan') }}:</span> {{ $row->jabatan_name ?? '-' }}</div>
                            <div class="mb-2"><span class="fw-bold">{{ __('Tanggal') }}:</span> {{ $row->date }}</div>
                            <div class="mb-2"><span class="fw-bold">{{ __('Jenis') }}:</span> {{ $row->work_type }}</div>
                            <div class="mb-2"><span class="fw-bold">{{ __('Status') }}:</span> {{ $row->status }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-2"><span class="fw-bold">{{ __('Clock In') }}:</span> {{ $row->clock_in_at ?? '-' }}</div>
                            <div class="mb-2"><span class="fw-bold">{{ __('Clock Out') }}:</span> {{ $row->clock_out_at ?? '-' }}</div>
                            <div class="mb-2"><span class="fw-bold">{{ __('Scheduled') }}:</span> {{ $row->scheduled_start_at ?? '-' }} - {{ $row->scheduled_end_at ?? '-' }}</div>
                            <div class="mb-2"><span class="fw-bold">{{ __('Telat') }}:</span> {{ $row->late_minutes }} m</div>
                            <div class="mb-2"><span class="fw-bold">{{ __('Kerja') }}:</span> {{ $row->work_minutes }} m</div>
                            <div class="mb-2"><span class="fw-bold">{{ __('Lembur') }}:</span> {{ $row->overtime_minutes }} m</div>
                            <div class="mb-2"><span class="fw-bold">{{ __('ACC Lembur') }}:</span> {{ (int) ($row->overtime_approved_minutes ?? 0) }} m ({{ $row->overtime_review_status ?? '-' }})</div>
                            <div class="mb-2"><span class="fw-bold">{{ __('Kurang Jam') }}:</span> {{ $row->undertime_minutes }} m</div>
                        </div>
                    </div>
                    @if (!empty($row->notes))
                        <div class="mt-2"><span class="fw-bold">{{ __('Catatan') }}:</span> {{ $row->notes }}</div>
                    @endif
                </div>
            </div>

            @can('attendance manage')
                <div class="card mt-3">
                    <div class="card-header">
                        <div class="fw-bold">{{ __('Validasi Absensi') }}</div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-2">
                                    <span class="fw-bold">{{ __('Review Status') }}:</span>
                                    {{ $row->review_status ?? '-' }}
                                </div>
                                <div class="mb-2">
                                    <span class="fw-bold">{{ __('Reviewed At') }}:</span>
                                    {{ $row->reviewed_at ?? '-' }}
                                </div>
                            </div>
                            <div class="col-md-6">
                                @if (!empty($sites) && !empty($siteCheck['site']))
                                    <div class="mb-2">
                                        <span class="fw-bold">{{ __('Titik Absensi') }}:</span>
                                        {{ $siteCheck['site']->name ?? '-' }}
                                    </div>
                                    <div class="mb-2">
                                        <span class="fw-bold">{{ __('Jarak') }}:</span>
                                        {{ $siteCheck['distance_m'] ?? '-' }} m (radius {{ $siteCheck['radius_m'] ?? '-' }} m)
                                    </div>
                                    <div class="mb-2">
                                        <span class="fw-bold">{{ __('Valid Lokasi') }}:</span>
                                        {{ !empty($siteCheck['ok']) ? 'Yes' : 'No' }}
                                    </div>
                                @elseif (!empty($sites))
                                    <div class="text-muted">{{ __('Lokasi clock-in belum diisi, atau tidak ada titik yang cocok.') }}</div>
                                @else
                                    <div class="text-muted">{{ __('Titik absensi belum diset. Set di menu Titik Absensi.') }}</div>
                                @endif
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-3">
                            @if (($row->review_status ?? '') !== 'approved')
                                <form method="POST" action="{{ route('hr-attendances.approve', $row->id) }}">
                                    @csrf
                                    <button class="btn btn-success" onclick="return confirm('Validasi absensi ini?')">{{ __('Approve') }}</button>
                                </form>
                            @endif
                            @if (($row->review_status ?? '') !== 'rejected')
                                <form method="POST" action="{{ route('hr-attendances.reject', $row->id) }}">
                                    @csrf
                                    <button class="btn btn-outline-danger" onclick="return confirm('Tolak absensi ini?')">{{ __('Reject') }}</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endcan

            @can('attendance manage')
                @if (($row->review_status ?? '') === 'approved' && ((int) ($row->overtime_minutes ?? 0)) > 0 && (($row->overtime_review_status ?? '') === 'pending'))
                    <div class="alert alert-warning mt-3">
                        {{ __('Lembur masih pending ACC. Payroll tidak akan menghitung lembur ini sampai di-ACC.') }}
                        <a class="ms-2" href="{{ route('hr-overtime-approvals.index', ['date' => $row->date]) }}">{{ __('Buka ACC Lembur') }}</a>
                    </div>
                @endif
            @endcan

            @can('attendance manage')
                @if (($row->review_status ?? '') === 'approved' && ((int) ($row->overtime_minutes ?? 0)) > 0)
                    <div class="card mt-3">
                        <div class="card-header">
                            <div class="fw-bold">{{ __('ACC Lembur') }}</div>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <span class="fw-bold">{{ __('Status') }}:</span>
                                {{ $row->overtime_review_status ?? '-' }}
                            </div>
                            <div class="mb-2">
                                <span class="fw-bold">{{ __('Lembur (hitung)') }}:</span>
                                {{ (int) ($row->overtime_minutes ?? 0) }} m
                            </div>
                            <div class="mb-3">
                                <span class="fw-bold">{{ __('ACC (menit)') }}:</span>
                                {{ (int) ($row->overtime_approved_minutes ?? 0) }} m
                            </div>

                            <div class="d-flex gap-2 align-items-center flex-wrap">
                                <form method="POST" action="{{ route('hr-overtime-approvals.approve', $row->id) }}" class="d-flex gap-2 align-items-center">
                                    @csrf
                                    <input type="number" name="approved_minutes" class="form-control" style="width:160px" min="0" max="{{ (int) ($row->overtime_minutes ?? 0) }}" value="{{ (int) (($row->overtime_review_status ?? '') === 'approved' ? ($row->overtime_approved_minutes ?? 0) : ($row->overtime_minutes ?? 0)) }}">
                                    <input type="text" name="note" class="form-control" style="width:260px" placeholder="{{ __('Catatan') }}" value="{{ (string) ($row->overtime_review_note ?? '') }}">
                                    <button class="btn btn-success" onclick="return confirm('ACC lembur untuk absensi ini?')">{{ __('ACC') }}</button>
                                </form>
                                <form method="POST" action="{{ route('hr-overtime-approvals.reject', $row->id) }}">
                                    @csrf
                                    <input type="hidden" name="note" value="{{ (string) ($row->overtime_review_note ?? '') }}">
                                    <button class="btn btn-outline-danger" onclick="return confirm('Tolak lembur untuk absensi ini?')">{{ __('Tolak') }}</button>
                                </form>
                                <a class="btn btn-outline-secondary" href="{{ route('hr-overtime-approvals.index', ['date' => $row->date]) }}">{{ __('Daftar ACC Lembur') }}</a>
                            </div>
                        </div>
                    </div>
                @endif
            @endcan

            <div class="card mt-3">
                <div class="card-header">
                    <div class="fw-bold">{{ __('Tracking (maks 2000 titik)') }}</div>
                </div>
                <div class="card-body">
                    <div class="card mb-3">
                        <div class="card-header">
                            <div class="fw-bold">{{ __('Catatan Karyawan') }}</div>
                        </div>
                        <div class="card-body">
                            @can('attendance manage')
                                <form class="row g-2 mb-3" method="POST" action="{{ route('hr-attendances.notes.store', $row->id) }}">
                                    @csrf
                                    <div class="col-md-3">
                                        <input type="datetime-local" name="noted_at" class="form-control" value="{{ old('noted_at') }}">
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" name="note" class="form-control" placeholder="Contoh: izin sebentar urusan pribadi" value="{{ old('note') }}" required>
                                    </div>
                                    <div class="col-md-2 d-grid">
                                        <button class="btn btn-primary" type="submit">{{ __('Tambah') }}</button>
                                    </div>
                                </form>
                            @endcan

                            <div class="table-responsive">
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Waktu') }}</th>
                                            <th>{{ __('Catatan') }}</th>
                                            @can('attendance manage')
                                                <th>{{ __('Action') }}</th>
                                            @endcan
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($notes as $n)
                                            <tr>
                                                <td>{{ $n->noted_at }}</td>
                                                <td>{{ $n->note }}</td>
                                                @can('attendance manage')
                                                    <td>
                                                        <form method="POST" action="{{ route('hr-attendances.notes.destroy', [$row->id, $n->id]) }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus catatan?')">{{ __('Hapus') }}</button>
                                                        </form>
                                                    </td>
                                                @endcan
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="@can('attendance manage') 3 @else 2 @endcan" class="text-center">{{ __('Belum ada catatan') }}</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    @can('attendance manage')
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <form class="d-flex gap-2" method="POST" action="{{ route('hr-attendances.tracks.import', $row->id) }}" enctype="multipart/form-data">
                                    @csrf
                                    <input type="file" name="file" class="form-control" required>
                                    <button class="btn btn-primary" type="submit">{{ __('Import CSV') }}</button>
                                    <a class="btn btn-outline-secondary" href="{{ route('hr-attendances.tracks.sample') }}">{{ __('Download Sample') }}</a>
                                </form>
                            </div>
                            <div class="col-md-4 d-flex justify-content-end">
                                <form method="POST" action="{{ route('hr-attendances.tracks.clear', $row->id) }}">
                                    @csrf
                                    <button class="btn btn-outline-danger" onclick="return confirm('Hapus semua tracking?')">{{ __('Clear Tracking') }}</button>
                                </form>
                            </div>
                            <div class="col-12 mt-2 text-muted">
                                {{ __('Format CSV: tracked_at,lat,lng,accuracy,speed,bearing,is_mock. Header opsional. tracked_at format: YYYY-MM-DD HH:MM:SS') }}
                            </div>
                        </div>
                    @endcan

                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="fw-bold">{{ __('Deteksi Anomali') }}</div>
                            <div class="mt-2">
                                <span class="me-3">{{ __('Total titik') }}: {{ $analysis['total_points'] ?? 0 }}</span>
                                <span class="me-3">{{ __('Mock') }}: {{ $analysis['mock_points'] ?? 0 }}</span>
                                <span class="me-3">{{ __('Max speed') }}: {{ $analysis['max_speed_kmh'] ?? 0 }} km/h</span>
                                <span class="me-3">{{ __('Event') }}: {{ $analysis['event_count'] ?? 0 }}</span>
                                <span class="me-3">{{ __('Need review') }}: {{ ($analysis['needs_review'] ?? false) ? 'Yes' : 'No' }}</span>
                            </div>
                            @if (!empty($analysis['events']))
                                <div class="table-responsive mt-3">
                                    <table class="table table-sm table-striped">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Type') }}</th>
                                                <th>{{ __('From') }}</th>
                                                <th>{{ __('To') }}</th>
                                                <th>{{ __('Distance (km)') }}</th>
                                                <th>{{ __('Duration (s)') }}</th>
                                                <th>{{ __('Speed (km/h)') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($analysis['events'] as $e)
                                                <tr>
                                                    <td>{{ $e['type'] }}</td>
                                                    <td>{{ $e['from_at'] }}</td>
                                                    <td>{{ $e['to_at'] }}</td>
                                                    <td>{{ $e['distance_km'] }}</td>
                                                    <td>{{ $e['duration_sec'] }}</td>
                                                    <td>{{ $e['speed_kmh'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="fw-bold">{{ __('Titik Berhenti Lama') }}</div>
                            <div class="text-muted mt-1">{{ __('Deteksi sederhana: tidak berpindah > 15m selama >= 5 menit.') }}</div>
                            <div class="table-responsive mt-2">
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Start') }}</th>
                                            <th>{{ __('End') }}</th>
                                            <th>{{ __('Durasi') }}</th>
                                            <th>{{ __('Lat') }}</th>
                                            <th>{{ __('Lng') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($stops as $s)
                                            <tr>
                                                <td>{{ $s['start_at'] }}</td>
                                                <td>{{ $s['end_at'] }}</td>
                                                <td>{{ floor(($s['duration_sec'] ?? 0) / 60) }} m</td>
                                                <td>{{ $s['lat'] }}</td>
                                                <td>{{ $s['lng'] }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center">{{ __('Tidak ada stop yang terdeteksi') }}</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div id="map" style="height: 420px;" class="mb-3"></div>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>{{ __('Waktu') }}</th>
                                    <th>{{ __('Lat') }}</th>
                                    <th>{{ __('Lng') }}</th>
                                    <th>{{ __('Akurasi') }}</th>
                                    <th>{{ __('Speed') }}</th>
                                    <th>{{ __('Mock') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($tracks as $t)
                                    <tr>
                                        <td>{{ $t->tracked_at }}</td>
                                        <td>{{ $t->lat }}</td>
                                        <td>{{ $t->lng }}</td>
                                        <td>{{ $t->accuracy ?? '-' }}</td>
                                        <td>{{ $t->speed ?? '-' }}</td>
                                        <td>{{ $t->is_mock }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">{{ __('Belum ada data tracking') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-3">
                <a href="{{ route('hr-attendances.index', ['date' => $row->date]) }}" class="btn btn-light">{{ __('Kembali') }}</a>
                @can('attendance manage')
                    <a href="{{ route('hr-attendances.edit', $row->id) }}" class="btn btn-primary">{{ __('Edit') }}</a>
                @endcan
            </div>
        </section>
    </div>
@endsection

@push('css')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
@endpush

@push('js')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        const trackPoints = [
            @foreach ($tracks as $t)
                { lat: {{ $t->lat }}, lng: {{ $t->lng }}, at: "{{ $t->tracked_at }}", mock: "{{ $t->is_mock }}" },
            @endforeach
        ];

        const fallbackLat = {{ $row->clock_in_lat ? (float) $row->clock_in_lat : 'null' }};
        const fallbackLng = {{ $row->clock_in_lng ? (float) $row->clock_in_lng : 'null' }};

        const map = L.map('map').setView([-2.5489, 118.0149], 5);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        if (trackPoints.length > 0) {
            const latlngs = trackPoints.map(p => [p.lat, p.lng]);
            const poly = L.polyline(latlngs, { color: '#2563eb', weight: 4 }).addTo(map);
            map.fitBounds(poly.getBounds(), { padding: [20, 20] });

            const first = trackPoints[0];
            const last = trackPoints[trackPoints.length - 1];
            L.marker([first.lat, first.lng]).addTo(map).bindPopup("Start: " + first.at + " | Mock: " + first.mock);
            L.marker([last.lat, last.lng]).addTo(map).bindPopup("Last: " + last.at + " | Mock: " + last.mock);
        } else if (fallbackLat != null && fallbackLng != null) {
            map.setView([fallbackLat, fallbackLng], 16);
            L.marker([fallbackLat, fallbackLng]).addTo(map).bindPopup("Clock In");
        }
    </script>
@endpush
