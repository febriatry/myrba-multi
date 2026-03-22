@extends('layouts.frontend.frontend-master')

@section('content')
    <style>
        .landing-card {
            border-radius: 12px;
        }
        .banner-slide img {
            width: 100%;
            height: auto;
            border-radius: 12px;
        }
        .video-frame {
            position: relative;
            padding-top: 56.25%;
            border-radius: 12px;
            overflow: hidden;
            background: #000;
        }
        .video-frame iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: 0;
        }
    </style>

    <section class="pt-4 pb-4" style="background-color: #e6f0ff;">
        <div class="container">
            <div class="section-title text-center mb-4">
                <h2 style="color: #003366;">Cek Tagihan Anda</h2>
                <p style="color: #666;">Masukkan ID Pelanggan Anda untuk melihat tagihan bulanan.</p>
            </div>
            <div class="row justify-content-center">
                <div class="col-12 col-md-6">
                    <div class="card landing-card p-3">
                        <form action="{{ route('website') }}" method="GET">
                            <div class="form-group">
                                <input type="text" name="no_tagihan" class="form-control form-control-lg"
                                    placeholder="Masukkan ID Pelanggan" required>
                            </div>
                            <div class="form-group text-center mt-3">
                                <button type="submit" class="btn btn-primary btn-lg"
                                    style="background-color: #007bff;">Cek Tagihan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="pt-4 pb-4" style="background-color: #f7fafc;">
        <div class="container text-center">
            <a href="https://myrba.net/download/myrba-client.apk" class="btn btn-success btn-lg shadow-lg">
                <i class="fas fa-download me-2"></i> Download Aplikasi Android
            </a>
            <p class="mt-2 text-muted small">Dapatkan pengalaman lebih baik dengan aplikasi MyRBA</p>
        </div>
    </section>

    @php
        $banners = \App\Models\BannerManagement::where('is_aktif', 'Yes')->orderBy('urutan')->get();
    @endphp

    <section class="pt-4 pb-4" style="background-color: #ffffff;">
        <div class="container">
            <div class="section-title text-center mb-3">
                <h2 style="color: #003366;">Info Terbaru</h2>
                <p style="color: #666;">Promo dan pengumuman terbaru dari kami.</p>
            </div>
            @if ($banners->count() > 0)
                <div id="bannerCarousel" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-indicators">
                        @foreach ($banners as $index => $banner)
                            <button type="button" data-bs-target="#bannerCarousel" data-bs-slide-to="{{ $index }}"
                                class="{{ $index === 0 ? 'active' : '' }}" aria-current="true"
                                aria-label="Slide {{ $index + 1 }}"></button>
                        @endforeach
                    </div>
                    <div class="carousel-inner">
                        @foreach ($banners as $index => $banner)
                            <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                                <div class="banner-slide">
                                    <img src="{{ asset('storage/uploads/file_banners/' . $banner->file_banner) }}"
                                        alt="Banner {{ $index + 1 }}">
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="text-center text-muted">Belum ada banner aktif.</div>
            @endif
        </div>
    </section>

    <section class="pricing-area pt-4 pb-4" style="background-color: #ffffff;">
        <div class="container">
            <div class="section-title text-center">
                <h2 style="color: #003366;">Pilih Paket Internet</h2>
                <p style="color: #666;">Tersedia hanya untuk wilayah tertentu. Harga belum termasuk PPN.</p>
            </div>
            <div class="row justify-content-center">
                @php
                    $paket = [
                        [
                            'nama' => 'RBA Lite',
                            'harga' => 110000,
                            'kecepatan' => 'Up to 20 Mbps',
                            'is_coming_soon' => true,
                        ],
                        [
                            'nama' => 'RBA Fast',
                            'harga' => 135000,
                            'kecepatan' => 'Up to 25 Mbps',
                            'is_coming_soon' => false,
                        ],
                        [
                            'nama' => 'RBA Pro',
                            'harga' => 165000,
                            'kecepatan' => 'Up to 50 Mbps',
                            'is_coming_soon' => false,
                        ],
                        [
                            'nama' => 'RBA Ultra',
                            'harga' => 300000,
                            'kecepatan' => 'Up to 100 Mbps',
                            'is_coming_soon' => false,
                        ],
                    ];
                @endphp

                @foreach ($paket as $p)
                    <div class="col-12 col-md-6 col-lg-3 mb-3">
                        <div class="single-pricing-box text-center p-4"
                            style="background: #e6f0ff; border: 1px solid #b3d1ff; border-radius: 10px; height: 100%; display: flex; flex-direction: column; justify-content: space-between;">
                            <div>
                                <h3 style="color: #003366;">{{ $p['nama'] }}</h3>
                                <p class="mb-2" style="font-weight: bold; color: #003366;">{{ $p['kecepatan'] }}</p>
                                <h4 style="color: #007bff;">Rp {{ number_format($p['harga'], 0, ',', '.') }}</h4>
                                <p>Wilayah terbatas</p>
                            </div>
                            
                            @if ($p['is_coming_soon'])
                                <div class="mt-3">
                                    <button class="btn btn-secondary w-100" disabled>Coming Soon</button>
                                    <div class="countdown mt-2 text-danger font-weight-bold" data-target="2026-04-01 00:00:00">
                                        Loading...
                                    </div>
                                </div>
                            @else
                                <a href="{{ route('daftar') }}" class="btn btn-primary mt-3 w-100"
                                    style="background-color: #007bff;">
                                    Daftar
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            
            <div class="text-center mt-4">
                <p class="text-muted">
                    Dengan mendaftar, Anda menyetujui <a href="{{ route('syarat-ketentuan') }}" class="text-primary text-decoration-none">Syarat dan Ketentuan</a> layanan kami.
                </p>
            </div>
        </div>
    </section>

    @php
        $videos = [];
        if (!empty($settingWeb->video_url_1)) {
            $videos[] = $settingWeb->video_url_1;
        }
        if (!empty($settingWeb->video_url_2)) {
            $videos[] = $settingWeb->video_url_2;
        }
        
        // Default videos jika tidak ada yang diisi di setting
        if (empty($videos)) {
            $videos = [
                'https://www.youtube.com/embed/dQw4w9WgXcQ', // Placeholder 1
                'https://www.youtube.com/embed/ScMzIvxBSi4', // Placeholder 2
            ];
        }
    @endphp

    @if(!empty($videos))
    <section class="pt-4 pb-4" style="background-color: #f7fafc;">
        <div class="container">
            <div class="section-title text-center mb-3">
                <h2 style="color: #003366;">Video Layanan</h2>
                <p style="color: #666;">Lihat informasi layanan kami melalui video berikut.</p>
            </div>
            <div id="videoCarousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-indicators">
                    @foreach ($videos as $index => $video)
                        <button type="button" data-bs-target="#videoCarousel" data-bs-slide-to="{{ $index }}"
                            class="{{ $index === 0 ? 'active' : '' }}" aria-current="true"
                            aria-label="Slide {{ $index + 1 }}"></button>
                    @endforeach
                </div>
                <div class="carousel-inner">
                    @foreach ($videos as $index => $video)
                        <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                            <div class="video-frame">
                                <iframe src="{{ $video }}" allowfullscreen></iframe>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
    @endif
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const countdownElements = document.querySelectorAll('.countdown');

            countdownElements.forEach(el => {
                const targetDate = new Date(el.getAttribute('data-target')).getTime();

                const updateCountdown = () => {
                    const now = new Date().getTime();
                    const distance = targetDate - now;

                    if (distance < 0) {
                        el.innerHTML = "EXPIRED";
                        return;
                    }

                    const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                    el.innerHTML = `${days}d ${hours}h ${minutes}m ${seconds}s`;
                };

                setInterval(updateCountdown, 1000);
                updateCountdown();
            });
        });
    </script>
@endsection
