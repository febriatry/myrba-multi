<td>
    @can('pemasukan view')
    <a href="{{ route('pemasukans.show', $model->id) }}" class="btn btn-outline-success btn-sm">
        <i class="fa fa-eye"></i>
    </a>
    @endcan

    @can('pemasukan edit')
        <a href="{{ route('pemasukans.edit', $model->id) }}" class="btn btn-outline-primary btn-sm">
            <i class="fa fa-pencil-alt"></i>
        </a>
    @endcan

    @can('pemasukan delete')
        @php
            $isLockedPemasukan = (($model->tagihan_status_bayar ?? null) === 'Sudah Bayar') &&
                (!empty($model->tagihan_tanggal_review) || !empty($model->tagihan_reviewed_by));
            $canForceDeletePemasukan = auth()->user()?->can('pemasukan protected delete');
        @endphp
        @if ($isLockedPemasukan && !$canForceDeletePemasukan)
            <button class="btn btn-outline-danger btn-sm" disabled title="Perlu izin khusus untuk hapus pemasukan tervalidasi">
                <i class="ace-icon fa fa-lock"></i>
            </button>
        @else
            <form action="{{ route('pemasukans.destroy', $model->id) }}" method="post" class="d-inline"
                onsubmit="return confirm('Are you sure to delete this record?')">
                @csrf
                @method('delete')
                <button class="btn btn-outline-danger btn-sm">
                    <i class="ace-icon fa fa-trash-alt"></i>
                </button>
            </form>
        @endif
    @endcan
</td>
