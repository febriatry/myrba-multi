@extends('layouts.frontend.frontend-master')

@push('css')
    <style>
        .frame {
            height: 65px;
            width: 160px;
            position: relative;
        }

        .img-aja {
            max-height: 100%;
            max-width: 100%;
            width: auto;
            height: auto;
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0;
            right: 0;
            margin: auto;
            padding: 0.25rem;
            background-color: #fff;
            /* border: 1px solid #dee2e6; */
            border-radius: 0.25rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, .075);
        }
    </style>

    <style>
        .special-card {
            background-color: rgba(245, 245, 245, 0.6) !important;
        }
    </style>

    <style>
        .ribbon {
            position: absolute;
            right: -5px;
            top: -5px;
            z-index: 1;
            overflow: hidden;
            width: 93px;
            height: 93px;
            text-align: right;
        }

        .ribbon span {
            font-size: 0.7rem;
            color: #fff;
            text-transform: uppercase;
            text-align: center;
            font-weight: bold;
            line-height: 32px;
            transform: rotate(45deg);
            width: 125px;
            display: block;
            background: #79a70a;
            background: linear-gradient(#9bc90d 0%, #79a70a 100%);
            box-shadow: 0 3px 10px -5px rgba(0, 0, 0, 1);
            position: absolute;
            top: 17px;
            right: -29px;
        }

        .ribbon span::before {
            content: '';
            position: absolute;
            left: 0px;
            top: 100%;
            z-index: -1;
            border-left: 3px solid #79A70A;
            border-right: 3px solid transparent;
            border-bottom: 3px solid transparent;
            border-top: 3px solid #79A70A;
        }

        .ribbon span::after {
            content: '';
            position: absolute;
            right: 0%;
            top: 100%;
            z-index: -1;
            border-right: 3px solid #79A70A;
            border-left: 3px solid transparent;
            border-bottom: 3px solid transparent;
            border-top: 3px solid #79A70A;
        }

        .red span {
            background: linear-gradient(#f70505 0%, #8f0808 100%);
        }

        .red span::before {
            border-left-color: #8f0808;
            border-top-color: #8f0808;
        }

        .red span::after {
            border-right-color: #8f0808;
            border-top-color: #8f0808;
        }

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
@endpush

@section('content')
    <section class="pt-4 pb-4" style="background-color: #e6f0ff;">
        <div class="container">
            <div class="section-title text-center mb-4">
                <h2 style="color: #003366;">Cek Tagihan Anda</h2>
                <p style="color: #666;">Masukkan ID Pelanggan Anda untuk melihat tagihan bulanan.</p>
            </div>
            <div class="row g-3">
                <div class="col-12">
                    <div class="card text-white bg-primary mb-0 landing-card">
                        <div class="card-header"><b>Masukan ID Pelanggan</b></div>
                        <form action="{{ route('website') }}" method="GET">
                            <div class="card-body special-card">
                                @if ($no_tagihan != '')
                                    @if ($tagihan != null)
                                        @if ($tagihan->status_bayar == 'Sudah Bayar')
                                            <div class="ribbon"><span>Sudah Bayar</span></div>
                                        @else
                                            <div class="ribbon red"><span>Belum Bayar</span></div>
                                        @endif
                                    @endif
                                @endif
                                <div class="form-group">
                                    <input type="text" class="form-control" name="no_tagihan" id="no_tagihan"
                                        required="no_tagihan" value="{{ $no_tagihan }}" style="border-color: white">
                                </div>
                                <div class="form-group mt-3 text-center">
                                    <button type="submit" class="btn"
                                        style="background-color: blue; color:white">Submit</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card text-white bg-primary landing-card">
                        <div class="card-header"><b>Informasi</b></div>
                        <div class="card-body special-card">
                            @if ($no_tagihan == '')
                                <center><b>Silahkan isi form Terlebih dahulu untuk melihat tagihan</b></center>
                            @else
                                @if ($tagihan != null)
                                    @if ($tagihanCount > 0)
                                        <div class="alert alert-danger" role="alert">
                                            <b>Anda mempunya tunggakan {{ $tagihanCount }} bulan pembayaran. Harap segera bayarkan !!!</b>
                                        </div>
                                    @else
                                        <div class="alert alert-success" role="alert">
                                            <b>Terimakasih, tagihan internet anda sudah terbayar semua !!!</b>
                                        </div>
                                    @endif
                                    <table class="table table-hover table-striped">
                                        <tr>
                                            <td class="fw-bold">{{ __('ID Pelanggan') }}</td>
                                            <td>{{ $tagihan->no_layanan }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">{{ __('No Tagihan') }}</td>
                                            <td>{{ $tagihan->no_tagihan }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">{{ __('Pelanggan') }}</td>
                                            <td>{{ $tagihan->nama }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">{{ __('Periode') }}</td>
                                            <td>{{ $tagihan->periode }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">{{ __('Metode Bayar') }}</td>
                                            <td>{{ $tagihan->metode_bayar }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">{{ __('Status Bayar') }}</td>
                                            <td>{{ $tagihan->status_bayar }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">{{ __('Nominal Bayar') }}</td>
                                            <td>{{ rupiah($tagihan->nominal_bayar) }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">{{ __('Potongan Bayar') }}</td>
                                            <td>{{ rupiah($tagihan->potongan_bayar) }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">{{ __('PPN') }}</td>
                                            <td>{{ $tagihan->ppn }} - {{ rupiah($tagihan->nominal_ppn) }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">{{ __('Total Bayar') }}</td>
                                            <td>
                                                (Nominal Bayar - Potongan Bayar) + PPN <br>
                                                ({{ rupiah($tagihan->nominal_bayar) }} -
                                                {{ rupiah($tagihan->potongan_bayar) }}) +
                                                {{ rupiah($tagihan->nominal_ppn) }} <br>
                                                <b>{{ rupiah($tagihan->total_bayar) }}</b>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold">{{ __('Tanggal Bayar') }}</td>
                                            <td>{{ isset($tagihan->tanggal_bayar) ? $tagihan->tanggal_bayar : '' }}</td>
                                        </tr>
                                    </table>
                                    @if ($tagihan->status_bayar == 'Belum Bayar')
                                        <div class="accordion" id="accordionExample">
                                            <div class="accordion-item">
                                                <h2 class="accordion-header" id="headingTwo">
                                                    <button class="accordion-button collapsed" type="button"
                                                        data-bs-toggle="collapse" data-bs-target="#collapseTwo"
                                                        aria-expanded="false" aria-controls="collapseTwo">
                                                        <b>LANGSUNG BAYAR TAGIHAN</b>
                                                    </button>
                                                </h2>
                                                <div id="collapseTwo" class="accordion-collapse collapse"
                                                    aria-labelledby="headingTwo" data-bs-parent="#accordionExample">
                                                    <div class="accordion-body">
                                                        <div class="col-md-12">
                                                            <div class="card">
                                                                <div class="card-body">
                                                                    <div class="row">
                                                                        @foreach ($metodeBayar as $row)
                                                                            <div class="col-6 col-md-3 mb-3">
                                                                                <div class="small-box bg-light"
                                                                                    style="border-radius: 5%">
                                                                                    <center>
                                                                                        <div class="frame">
                                                                                            <img src="{{ $row->icon_url }}"
                                                                                                class="img-aja"
                                                                                                style="height: 80%" />
                                                                                        </div>
                                                                                        <a href="{{ route('bayar', [
                                                                                            'tagihan_id' => $tagihan->id,
                                                                                            'metode' => $row->code,
                                                                                        ]) }}"
                                                                                            class="small-box-footer"
                                                                                            style="color: blue">
                                                                                            <b>Pilih Metode</b>
                                                                                            <i class="fas fa-arrow-circle-right"></i>
                                                                                        </a>
                                                                                    </center>
                                                                                </div>
                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @else
                                    <center><b>Data tidak ditemukan, Silahkan cek kembali no tagihan</b></center>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
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
                    <button class="carousel-control-prev" type="button" data-bs-target="#bannerCarousel"
                        data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#bannerCarousel"
                        data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
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
                        ['harga' => 110000, 'kecepatan' => '20 Mbps', 'limited' => true, 'keterangan' => 'Syarat dan ketentuan berlaku'],
                        ['harga' => 135000, 'kecepatan' => '20 Mbps'],
                        ['harga' => 165000, 'kecepatan' => '50 Mbps'],
                        ['harga' => 205000, 'kecepatan' => '75 Mbps'],
                        ['harga' => 350000, 'kecepatan' => '100 Mbps'],
                    ];
                @endphp

                @foreach ($paket as $p)
                    <div class="col-12 col-md-6 col-lg-3 mb-3">
                        <div class="single-pricing-box text-center p-4"
                            style="background: #e6f0ff; border: 1px solid #b3d1ff; border-radius: 10px; position: relative; overflow: hidden;">
                            @if (!empty($p['limited']))
                                <div class="ribbon"><span>Limited Offer</span></div>
                            @endif
                            <h3 style="color: #003366;">{{ $p['kecepatan'] }}</h3>
                            <h4 style="color: #007bff;">Rp {{ number_format($p['harga'], 0, ',', '.') }}</h4>
                            <p>Wilayah terbatas hub CS</p>
                            @if (!empty($p['keterangan']))
                                <p><a href="{{ route('syarat-ketentuan') }}">{{ $p['keterangan'] }}</a></p>
                            @endif
                            <a href="{{ route('daftar') }}" class="btn btn-primary mt-3"
                                style="background-color: #007bff;">
                                Daftar
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    @php
        $videos = [
            'https://www.youtube.com/embed/WEkSYw3o5is',
            'https://www.youtube.com/embed/dQw4w9WgXcQ',
        ];
    @endphp

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
                <button class="carousel-control-prev" type="button" data-bs-target="#videoCarousel"
                    data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#videoCarousel"
                    data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            </div>
        </div>
    </section>
@endsection
