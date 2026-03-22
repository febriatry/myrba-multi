<div class="d-flex">
    <a href="{{ route('withdraws.show', $model->id) }}" class="btn btn-outline-info btn-sm me-1" title="Lihat Detail">
        <i class="fas fa-eye"></i>
    </a>

    @if ($model->status == 'Pending')
        @can('withdraw approval')
            <button type="button" class="btn btn-outline-success btn-sm me-1 approval-btn" data-bs-toggle="modal"
                data-bs-target="#approvalModal" data-id="{{ $model->id }}"
                data-pelanggan="{{ $model->pelanggan_nama }} ({{ $model->pelanggan_no_layanan }})"
                data-nominal="{{ rupiah($model->nominal_wd) }}" title="Proses Approval">
                <i class="fas fa-check-circle"></i>
            </button>
        @endcan

        @can('withdraw edit')
            <a href="{{ route('withdraws.edit', $model->id) }}" class="btn btn-outline-primary btn-sm me-1" title="Edit">
                <i class="fas fa-pencil-alt"></i>
            </a>
        @endcan

        @can('withdraw delete')
            <form action="{{ route('withdraws.destroy', $model->id) }}" method="post" class="d-inline"
                onsubmit="return confirm('Anda yakin ingin menghapus data ini?')">
                @csrf
                @method('delete')
                <button class="btn btn-outline-danger btn-sm" title="Hapus">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </form>
        @endcan
    @endif
</div>
