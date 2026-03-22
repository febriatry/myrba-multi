<table>
    <thead>
        <tr>
            <th colspan="9"><strong>Laporan Pergerakan Barang (Kartu Stok)</strong></th>
        </tr>
        <tr>
            <th colspan="9"><strong>Periode: {{ \Carbon\Carbon::parse($tanggalMulai)->format('d-m-Y') }} s/d
                    {{ \Carbon\Carbon::parse($tanggalSelesai)->format('d-m-Y') }}</strong></th>
        </tr>
        <tr></tr>
    </thead>
    <tbody>
        @foreach ($laporan as $item)
            @if ($item->is_header)
                <tr>
                    <td colspan="3"><strong>Nama Barang: {{ $item->nama_barang_header }}</strong></td>
                    <td colspan="3"><strong>Pemilik: {{ $item->owner_label ?? 'Kantor' }}</strong></td>
                    <td colspan="2" style="text-align: right;"><strong>Stock Awal:</strong></td>
                    <td style="text-align: right;"><strong>{{ $item->saldo_awal }}</strong></td>
                </tr>
                <tr>
                    <th
                        style="background-color: #EAEAEA; font-weight: bold; border: 1px solid #000; text-align: center;">
                        No.</th>
                    <th style="background-color: #EAEAEA; font-weight: bold; border: 1px solid #000;">Tanggal</th>
                    <th style="background-color: #EAEAEA; font-weight: bold; border: 1px solid #000;">Kode Transaksi</th>
                    <th style="background-color: #EAEAEA; font-weight: bold; border: 1px solid #000;">Keterangan</th>
                    <th style="background-color: #EAEAEA; font-weight: bold; border: 1px solid #000; text-align: right;">HPP/Unit</th>
                    <th style="background-color: #EAEAEA; font-weight: bold; border: 1px solid #000; text-align: right;">Harga/Unit</th>
                    <th
                        style="background-color: #EAEAEA; font-weight: bold; border: 1px solid #000; text-align: right;">
                        Masuk</th>
                    <th
                        style="background-color: #EAEAEA; font-weight: bold; border: 1px solid #000; text-align: right;">
                        Keluar</th>
                    <th
                        style="background-color: #EAEAEA; font-weight: bold; border: 1px solid #000; text-align: right;">
                        Stock Akhir</th>
                </tr>
            @else
                <tr>
                    <td style="border: 1px solid #000; text-align: center;">{{ (int) ($item->no ?? 0) }}</td>
                    <td style="border: 1px solid #000;">
                        {{ \Carbon\Carbon::parse($item->tanggal_transaksi)->format('d-m-Y') }}</td>
                    <td style="border: 1px solid #000;">{{ $item->kode_transaksi }}</td>
                    <td style="border: 1px solid #000;">{{ $item->keterangan }}</td>
                    <td style="border: 1px solid #000; text-align: right;">{{ (int) ($item->hpp_unit ?? 0) }}</td>
                    <td style="border: 1px solid #000; text-align: right;">{{ (int) ($item->harga_jual_unit ?? 0) }}</td>
                    <td style="border: 1px solid #000; text-align: right;">{{ $item->masuk > 0 ? $item->masuk : '' }}
                    </td>
                    <td style="border: 1px solid #000; text-align: right;">{{ $item->keluar > 0 ? $item->keluar : '' }}
                    </td>
                    <td style="border: 1px solid #000; text-align: right;">{{ $item->saldo_akhir }}</td>
                </tr>
            @endif
        @endforeach
    </tbody>
</table>
