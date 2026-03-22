@extends('layouts.frontend.frontend-master')

@section('content')
    <section class="pt-5 pb-5" style="background-color: #ffffff;">
        <div class="container">
            <div class="section-title text-center mb-4">
                <h2 style="color: #003366;">Form Pendaftaran</h2>
                <p style="color: #666;">Lengkapi data berikut untuk proses pendaftaran layanan.</p>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header bg-white border-bottom-0 pb-0">
                            <h4 class="mb-1" style="color:#003366;">Formulir Pendaftaran Pelanggan Baru</h4>
                            <p class="mb-0 text-muted">Silakan isi data diri dengan benar untuk diproses sebagai request pelanggan.</p>
                        </div>
                        <div class="card-body">
                            @if (session('success'))
                                <div class="alert alert-success">{{ session('success') }}</div>
                            @endif
                            @if (session('error'))
                                <div class="alert alert-danger">{{ session('error') }}</div>
                            @endif
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            <div class="alert alert-info">Semua kolom wajib diisi kecuali Kode Referal.</div>
                            <form action="{{ route('daftar.store') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Nama Lengkap (Sesuai KTP)</label>
                                        <input type="text" class="form-control" name="nama" required>
                                        <div class="form-text">Isi sesuai nama di KTP.</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">NIK (16 Digit)</label>
                                        <input type="text" class="form-control" name="nik" inputmode="numeric"
                                            pattern="[0-9]{16}" required>
                                        <div class="form-text">Masukkan 16 digit NIK.</div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Alamat Lengkap Pemasangan</label>
                                    <textarea class="form-control" name="alamat" rows="3" required></textarea>
                                    <div class="form-text">Tuliskan alamat lengkap sesuai domisili.</div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Nomor WhatsApp Aktif (Diawali 62)</label>
                                        <input type="tel" class="form-control" name="no_whatsapp" value="62"
                                            pattern="^62[0-9]{8,13}$" required>
                                        <div class="form-text">Contoh: 6281234567890.</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Alamat Email Aktif</label>
                                        <input type="email" class="form-control" name="email" required>
                                        <div class="form-text">Contoh: nama@email.com.</div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Kode Referal (Opsional)</label>
                                    <input type="text" class="form-control" name="kode_referal" value="{{ old('kode_referal', $ref ?? '') }}">
                                    <div class="form-text">Isi jika memiliki kode referal.</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Titik Lokasi Pemasangan (Latitude & Longitude)</label>
                                    <div class="row g-2">
                                        <div class="col-md-5">
                                            <input type="text" class="form-control" id="latitude" name="latitude"
                                                readonly required>
                                        </div>
                                        <div class="col-md-5">
                                            <input type="text" class="form-control" id="longitude" name="longitude"
                                                readonly required>
                                        </div>
                                        <div class="col-md-2 d-grid">
                                            <button type="button" class="btn btn-outline-primary" id="lokasiSaya">Lokasi Saya</button>
                                        </div>
                                    </div>
                                    <div class="form-text">Tekan tombol untuk mengisi latitude dan longitude otomatis.</div>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Upload Foto Selfie dengan KTP <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control" name="photo_ktp" accept="image/*" required>
                                    <div class="form-text text-muted">
                                        <i class="bi bi-camera"></i> Pastikan wajah dan data KTP terlihat jelas dalam satu frame. Format: JPG, JPEG, PNG.
                                    </div>
                                </div>
                                <div class="text-center">
                                    <button type="submit" class="btn btn-primary" style="background-color: #007bff;">
                                        Kirim Pendaftaran
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('css')
    <style>
        .form-control,
        .form-select {
            color: #212529;
        }
    </style>
@endpush

@push('js')
    <script>
        const lokasiButton = document.getElementById('lokasiSaya');
        const latInput = document.getElementById('latitude');
        const lngInput = document.getElementById('longitude');

        if (lokasiButton) {
            lokasiButton.addEventListener('click', () => {
                if (!navigator.geolocation) {
                    alert('Geolocation tidak tersedia di perangkat ini.');
                    return;
                }
                navigator.geolocation.getCurrentPosition(
                    (pos) => {
                        const lat = pos.coords.latitude.toFixed(6);
                        const lng = pos.coords.longitude.toFixed(6);
                        latInput.value = lat;
                        lngInput.value = lng;
                    },
                    () => {
                        alert('Gagal mengambil lokasi. Pastikan izin lokasi diaktifkan.');
                    }
                );
            });
        }
    </script>
@endpush
