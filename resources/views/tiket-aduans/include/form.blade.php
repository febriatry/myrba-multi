<div class="row mb-2">
    <div class="col-md-6">
        <div class="form-group">
            <label for="pelanggan-id">{{ __('Pelanggan') }}</label>
            <select class="form-select @error('pelanggan_id') is-invalid @enderror" name="pelanggan_id"
                id="pelanggan-id" class="form-control" required>
                <option value="" selected disabled>-- {{ __('Select pelanggan') }} --</option>
                @foreach ($pelanggans as $pelanggan)
                    <option value="{{ $pelanggan->id }}"
                        {{ isset($tiketAduan) && $tiketAduan->pelanggan_id == $pelanggan->id ? 'selected' : (old('pelanggan_id') == $pelanggan->id ? 'selected' : '') }}>
                        {{ $pelanggan->nama }}
                    </option>
                @endforeach
            </select>
            @error('pelanggan_id')
                <span class="text-danger">
                    {{ $message }}
                </span>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="deskripsi-aduan">{{ __('Deskripsi Aduan') }}</label>
            <textarea name="deskripsi_aduan" id="deskripsi-aduan"
                class="form-control @error('deskripsi_aduan') is-invalid @enderror" placeholder="{{ __('Deskripsi Aduan') }}"
                required>{{ isset($tiketAduan) ? $tiketAduan->deskripsi_aduan : old('deskripsi_aduan') }}</textarea>
            @error('deskripsi_aduan')
                <span class="text-danger">
                    {{ $message }}
                </span>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="tanggal-aduan">{{ __('Tanggal Aduan') }}</label>
            <input type="datetime-local" name="tanggal_aduan" id="tanggal-aduan"
                class="form-control @error('tanggal_aduan') is-invalid @enderror"
                value="{{ isset($tiketAduan) && $tiketAduan->tanggal_aduan ? $tiketAduan->tanggal_aduan->format('Y-m-d\TH:i') : old('tanggal_aduan') }}"
                placeholder="{{ __('Tanggal Aduan') }}" required />
            @error('tanggal_aduan')
                <span class="text-danger">
                    {{ $message }}
                </span>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="status">{{ __('Status') }}</label>
            <select class="form-select @error('status') is-invalid @enderror" name="status" id="status"
                class="form-control" required>
                <option value="" selected disabled>-- {{ __('Select status') }} --</option>
                <option value="Menunggu"
                    {{ isset($tiketAduan) && $tiketAduan->status == 'Menunggu' ? 'selected' : (old('status') == 'Menunggu' ? 'selected' : '') }}>
                    Menunggu</option>
                <option value="Diproses"
                    {{ isset($tiketAduan) && $tiketAduan->status == 'Diproses' ? 'selected' : (old('status') == 'Diproses' ? 'selected' : '') }}>
                    Diproses</option>
                <option value="Selesai"
                    {{ isset($tiketAduan) && $tiketAduan->status == 'Selesai' ? 'selected' : (old('status') == 'Selesai' ? 'selected' : '') }}>
                    Selesai</option>
                <option value="Dibatalkan"
                    {{ isset($tiketAduan) && $tiketAduan->status == 'Dibatalkan' ? 'selected' : (old('status') == 'Dibatalkan' ? 'selected' : '') }}>
                    Dibatalkan</option>
            </select>
            @error('status')
                <span class="text-danger">
                    {{ $message }}
                </span>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="prioritas">{{ __('Prioritas') }}</label>
            <select class="form-select @error('prioritas') is-invalid @enderror" name="prioritas" id="prioritas"
                class="form-control" required>
                <option value="" selected disabled>-- {{ __('Select prioritas') }} --</option>
                <option value="Rendah"
                    {{ isset($tiketAduan) && $tiketAduan->prioritas == 'Rendah' ? 'selected' : (old('prioritas') == 'Rendah' ? 'selected' : '') }}>
                    Rendah</option>
                <option value="Sedang"
                    {{ isset($tiketAduan) && $tiketAduan->prioritas == 'Sedang' ? 'selected' : (old('prioritas') == 'Sedang' ? 'selected' : '') }}>
                    Sedang</option>
                <option value="Tinggi"
                    {{ isset($tiketAduan) && $tiketAduan->prioritas == 'Tinggi' ? 'selected' : (old('prioritas') == 'Tinggi' ? 'selected' : '') }}>
                    Tinggi</option>
            </select>
            @error('prioritas')
                <span class="text-danger">
                    {{ $message }}
                </span>
            @enderror
        </div>
    </div>
    @isset($tiketAduan)
        <div class="col-md-6">
            <div class="row">
                <div class="col-md-4 text-center">
                    @if ($tiketAduan->lampiran == null)
                        <img src="https://dummyimage.com/350x350/cccccc/000000&text=No+Image" alt="Lampiran"
                            class="rounded mb-2 mt-2" alt="Lampiran" width="200" height="150"
                            style="object-fit: cover">
                    @else
                        <img src="{{ asset('storage/uploads/lampirans/' . $tiketAduan->lampiran) }}" alt="Lampiran"
                            class="rounded mb-2 mt-2" width="200" height="150" style="object-fit: cover">
                    @endif
                </div>

                <div class="col-md-8">
                    <div class="form-group ms-3">
                        <label for="lampiran">{{ __('Lampiran') }}</label>
                        <input type="file" name="lampiran" class="form-control @error('lampiran') is-invalid @enderror"
                            id="lampiran">

                        @error('lampiran')
                            <span class="text-danger">
                                {{ $message }}
                            </span>
                        @enderror
                        <div id="lampiranHelpBlock" class="form-text">
                            {{ __('Leave the lampiran blank if you don`t want to change it.') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="col-md-6">
            <div class="form-group">
                <label for="lampiran">{{ __('Lampiran') }}</label>
                <input type="file" name="lampiran" class="form-control @error('lampiran') is-invalid @enderror"
                    id="lampiran">

                @error('lampiran')
                    <span class="text-danger">
                        {{ $message }}
                    </span>
                @enderror
            </div>
        </div>
    @endisset
</div>
