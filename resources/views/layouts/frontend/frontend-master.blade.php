<!doctype html>
<html lang="zxx">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>My RBA Billing</title>

    <link rel="stylesheet" href="{{ asset('frontend') }}/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ asset('frontend') }}/assets/css/animate.min.css">
    <link rel="stylesheet" href="{{ asset('frontend') }}/assets/css/fontawesome.min.css">
    <link rel="stylesheet" href="{{ asset('frontend') }}/assets/css/magnific-popup.min.css">
    <link rel="stylesheet" href="{{ asset('frontend') }}/assets/css/flaticon.css">
    <link rel="stylesheet" href="{{ asset('frontend') }}/assets/css/nice-select.min.css">
    <link rel="stylesheet" href="{{ asset('frontend') }}/assets/css/meanmenu.css">
    <link rel="stylesheet" href="{{ asset('frontend') }}/assets/css/owl.carousel.min.css">
    <link rel="stylesheet" href="{{ asset('frontend') }}/assets/css/owl.theme.default.min.css">
    <link rel="stylesheet" href="{{ asset('frontend') }}/assets/css/style.css">
    <link rel="stylesheet" href="{{ asset('frontend') }}/assets/css/responsive.css">
    <link rel="stylesheet" href="{{ asset('frontend') }}/assets/css/dark-style.css">
    <link href="{{ asset('mazer/assets/jqvmap/dist/jqvmap.min.css') }}" rel="stylesheet" />
    <style>
        .form-control,
        .form-select,
        textarea,
        input,
        .form-control:focus,
        .form-select:focus {
            color: #212529 !important;
            background-color: #ffffff !important;
            caret-color: #212529 !important;
        }

        .form-control::placeholder,
        .form-select::placeholder,
        textarea::placeholder,
        input::placeholder {
            color: #6c757d !important;
        }

        .form-control:-webkit-autofill,
        .form-control:-webkit-autofill:hover,
        .form-control:-webkit-autofill:focus,
        .form-select:-webkit-autofill,
        .form-select:-webkit-autofill:hover,
        .form-select:-webkit-autofill:focus {
            -webkit-text-fill-color: #212529 !important;
            transition: background-color 5000s ease-in-out 0s;
        }

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
            background: linear-gradient(#f70505 0%, #8f0808 100%);
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
            border-left: 3px solid #8f0808;
            border-right: 3px solid transparent;
            border-bottom: 3px solid transparent;
            border-top: 3px solid #8f0808;
        }

        .ribbon span::after {
            content: '';
            position: absolute;
            right: 0%;
            top: 100%;
            z-index: -1;
            border-right: 3px solid #8f0808;
            border-left: 3px solid transparent;
            border-bottom: 3px solid transparent;
            border-top: 3px solid #8f0808;
        }
    </style>
    @php
        $settingWeb = getSettingWeb();
    @endphp
</head>
@stack('css')

<body>

    <!-- Header -->
    <header class="header-area">
        <div class="navbar-area" style="background-color: #003366;">
            <div class="bahama-nav">
                <div class="container">
                    <nav class="navbar navbar-expand-md navbar-light">
                        <a class="navbar-brand d-flex align-items-center gap-2" href="/">
                            @if ($settingWeb && $settingWeb->logo)
                                <img src="{{ asset('storage/uploads/logos/' . $settingWeb->logo) }}" alt="Logo"
                                    style="height: 50px; width: auto; border-radius: 6px;">
                            @else
                                <img src="{{ asset('default-logo.png') }}" alt="Default Logo"
                                    style="height: 50px; width: auto; border-radius: 6px;">
                            @endif
                            <h1 class="mb-0" style="color: #ffffff; font-size: 1.5rem;"><b>My RBA Billing</b></h1>
                        </a>
                    </nav>
                </div>
            </div>
        </div>
    </header>



    @yield('content')

    <!-- Scroll top -->
    <div class="go-top"><i class="fas fa-arrow-up"></i></div>

    <!-- Scripts -->
    <script src="{{ asset('frontend') }}/assets/js/jquery.min.js"></script>
    <script src="{{ asset('frontend') }}/assets/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('frontend') }}/assets/js/jquery.meanmenu.js"></script>
    <script src="{{ asset('frontend') }}/assets/js/jquery.magnific-popup.min.js"></script>
    <script src="{{ asset('frontend') }}/assets/js/owl.carousel.min.js"></script>
    <script src="{{ asset('frontend') }}/assets/js/parallax.min.js"></script>
    <script src="{{ asset('frontend') }}/assets/js/jquery.nice-select.min.js"></script>
    <script src="{{ asset('frontend') }}/assets/js/wow.min.js"></script>
    <script src="{{ asset('frontend') }}/assets/js/jquery.ajaxchimp.min.js"></script>
    <script src="{{ asset('frontend') }}/assets/js/form-validator.min.js"></script>
    <script src="{{ asset('frontend') }}/assets/js/contact-form-script.js"></script>
    <script src="{{ asset('frontend') }}/assets/js/main.js"></script>
    <script src="{{ asset('mazer/assets/jqvmap/dist/jquery.vmap.js') }}"></script>
    <script src="{{ asset('mazer/assets/jqvmap/dist/maps/jquery.vmap.world.js') }}"></script>
    <script src="{{ asset('mazer/assets/jqvmap/examples/js/jquery.vmap.sampledata.js') }}"></script>
    @include('sweetalert::alert')
    @stack('js')
    <!-- DESKRIPSI SINGKAT LAYANAN -->
    <section class="pt-5 pb-5" style="background-color: #f0f8ff;">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-lg-10">
                    <h2 class="mb-4" style="color: #003366;"><strong>Hubungi Kami</strong></h2>
                    <p style="font-size: 1.1rem; color: #555; mb-4">
                        Butuh bantuan atau informasi lebih lanjut? Hubungi Customer Service kami.
                    </p>
                    <div class="mt-4">
                        <a href="https://wa.me/6285133234599" target="_blank" class="btn btn-success btn-lg px-5 py-3 shadow-sm rounded-pill" style="font-size: 1.2rem; font-weight: 600;">
                            <i class="fab fa-whatsapp me-2" style="font-size: 1.5rem;"></i> Chat via WhatsApp
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    

</body>
<!-- DESKRIPSI SINGKAT LAYANAN -->
    <footer class="footer-area" style="background-color: #003366; color: #ffffff; padding: 40px 0 20px; border-top: 3px solid #007bff;">
    <div class="container">
        <div class="row align-items-center mb-4">
            <div class="col-md-6 text-center text-md-start">
                <h4 class="mb-2" style="color: #ffffff;"><strong>My RBA</strong></h4>
                <h3 class="mb-2" style="color: #ffffff;"><strong>CV RINTIS PILAR MUDA</strong></h3>
                <p style="color: #cbd5e0; font-size: 0.9rem; max-width: 400px;">
                    Jebug, RT02 RW10 Desa Punggelan, Kec. Punggelan, Kab. Banjarnegara, Jawa Tengah Kode Pos: 53462
                </p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <div class="contact-info">
                    <p class="mb-1" style="color: #ffffff;"><i class="fas fa-phone-alt me-2"></i> CS: +62 851-3323-4599</p>
                    <p class="mb-0" style="color: #ffffff;"><i class="fas fa-envelope me-2"></i> info@myrba.net</p>
                </div>
            </div>
        </div>

        <hr style="background-color: rgba(255,255,255,0.2);">

        <div class="row pt-2">
            <div class="col-md-6 text-center text-md-start">
                <p style="color: #cbd5e0; font-size: 0.85rem;">
                    &copy; {{ date('Y') }} <strong>My RBA</strong>. Seluruh Hak Cipta Dilindungi.
                </p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <div class="social-links">
                    <a href="https://www.facebook.com/people/RBA-Solution/61588659569146/" class="text-white me-3" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://wa.me/6285133234599" class="text-white" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>
        </div>
    </div>
</footer>


</html>
