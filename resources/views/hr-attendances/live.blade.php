@extends('layouts.app')

@section('title', __('Live Tracking'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Live Tracking') }}</h3>
                    <p class="text-subtitle text-muted">{{ __('Pantau posisi terakhir karyawan yang sedang bekerja (status open).') }}</p>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="/dashboard">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Live Tracking') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <x-alert></x-alert>

            <div class="card">
                <div class="card-body">
                    <form class="d-flex gap-2 mb-3" method="GET" action="{{ route('hr-attendances-live.index') }}">
                        <input type="date" name="date" class="form-control" value="{{ $date }}">
                        <button class="btn btn-outline-primary" type="submit">{{ __('Tampilkan') }}</button>
                    </form>

                    <div class="mb-2">
                        <span class="fw-bold">{{ __('Tanggal') }}:</span> {{ $date }} | <span class="fw-bold">{{ __('Jumlah') }}:</span> <span id="count">0</span>
                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <div id="map" style="height: 520px;"></div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <div class="fw-bold">{{ __('Rute & Aktivitas') }}</div>
                                </div>
                                <div class="card-body">
                                    <div class="text-muted mb-2">{{ __('Klik marker karyawan untuk melihat rute, stop, dan catatan.') }}</div>
                                    <div class="mb-2">
                                        <div class="fw-bold" id="panelTitle">{{ __('Belum dipilih') }}</div>
                                        <div class="text-muted" id="panelSub"></div>
                                    </div>
                                    <div class="mb-2">
                                        <span class="me-2">{{ __('Titik') }}: <span id="panelPoints">0</span></span>
                                        <span class="me-2">{{ __('Stop') }}: <span id="panelStops">0</span></span>
                                        <span class="me-2">{{ __('Catatan') }}: <span id="panelNotes">0</span></span>
                                    </div>
                                    <div class="fw-bold mt-3">{{ __('Stop (lama tidak bergerak)') }}</div>
                                    <div class="text-muted">{{ __('>= 5 menit, radius 15m') }}</div>
                                    <ul class="mt-2" id="stopList"></ul>
                                    <div class="fw-bold mt-3">{{ __('Catatan') }}</div>
                                    <ul class="mt-2" id="noteList"></ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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
        const map = L.map('map').setView([-2.5489, 118.0149], 5);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        let markers = {};
        let activeRoute = null;
        let activeStopsLayer = null;

        async function loadData() {
            const res = await fetch("{{ route('hr-attendances-live.data') }}?date={{ $date }}", {
                headers: { 'Accept': 'application/json' }
            });
            const json = await res.json();
            document.getElementById('count').innerText = json.count || 0;

            const bounds = [];
            const seen = {};

            (json.items || []).forEach(item => {
                const key = String(item.session_id);
                seen[key] = true;
                const lat = item.lat;
                const lng = item.lng;
                if (lat == null || lng == null) return;
                bounds.push([lat, lng]);
                const speedKmh = item.speed_kmh != null ? Number(item.speed_kmh).toFixed(1) : '-';
                const label = `${item.user_name}\n${item.jabatan_name || '-'}\nMock: ${item.is_mock}\nSpeed: ${speedKmh} km/h\nTracked: ${item.tracked_at || '-'}\nSession: ${item.session_id}`;
                const isBad = String(item.is_mock) === 'Yes' || (item.speed_kmh != null && Number(item.speed_kmh) >= 150);
                const color = isBad ? '#dc2626' : '#16a34a';

                if (!markers[key]) {
                    markers[key] = L.circleMarker([lat, lng], { radius: 8, color, fillColor: color, fillOpacity: 0.9 })
                        .addTo(map)
                        .bindPopup(label)
                        .on('click', () => loadSessionRoute(item.session_id));
                } else {
                    markers[key].setLatLng([lat, lng]).setStyle({ color, fillColor: color }).setPopupContent(label);
                }
            });

            Object.keys(markers).forEach(k => {
                if (!seen[k]) {
                    map.removeLayer(markers[k]);
                    delete markers[k];
                }
            });

            if (bounds.length > 0) {
                map.fitBounds(bounds, { padding: [30, 30] });
            }
        }

        function clearRoute() {
            if (activeRoute) {
                map.removeLayer(activeRoute);
                activeRoute = null;
            }
            if (activeStopsLayer) {
                map.removeLayer(activeStopsLayer);
                activeStopsLayer = null;
            }
        }

        async function loadSessionRoute(sessionId) {
            clearRoute();
            document.getElementById('panelTitle').innerText = 'Loading...';
            document.getElementById('panelSub').innerText = '';
            document.getElementById('panelPoints').innerText = '0';
            document.getElementById('panelStops').innerText = '0';
            document.getElementById('panelNotes').innerText = '0';
            document.getElementById('stopList').innerHTML = '';
            document.getElementById('noteList').innerHTML = '';

            const res = await fetch("{{ url('/hr-attendances-live/session') }}/" + sessionId, {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) {
                document.getElementById('panelTitle').innerText = 'Gagal memuat';
                return;
            }
            const json = await res.json();
            const s = json.session;
            document.getElementById('panelTitle').innerText = s.user_name;
            document.getElementById('panelSub').innerText = `${s.jabatan_name || '-'} | Session ${s.id} | ${s.date}`;

            const pts = json.points || [];
            document.getElementById('panelPoints').innerText = String(pts.length);

            if (pts.length > 1) {
                const latlngs = pts.map(p => [p.lat, p.lng]);
                activeRoute = L.polyline(latlngs, { color: '#2563eb', weight: 4 }).addTo(map);
                map.fitBounds(activeRoute.getBounds(), { padding: [20, 20] });
            } else if (pts.length === 1) {
                map.setView([pts[0].lat, pts[0].lng], 16);
            }

            const stops = json.stops || [];
            document.getElementById('panelStops').innerText = String(stops.length);
            if (stops.length > 0) {
                activeStopsLayer = L.layerGroup().addTo(map);
                stops.forEach(st => {
                    const mins = Math.floor((st.duration_sec || 0) / 60);
                    const label = `Stop ${mins} m\n${st.start_at} - ${st.end_at}`;
                    L.circleMarker([st.lat, st.lng], { radius: 7, color: '#111827', fillColor: '#f59e0b', fillOpacity: 0.9 })
                        .addTo(activeStopsLayer)
                        .bindPopup(label);

                    const li = document.createElement('li');
                    li.innerText = `${mins} m | ${st.start_at} - ${st.end_at}`;
                    document.getElementById('stopList').appendChild(li);
                });
            }

            const notes = json.notes || [];
            document.getElementById('panelNotes').innerText = String(notes.length);
            notes.forEach(n => {
                const li = document.createElement('li');
                li.innerText = `${n.noted_at}: ${n.note}`;
                document.getElementById('noteList').appendChild(li);
            });
        }

        loadData();
        setInterval(loadData, 15000);
    </script>
@endpush
