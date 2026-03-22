<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Laporan Keuangan & Tagihan</title>
    <style>
        @page {
            margin: 25mm 20mm;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12px;
            line-height: 1.4;
            color: #000;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 10px;
            page-break-inside: auto;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        thead {
            display: table-header-group;
        }

        th,
        td {
            padding: 4px 6px;
            text-align: left;
            vertical-align: top;
            word-wrap: break-word;
        }

        /* --- Header Styling (Original/Arial) --- */
        .table-header {
            border: none;
            margin-bottom: 5px;
            font-size: 13px;
            /* Ukuran default header */
        }

        .table-header td {
            border: none;
            vertical-align: middle;
            /* Tengahkan vertikal */
            padding: 0 5px;
        }

        .logo-cell {
            width: 80px;
            text-align: right;
        }

        .logo {
            width: 150px;
            height: auto;
        }

        .header-text-cell {
            text-align: center;
            font-size: 14px;
        }

        /* Ukuran teks alamat */
        .header-instansi {
            font-size: 15px;
            font-weight: bold;
            line-height: 1.2;
        }

        /* Nama instansi */
        .header-address {
            font-size: 13px;
            color: #333;
            line-height: 1.3;
            margin-top: 2px;
        }

        hr.header-line {
            border: none;
            border-top: 2px solid #000;
            margin: 5px 0 15px 0;
        }

        .creator-info {
            font-size: 10px;
            color: #555;
            margin-bottom: 15px;
        }

        .doc-title {
            text-align: center;
            font-size: 14px;
            margin-bottom: 20px;
            line-height: 1.3;
        }

        .doc-title b {
            font-weight: bold;
            text-transform: uppercase;
        }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 8px;
            background-color: #f2f2f2;
            padding: 5px;
            border-left: 3px solid #333;
        }

        .sub-section-title {
            font-size: 12px;
            font-weight: bold;
            margin-top: 15px;
            margin-bottom: 8px;
            padding-left: 10px;
        }

        .table-bordered th,
        .table-bordered td {
            border: 1px solid #333;
            font-size: 10px;
        }

        .table-bordered thead th {
            background-color: #EAEAEA;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
        }

        .summary-box {
            margin-left: 15px;
            margin-bottom: 15px;
        }

        .summary-box p {
            margin: 0 0 4px 0;
        }

        .summary-box ul {
            padding-left: 20px;
            margin-top: 5px;
        }

        .summary-box li {
            margin-bottom: 3px;
        }

        .summary-table {
            width: 60%;
            /* Dibuat lebih lebar */
            margin-top: 5px;
        }

        .summary-table td {
            border: 1px solid #333;
        }

        .total-row td {
            font-weight: bold;
            background-color: #f8f8f8;
        }

        .grand-total-box {
            margin-top: 20px;
            padding: 10px;
            border: 1px solid #155724;
            background-color: #d4edda;
            text-align: center;
        }

        .grand-total-box b {
            font-size: 14px;
            color: #155724;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }
    </style>
</head>

<body>
    <table class="table-header">
        <tr>
            <td class="logo-cell">
                @if ($logoUrl)
                    <img src="{{ $logoUrl }}" alt="Logo" class="logo">
                @endif
            </td>
            <td class="header-text-cell">
                <div class="header-instansi">{{ $settingWeb->nama_perusahaan ?? 'NAMA PERUSAHAAN' }}</div>
                <div class="header-address">
                    {{ $settingWeb->alamat ?? 'Alamat Perusahaan' }}<br>
                    Telepon {{ $settingWeb->telepon_perusahaan ?? '-' }} | Email: {{ $settingWeb->email ?? '-' }}
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="padding: 0;">
                <hr class="header-line">
            </td>
        </tr>
    </table>

    <div class="creator-info">Dokumen dibuat oleh: {{ $namaPembuat }} pada {{ $tanggalCetak }}</div>

    <div class="doc-title">
        <b>Laporan Tagihan & Keuangan</b><br>
        Periode {{ \Carbon\Carbon::parse($start)->translatedFormat('d F Y') }} s.d.
        {{ \Carbon\Carbon::parse($end)->translatedFormat('d F Y') }}
    </div>

    <div class="section-title">LAPORAN TAGIHAN</div>

    <div class="sub-section-title">1. Tagihan Sudah Bayar</div>
    <div class="summary-box">
        <p><b>Total:</b> {{ $totalTagihanLunas }} Tagihan</p>
        <p><b>Nominal:</b> {{ rupiah($nominalTagihanLunas) }}</p>
    </div>

    <div class="sub-section-title">1.1 Detail Pembayaran Periode</div>
    <div class="summary-box">
        <ul>
            <li>Cash: {{ rupiah($detailTagihanPeriode['Cash']) }}</li>
            <li>Payment Tripay: {{ rupiah($detailTagihanPeriode['Payment Tripay']) }}</li>
            <li>Transfer Bank: {{ rupiah(collect($detailTagihanPeriode['Transfer Bank'])->sum()) }}
                @if (!empty($detailTagihanPeriode['Transfer Bank']))
                    <ul>
                        @foreach ($detailTagihanPeriode['Transfer Bank'] as $bank => $total)
                            <li>{{ $bank }}: {{ rupiah($total) }}</li>
                        @endforeach
                    </ul>
                @endif
            </li>
        </ul>
    </div>

    <div class="sub-section-title">2. Tagihan Belum Bayar</div>
    <div class="summary-box">
        <p><b>Total:</b> {{ $totalTagihanBelumLunas }} Tagihan</p>
        <p><b>Nominal:</b> {{ rupiah($nominalTagihanBelumLunas) }}</p>
    </div>


    <div class="section-title">LAPORAN KEUANGAN</div>

    <div class="sub-section-title">1. Pemasukan: {{ rupiah($totalPemasukan) }}</div>
    <div class="summary-box">
        <b>Berdasarkan Kategori:</b>
        <table class="table-bordered summary-table">
            <thead>
                <tr>
                    <th>Kategori</th>
                    <th>Total Transaksi</th>
                    <th>Nominal</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pemasukanPerKategori as $item)
                    <tr>
                        <td>{{ $item->kategori }}</td>
                        <td class="text-center">{{ $item->total_transaksi }}</td>
                        <td class="text-right">{{ rupiah($item->total_nominal) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center">Tidak ada data.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot class="total-row">
                <tr>
                    <td>Total</td>
                    <td class="text-center">{{ $pemasukanPerKategori->sum('total_transaksi') }}</td>
                    <td class="text-right">{{ rupiah($pemasukanPerKategori->sum('total_nominal')) }}</td>
                </tr>
            </tfoot>
        </table>

        <b style="display:block; margin-top:10px;">Berdasarkan Metode Bayar:</b>
        <table class="table-bordered summary-table">
            <thead>
                <tr>
                    <th>Metode Bayar</th>
                    <th>Total Transaksi</th>
                    <th>Nominal</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pemasukanPerMetode as $item)
                    <tr>
                        <td>{{ $item->metode_bayar }}</td>
                        <td class="text-center">{{ $item->total_transaksi }}</td>
                        <td class="text-right">{{ rupiah($item->total_nominal) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center">Tidak ada data.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot class="total-row">
                <tr>
                    <td>Total</td>
                    <td class="text-center">{{ $pemasukanPerMetode->sum('total_transaksi') }}</td>
                    <td class="text-right">{{ rupiah($pemasukanPerMetode->sum('total_nominal')) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="sub-section-title">2. Pengeluaran: {{ rupiah($totalPengeluaran) }}</div>
    <div class="summary-box">
        <table class="table-bordered summary-table">
            <thead>
                <tr>
                    <th>Kategori</th>
                    <th>Total Transaksi</th>
                    <th>Nominal</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pengeluaranPerKategori as $item)
                    <tr>
                        <td>{{ $item->kategori }}</td>
                        <td class="text-center">{{ $item->total_transaksi }}</td>
                        <td class="text-right">{{ rupiah($item->total_nominal) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center">Tidak ada data.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot class="total-row">
                <tr>
                    <td>Total</td>
                    <td class="text-center">{{ $pengeluaranPerKategori->sum('total_transaksi') }}</td>
                    <td class="text-right">{{ rupiah($pengeluaranPerKategori->sum('total_nominal')) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="grand-total-box">
        <b>3. Sisa Hasil Pendapatan : {{ rupiah($sisaHasil) }}</b>
    </div>
</body>

</html>
