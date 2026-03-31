<!DOCTYPE html>
<html lang="en">

@php
    $settingWeb = DB::table('setting_web')->first();
@endphp

<head>
    <meta charset="utf-8">
    <title>Invoice Internet {{ $settingWeb->nama_perusahaan }}</title>
</head>
<style>
    @page { margin: 0; margin-right: 1pt; }
    body {
        width: 55mm;
        margin: 0 auto;
        padding: 1mm 1mm 0 1mm;
        color: #000;
        background: #fff;
        font-family: "Courier New", monospace;
        font-size: 8px;
        line-height: 1.2;
        word-break: break-word;
    }
    .center { text-align: center; }
    .right { text-align: right; }
    .bold { font-weight: bold; }
    .separator { border-top: 1px dashed #000; margin: 3px 0; }
    .small { font-size: 7px; }
    .invoice-header { position: relative; }
    .stamp-paid {
        position: absolute;
        right: 0;
        top: -2mm;
        border: 0.5mm solid #e74c3c;
        color: #e74c3c;
        font-weight: bold;
        padding: 1mm 2mm;
        transform: rotate(-12deg);
        border-radius: 1mm;
        font-size: 9px;
    }
    .table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }
    .table th, .table td {
        padding: 2px 0;
        word-break: break-word;
    }
    .table th {
        border-bottom: 1px dashed #000;
        font-weight: bold;
    }
    .table thead th:nth-child(1) { width: 44%; }
    .table thead th:nth-child(2) { width: 8%; }
    .table thead th:nth-child(3) { width: 24%; }
    .table thead th:nth-child(4) { width: 24%; }
    .table tfoot td { padding-top: 3px; }
    img.logo { max-width: 20mm; height: auto; }
</style>

<body>
    <header class="center">
        <div class="bold">{{ $settingWeb->nama_perusahaan }}</div>
        <div class="small">{{ $settingWeb->telepon_perusahaan }} | {{ $settingWeb->email }}</div>
    </header>
    <div class="separator"></div>
    <div class="invoice-header">
        <div class="bold">INVOICE {{ $data->no_tagihan }}</div>
        @if ($data->status_bayar === 'Sudah Bayar')
        <div class="stamp-paid">LUNAS</div>
        @endif
        <div>Tanggal: {{ date('Y-m-d', strtotime($data->tanggal_create_tagihan)) }}</div>
        @php
            $tgl1 = date('Y-m-d', strtotime($data->tanggal_create_tagihan));
            $tgl2 = date('Y-m-d', strtotime('+7 days', strtotime($tgl1)));
        @endphp
        <div>Jatuh Tempo: {{ $tgl2 }}</div>
    </div>
    <div class="separator"></div>
    <div>
        <div class="bold">Kepada:</div>
        <div>{{ $data->nama }}</div>
        <div class="small">{{ $data->alamat_customer }}</div>
        <div class="small">{{ $data->email_customer }}</div>
        <div>No Layanan: {{ formatNoLayananTenant($data->no_layanan, (int) ($data->tenant_id ?? (auth()->user()->tenant_id ?? 0))) }}</div>
    </div>
    <div class="separator"></div>
    <table class="table">
        <thead>
            <tr>
                <th class="left">Deskripsi</th>
                <th class="center">Qty</th>
                <th class="right">Harga</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Internet {{ $settingWeb->nama_perusahaan }} - {{ $data->nama_layanan }}</td>
                <td class="center">1</td>
                <td class="right">{{ rupiah($data->nominal_bayar - $data->potongan_bayar) }}</td>
                <td class="right">{{ rupiah($data->nominal_bayar - $data->potongan_bayar) }}</td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="right">Subtotal</td>
                <td class="right">{{ rupiah($data->nominal_bayar - $data->potongan_bayar) }}</td>
            </tr>
            @if ($data->ppn == 'Yes')
            <tr>
                <td colspan="3" class="right">PPN 11%</td>
                <td class="right">{{ rupiah($data->nominal_ppn) }}</td>
            </tr>
            @endif
            <tr>
                <td colspan="3" class="right bold">Grand Total</td>
                <td class="right bold">{{ rupiah($data->total_bayar) }}</td>
            </tr>
        </tfoot>
    </table>
    <div class="separator"></div>
    <div class="center small">Selalu cek tagihan Anda di myrba.net dan masukkan nomor ID Anda</div>
    <div class="center small">Invoice dihasilkan otomatis dan sah tanpa tanda tangan</div>
    <div class="center small">Terima kasih</div>
</body>

</html>
