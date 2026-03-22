<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Berlangganan - {{ $data->nama }}</title>
    <style>
        @page {
            size: A4;
            margin: 1.5cm;
        }
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 11pt;
            line-height: 1.3;
            color: #000;
            background: #fff;
        }
        .header {
            display: none; /* Hilangkan kop surat */
        }
        .content {
            margin-bottom: 20px;
        }
        .content h3 {
            text-align: center;
            text-transform: uppercase;
            text-decoration: underline;
            margin-bottom: 20px;
            font-size: 14pt;
        }
        .table-data {
            width: 100%;
            margin-bottom: 15px;
        }
        .table-data td {
            padding: 3px 5px;
            vertical-align: top;
        }
        .table-data td:first-child {
            width: 140px;
        }
        .table-data td:nth-child(2) {
            width: 10px;
        }
        .terms {
            text-align: justify;
            margin-bottom: 20px;
        }
        .terms ol {
            padding-left: 20px;
            margin-top: 5px;
            margin-bottom: 5px;
        }
        .terms li {
            margin-bottom: 3px;
        }
        .signature {
            width: 100%;
            margin-top: 30px;
        }
        .signature td {
            text-align: center;
            vertical-align: top;
            width: 50%;
        }
        .signature-box {
            height: 60px;
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 9pt;
            color: #666;
        }
        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            .header {
                display: none !important;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body onload="window.print()">

    <!-- Kop surat dihapus sesuai permintaan -->

    <div class="content" style="margin-top: 20px;">
        <h3>SURAT PERNYATAAN BERLANGGANAN</h3>

        <p>Yang bertanda tangan di bawah ini:</p>

        <table class="table-data">
            <tr>
                <td>Nama Lengkap</td>
                <td>:</td>
                <td>{{ $data->nama }}</td>
            </tr>
            <tr>
                <td>NIK / No. KTP</td>
                <td>:</td>
                <td>{{ $data->no_ktp }}</td>
            </tr>
            <tr>
                <td>Alamat Pemasangan</td>
                <td>:</td>
                <td>{{ $data->alamat }}</td>
            </tr>
            <tr>
                <td>No. WhatsApp</td>
                <td>:</td>
                <td>{{ $data->no_wa }}</td>
            </tr>
            <tr>
                <td>Paket Layanan</td>
                <td>:</td>
                <td>{{ $data->nama_layanan ?? 'Paket Custom' }} ({{ $data->harga ? 'Rp ' . number_format($data->harga, 0, ',', '.') : '-' }})</td>
            </tr>
        </table>

        <div class="terms">
            <p>Dengan ini menyatakan setuju untuk berlangganan layanan internet dari <strong>{{ $settingWeb->nama_perusahaan ?? 'RBA SOLUTION' }}</strong> dengan ketentuan sebagai berikut:</p>
            <ol>
                <li>Bersedia berlangganan minimal selama <strong>6 (enam) bulan</strong> sejak tanggal pemasangan.</li>
                <li>Tidak akan menjual kembali (reselling) layanan internet kepada pihak lain tanpa izin resmi.</li>
                <li>Bersedia membayar tagihan bulanan tepat waktu sesuai dengan tanggal jatuh tempo.</li>
                <li>Apabila berhenti berlangganan sebelum masa kontrak 6 bulan berakhir, bersedia melunasi sisa tagihan hingga masa kontrak selesai.</li>
                <li>Menyetujui segala syarat dan ketentuan yang berlaku di {{ $settingWeb->nama_perusahaan ?? 'RBA SOLUTION' }}.</li>
            </ol>
        </div>

        <p>Demikian surat pernyataan ini saya buat dengan sadar dan tanpa paksaan dari pihak manapun.</p>

        <table class="signature">
            <tr>
                <td>
                    Mengetahui,<br>
                    <strong>{{ $settingWeb->nama_perusahaan ?? 'RBA SOLUTION' }}</strong>
                    <div class="signature-box"></div>
                    <br>
                    ( Admin )
                </td>
                <td>
                    {{ date('d F Y') }}<br>
                    Pelanggan,
                    <div class="signature-box">
                        <!-- Tempat Materai jika diperlukan -->
                        @if(isset($data->nama_layanan) && str_contains(strtolower($data->nama_layanan), 'lite'))
                        <div style="border: 1px dashed #ccc; width: 60px; height: 30px; margin: 25px auto; font-size: 8pt; line-height: 30px; color: #ccc;">Materai 10.000</div>
                        @endif
                    </div>
                    <br>
                    ( <strong>{{ $data->nama }}</strong> )
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
