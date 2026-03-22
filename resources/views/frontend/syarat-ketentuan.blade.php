@extends('layouts.frontend.frontend-master')

@section('content')
    <section class="pt-5 pb-5" style="background-color: #ffffff;">
        <div class="container">
            <div class="section-title text-center mb-5">
                <h2 style="color: #003366; font-weight: bold;">SYARAT DAN KETENTUAN LAYANAN</h2>
                <h4 style="color: #007bff;">RBA SOLUTION</h4>
            </div>
            
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-5">
                            <p class="lead mb-4" style="color: #555;">
                                Dengan menggunakan layanan internet dari RBA Solution, pelanggan dianggap telah membaca, memahami, dan menyetujui seluruh syarat dan ketentuan berikut:
                            </p>

                            <div class="mb-4">
                                <h5 class="fw-bold" style="color: #003366;">1. Masa Berlangganan</h5>
                                <p style="color: #555;">
                                    Pelanggan yang berlangganan layanan internet RBA Solution wajib mengikuti masa berlangganan minimal <strong>6 (enam) bulan</strong>. Tagihan layanan akan tetap berjalan selama masa kontrak 6 bulan penuh, meskipun pelanggan berhenti menggunakan layanan sebelum masa tersebut berakhir.
                                </p>
                            </div>

                            <div class="mb-4">
                                <h5 class="fw-bold" style="color: #003366;">2. Larangan Penjualan Kembali Layanan</h5>
                                <p style="color: #555;">
                                    Layanan internet yang disediakan oleh RBA Solution <strong>tidak diperbolehkan</strong> untuk diperjualbelikan kembali kepada pihak lain tanpa izin resmi dari pihak RBA Solution. Apabila pelanggan terbukti melakukan penjualan kembali layanan tanpa izin, maka pelanggan dapat dikenakan sanksi sesuai dengan peraturan dan perundang-undangan yang berlaku di Indonesia.
                                </p>
                            </div>

                            <div class="mb-4">
                                <h5 class="fw-bold" style="color: #003366;">3. Kode Referral</h5>
                                <p style="color: #555;">
                                    Apabila pelanggan bergabung melalui rekomendasi atau referensi dari pelanggan lain, maka pelanggan wajib mencantumkan <strong>kode referral</strong> pada saat pendaftaran layanan.
                                </p>
                            </div>

                            <div class="mb-4">
                                <h5 class="fw-bold" style="color: #003366;">4. Surat Pernyataan (Khusus Paket RBA Lite)</h5>
                                <p style="color: #555;">
                                    Khusus pelanggan yang berlangganan <strong>Paket RBA Lite (Rp110.000)</strong>, pelanggan wajib mengisi dan menandatangani surat pernyataan bermaterai. Biaya materai sepenuhnya ditanggung oleh pelanggan.
                                </p>
                            </div>

                            <div class="mb-4">
                                <h5 class="fw-bold" style="color: #003366;">5. Jam Pelayanan</h5>
                                <p style="color: #555;">
                                    Pelanggan memahami dan menyetujui bahwa jam pelayanan efektif RBA Solution adalah pukul <strong>08.00 WIB sampai dengan pukul 16.00 WIB</strong> pada hari kerja. Permintaan layanan atau laporan gangguan di luar jam tersebut akan ditangani pada jam pelayanan berikutnya.
                                </p>
                            </div>

                            <div class="mb-4">
                                <h5 class="fw-bold" style="color: #003366;">6. Pajak</h5>
                                <p style="color: #555;">
                                    Seluruh harga layanan yang tercantum <strong>belum termasuk Pajak Pertambahan Nilai (PPN)</strong>. Apabila terdapat kewajiban pajak, maka PPN sepenuhnya menjadi tanggungan pelanggan sesuai dengan ketentuan perpajakan yang berlaku.
                                </p>
                            </div>

                            <div class="mb-4">
                                <h5 class="fw-bold" style="color: #003366;">7. Ketentuan Pembayaran</h5>
                                <p style="color: #555;">
                                    Pelanggan wajib melakukan pembayaran tagihan sebelum atau pada tanggal jatuh tempo yang telah ditentukan. Apabila pembayaran melewati tanggal jatuh tempo, maka layanan internet pelanggan dapat dilakukan <strong>isolir (pemutusan sementara)</strong> oleh pihak RBA Solution.
                                </p>
                                <p style="color: #555;">
                                    Meskipun layanan dalam kondisi terisolir, tagihan tetap berjalan dan tetap menjadi kewajiban pelanggan untuk dibayarkan sesuai dengan ketentuan masa berlangganan yang berlaku.
                                </p>
                            </div>

                            <div class="mb-4">
                                <h5 class="fw-bold" style="color: #003366;">8. Persetujuan Pelanggan</h5>
                                <p style="color: #555;">
                                    Dengan melakukan pendaftaran dan menggunakan layanan internet dari RBA Solution, pelanggan dianggap telah membaca, memahami, dan menyetujui seluruh syarat dan ketentuan yang berlaku.
                                </p>
                            </div>

                            <div class="text-center mt-5">
                                <a href="{{ route('website') }}" class="btn btn-primary btn-lg px-5" style="background-color: #007bff;">
                                    <i class="bi bi-arrow-left"></i> Kembali ke Beranda
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
