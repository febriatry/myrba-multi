<div class="row mb-2">
    <div class="col-md-6">
        <div class="form-group">
            <label for="pelanggan-id">{{ __('Pelanggan') }}</label>
            <select class="form-select @error('pelanggan_id') is-invalid @enderror" name="pelanggan_id" id="pelanggan-id"
                required>
                <option value="" selected disabled>-- {{ __('Pilih pelanggan') }} --</option>
                @foreach ($pelanggans as $pelanggan)
                    <option value="{{ $pelanggan->id }}" data-balance="{{ $pelanggan->balance }}"
                        {{ isset($withdraw) && $withdraw->pelanggan_id == $pelanggan->id ? 'selected' : (old('pelanggan_id') == $pelanggan->id ? 'selected' : '') }}>
                        {{ $pelanggan->nama }} ({{ $pelanggan->no_layanan }}) - Saldo: {{ rupiah($pelanggan->balance) }}
                    </option>
                @endforeach
            </select>
            @error('pelanggan_id')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="nominal-wd">{{ __('Nominal Withdraw') }}</label>
            <input type="number" name="nominal_wd" id="nominal-wd"
                class="form-control @error('nominal_wd') is-invalid @enderror"
                value="{{ isset($withdraw) ? $withdraw->nominal_wd : old('nominal_wd') }}"
                placeholder="{{ __('Masukkan nominal') }}" required />
            @error('nominal_wd')
                <span class="text-danger">{{ $message }}</span>
            @enderror
            <small id="max-wd-info" class="form-text text-muted"></small>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="tanggal-wd">{{ __('Tanggal Withdraw') }}</label>
            <input type="datetime-local" name="tanggal_wd" id="tanggal-wd"
                class="form-control @error('tanggal_wd') is-invalid @enderror"
                value="{{ isset($withdraw) && $withdraw->tanggal_wd ? $withdraw->tanggal_wd->format('Y-m-d\TH:i') : old('tanggal_wd', now()->format('Y-m-d\TH:i')) }}"
                required />
            @error('tanggal_wd')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>

@push('js')
    <script>
        $(document).ready(function() {
            $('#pelanggan-id').select2({
                theme: "bootstrap-5",
                placeholder: "-- {{ __('Pilih pelanggan') }} --"
            });

            function updateMaxWdInfo() {
                var selectedOption = $('#pelanggan-id').find('option:selected');
                var balance = selectedOption.data('balance');
                var inputNominal = $('#nominal-wd');

                if (balance !== undefined) {
                    var formattedBalance = new Intl.NumberFormat('id-ID', {
                        style: 'currency',
                        currency: 'IDR'
                    }).format(balance);
                    $('#max-wd-info').text('Saldo tersedia: ' + formattedBalance);
                    inputNominal.attr('max', balance);
                } else {
                    $('#max-wd-info').text('');
                    inputNominal.removeAttr('max');
                }
            }

            $('#pelanggan-id').on('change', function() {
                updateMaxWdInfo();
            });

            // Panggil saat halaman dimuat jika ada data (untuk form edit)
            if ($('#pelanggan-id').val()) {
                updateMaxWdInfo();
            }
        });
    </script>
    <script>
        // Menangani form submission untuk mencegah double click
        $('form').on('submit', function(e) {
            // Menonaktifkan tombol simpan
            $('#saveBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>  Menyimpan...');
        });
    </script>
@endpush
