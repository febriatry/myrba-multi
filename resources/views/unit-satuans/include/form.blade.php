<div class="row mb-2">
    <div class="col-md-6">
        <div class="form-group">
            <label for="nama-unit-satuan">{{ __('Nama Unit Satuan') }}</label>
            <input type="text" name="nama_unit_satuan" id="nama-unit-satuan" class="form-control @error('nama_unit_satuan') is-invalid @enderror" value="{{ isset($unitSatuan) ? $unitSatuan->nama_unit_satuan : old('nama_unit_satuan') }}" placeholder="{{ __('Nama Unit Satuan') }}" required />
            @error('nama_unit_satuan')
                <span class="text-danger">
                    {{ $message }}
                </span>
            @enderror
        </div>
    </div>
</div>