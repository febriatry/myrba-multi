<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Transaksi Masuk - {{ $transaksi->kode_transaksi }}</title>
    <style>
        @page {
            margin: 25mm 20mm 25mm 20mm;
            size: a4 portrait;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11px;
            line-height: 1.4;
            color: #000;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 10px;
        }

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-footer-group;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        .table-header {
            border: none;
            margin-bottom: 5px;
            width: 100%;
        }

        .table-header td {
            border: none;
            vertical-align: middle;
            padding: 0;
        }

        .header-text-cell {
            text-align: center;
            vertical-align: middle;
        }

        .header-instansi {
            font-size: 15px;
            font-weight: bold;
            line-height: 1.2;
            text-transform: uppercase;
        }

        .header-address,
        .header-contact {
            font-size: 11px;
            color: #333;
            line-height: 1.3;
        }

        hr.header-line {
            border: none;
            border-top: 2px solid #000;
            margin: 5px 0 15px 0;
        }

        .creator-info {
            font-size: 9px;
            color: #555;
            margin-bottom: 15px;
            text-align: right;
        }

        .doc-title {
            text-align: center;
            font-size: 13px;
            margin-bottom: 20px;
            font-weight: bold;
            text-transform: uppercase;
            text-decoration: underline;
        }

        .transaction-header-table td {
            border: none;
            padding: 2px 0;
            vertical-align: top;
        }

        .transaction-header-table td.label {
            font-weight: normal;
            width: 100px;
        }

        .details-table th {
            background-color: #EAEAEA;
            font-weight: bold;
            text-align: center;
            padding: 4px 5px;
            border: 1px solid #333;
        }

        .details-table td {
            padding: 4px 5px;
            border: 1px solid #333;
            vertical-align: middle;
        }

        .details-table td.text-center {
            text-align: center;
        }

        .details-table td.text-right {
            text-align: right;
        }

        .signature-section {
            page-break-inside: avoid;
            margin-top: 40px;
            float: right;
            width: 220px;
            text-align: center;
        }

        .signature-place-date {
            margin-bottom: 60px;
        }

        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <table class="table-header">
        <tr>
            <td class="header-text-cell">
                <div class="header-instansi">{{ $transaksi->router?->nama_perusahaan ?? 'NAMA PERUSAHAAN' }}</div>
                <div class="header-address">
                    {{ $transaksi->router?->alamat ?? 'Alamat Perusahaan' }}<br>
                    <span class="header-contact">
                        @if ($transaksi->router?->no_hp)
                            Telepon: {{ $transaksi->router->no_hp }}
                        @endif
                        @if ($transaksi->router?->no_hp && $transaksi->router?->email)
                            |
                        @endif
                        @if ($transaksi->router?->email)
                            Email: {{ $transaksi->router->email }}
                        @endif
                    </span>
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="padding:0;">
                <hr class="header-line">
            </td>
        </tr>
    </table>

    <div class="creator-info">Dicetak oleh: {{ $namaPembuat }} pada {{ now()->format('d-m-Y H:i') }}</div>
    <div class="doc-title">BUKTI PENERIMAAN BARANG</div>

    <table class="transaction-header-table">
        <tr>
            <td class="label">No. Transaksi</td>
            <td>: {{ $transaksi->kode_transaksi }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal</td>
            <td>: {{ $transaksi->tanggal_transaksi->format('d F Y') }}</td>
        </tr>
        <tr>
            <td class="label">Keterangan</td>
            <td>: {{ $transaksi->keterangan ?? '-' }}</td>
        </tr>
    </table>

    <table class="details-table">
        <thead>
            <tr>
                <th style="width:5%;">No</th>
                <th style="width:55%;">Nama Barang</th>
                <th style="width:20%;">Jumlah</th>
                <th style="width:20%;">Satuan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transaksi->details as $index => $detail)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $detail->barang?->nama_barang ?? 'N/A' }}</td>
                    <td class="text-center">{{ $detail->jumlah }}</td>
                    <td class="text-center">{{ $detail->barang?->unit_satuan?->nama_unit_satuan ?? 'N/A' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center">Tidak ada detail barang.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="signature-section">
        <div class="signature-place-date">
            {{ $transaksi->router?->kota ?? 'Kota' }}, {{ $transaksi->tanggal_transaksi->format('d F Y') }}
        </div>
        <div class="signature-name">{{ $transaksi->user->name }}</div>
        <div>Penerima</div>
    </div>
</body>

</html>
