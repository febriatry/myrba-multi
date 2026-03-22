<div class="d-flex">
    {{-- Tombol Detail --}}
    <a href="{{ route('topups.show', $model->id) }}" class="btn btn-info btn-sm me-2">
        <i class="fa fa-eye"></i> Detail
    </a>

    {{-- Tombol Konfirmasi/Approve hanya untuk topup manual yang masih pending --}}
    @if ($model->status == 'pending' && $model->metode == 'manual' && auth()->user()->can('topup approval'))
        <button type="button" class="btn btn-success btn-sm me-2 btn-konfirmasi-topup" data-id="{{ $model->id }}"
            data-no-topup="{{ $model->no_topup }}" data-pelanggan="{{ $model->pelanggan->nama }}"
            data-nominal="{{ rupiah($model->nominal) }}">
            <i class="fa fa-check"></i> Konfirmasi
        </button>
    @endif

    {{-- Tombol Hapus untuk SEMUA topup yang masih pending --}}
    @can('topup delete')
        @if ($model->status == 'pending')
            <form action="{{ route('topups.destroy', $model->id) }}" method="POST" class="d-inline"
                id="delete-form-{{ $model->id }}">
                @csrf
                @method('DELETE')
                <button type="button" class="btn btn-sm btn-danger btn-delete-topup" data-id="{{ $model->id }}"
                    data-no-topup="{{ $model->no_topup }}">
                    <i class="fas fa-trash"></i> Hapus
                </button>
            </form>
        @endif
    @endcan
</div>
