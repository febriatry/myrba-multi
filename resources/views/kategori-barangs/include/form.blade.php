<div class="row mb-2">
    <div class="col-md-6">
        <div class="form-group">
            <label for="nama-kategori-barang">{{ __('Nama Kategori Barang') }}</label>
            <input type="text" name="nama_kategori_barang" id="nama-kategori-barang" class="form-control @error('nama_kategori_barang') is-invalid @enderror" value="{{ isset($kategoriBarang) ? $kategoriBarang->nama_kategori_barang : old('nama_kategori_barang') }}" placeholder="{{ __('Nama Kategori Barang') }}" required />
            @error('nama_kategori_barang')
                <span class="text-danger">
                    {{ $message }}
                </span>
            @enderror
        </div>
    </div>
</div>