<div class="d-flex">
    <a href="{{ route('transaksi-stock-in.show', $transaksi->id) }}" class="btn btn-info btn-sm me-1"><i
            class="fas fa-eye"></i></a>
    <a href="{{ route('transaksi-stock-in.edit', $transaksi->id) }}" class="btn btn-primary btn-sm me-1"><i
            class="fas fa-pencil-alt"></i></a>
    <a href="{{ route('transaksi-stock-in.exportItemPdf', $transaksi->id) }}" class="btn btn-secondary btn-sm me-1"
        title="Cetak PDF" target="_blank"><i class="fa fa-file-pdf"></i></a>
    <form action="{{ route('transaksi-stock-in.destroy', $transaksi->id) }}" method="POST"
        onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ini?');">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash-alt"></i></button>
    </form>
</div>
