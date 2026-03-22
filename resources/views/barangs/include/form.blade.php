<div class="row mb-2">
    <div class="col-md-6">
        <div class="form-group">
            <label for="kode-barang">{{ __('Kode Barang') }}</label>
            <input type="text" name="kode_barang" id="kode-barang"
                class="form-control @error('kode_barang') is-invalid @enderror"
                value="{{ isset($barang) ? $barang->kode_barang : old('kode_barang') }}"
                placeholder="{{ __('Kode Barang') }}" required />
            @error('kode_barang')
                <span class="text-danger">
                    {{ $message }}
                </span>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="nama-barang">{{ __('Nama Barang') }}</label>
            <input type="text" name="nama_barang" id="nama-barang"
                class="form-control @error('nama_barang') is-invalid @enderror"
                value="{{ isset($barang) ? $barang->nama_barang : old('nama_barang') }}"
                placeholder="{{ __('Nama Barang') }}" required />
            @error('nama_barang')
                <span class="text-danger">
                    {{ $message }}
                </span>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="unit-satuan-id">{{ __('Unit Satuan') }}</label>
            <select class="form-select @error('unit_satuan_id') is-invalid @enderror" name="unit_satuan_id"
                id="unit-satuan-id" class="form-control" required>
                <option value="" selected disabled>-- {{ __('Select unit satuan') }} --</option>

                @foreach ($unitSatuans as $unitSatuan)
                    <option value="{{ $unitSatuan->id }}"
                        {{ isset($barang) && $barang->unit_satuan_id == $unitSatuan->id ? 'selected' : (old('unit_satuan_id') == $unitSatuan->id ? 'selected' : '') }}>
                        {{ $unitSatuan->nama_unit_satuan }}
                    </option>
                @endforeach
            </select>
            @error('unit_satuan_id')
                <span class="text-danger">
                    {{ $message }}
                </span>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="kategori-barang-id">{{ __('Kategori Barang') }}</label>
            <select class="form-select @error('kategori_barang_id') is-invalid @enderror" name="kategori_barang_id"
                id="kategori-barang-id" class="form-control" required>
                <option value="" selected disabled>-- {{ __('Select kategori barang') }} --</option>

                @foreach ($kategoriBarangs as $kategoriBarang)
                    <option value="{{ $kategoriBarang->id }}"
                        {{ isset($barang) && $barang->kategori_barang_id == $kategoriBarang->id ? 'selected' : (old('kategori_barang_id') == $kategoriBarang->id ? 'selected' : '') }}>
                        {{ $kategoriBarang->nama_kategori_barang }}
                    </option>
                @endforeach
            </select>
            @error('kategori_barang_id')
                <span class="text-danger">
                    {{ $message }}
                </span>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="deskripsi-barang">{{ __('Deskripsi Barang') }}</label>
            <textarea name="deskripsi_barang" id="deskripsi-barang"
                class="form-control @error('deskripsi_barang') is-invalid @enderror" placeholder="{{ __('Deskripsi Barang') }}">{{ isset($barang) ? $barang->deskripsi_barang : old('deskripsi_barang') }}</textarea>
            @error('deskripsi_barang')
                <span class="text-danger">
                    {{ $message }}
                </span>
            @enderror
        </div>
    </div>
    @isset($barang)
        <div class="col-md-6">
            <div class="row">
                <div class="col-md-4 text-center">
                    @if ($barang->photo_barang == null)
                        <img src="https://via.placeholder.com/350?text=No+Image+Avaiable" alt="Photo Barang"
                            class="rounded mb-2 mt-2" alt="Photo Barang" width="200" height="150"
                            style="object-fit: cover">
                    @else
                        <img src="{{ asset('storage/uploads/photo_barangs/' . $barang->photo_barang) }}" alt="Photo Barang"
                            class="rounded mb-2 mt-2" width="200" height="150" style="object-fit: cover">
                    @endif
                </div>

                <div class="col-md-8">
                    <div class="form-group ms-3">
                        <label for="photo_barang">{{ __('Photo Barang') }}</label>
                        <input type="file" name="photo_barang"
                            class="form-control @error('photo_barang') is-invalid @enderror" id="photo_barang">

                        @error('photo_barang')
                            <span class="text-danger">
                                {{ $message }}
                            </span>
                        @enderror
                        <div id="photo_barangHelpBlock" class="form-text">
                            {{ __('Leave the photo barang blank if you don`t want to change it.') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="col-md-6">
            <div class="form-group">
                <label for="photo_barang">{{ __('Photo Barang') }}</label>
                <input type="file" name="photo_barang" class="form-control @error('photo_barang') is-invalid @enderror"
                    id="photo_barang">

                @error('photo_barang')
                    <span class="text-danger">
                        {{ $message }}
                    </span>
                @enderror
            </div>
        </div>
    @endisset
</div>
