<div class="row mb-2">
    <div class="col-md-6">
        <div class="form-group">
            <label for="judul">{{ __('Judul') }}</label>
            <input type="text" name="judul" id="judul" class="form-control @error('judul') is-invalid @enderror" value="{{ isset($informasiManagement) ? $informasiManagement->judul : old('judul') }}" placeholder="{{ __('Judul') }}" required />
            @error('judul')
                <span class="text-danger">
                    {{ $message }}
                </span>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="deskripsi">{{ __('Deskripsi') }}</label>
            <textarea name="deskripsi" id="deskripsi" class="form-control @error('deskripsi') is-invalid @enderror" placeholder="{{ __('Deskripsi') }}" required>{{ isset($informasiManagement) ? $informasiManagement->deskripsi : old('deskripsi') }}</textarea>
            @error('deskripsi')
                <span class="text-danger">
                    {{ $message }}
                </span>
            @enderror
        </div>
    </div>
    @isset($informasiManagement)
        <div class="col-md-6">
            <div class="row">
                <div class="col-md-4 text-center">
                    @if ($informasiManagement->thumbnail == null)
                        <img src="https://via.placeholder.com/350?text=No+Image+Avaiable" alt="Thumbnail" class="rounded mb-2 mt-2" alt="Thumbnail" width="200" height="150" style="object-fit: cover">
                    @else
                        <img src="{{ asset('storage/uploads/thumbnails/' . $informasiManagement->thumbnail) }}" alt="Thumbnail" class="rounded mb-2 mt-2" width="200" height="150" style="object-fit: cover">
                    @endif
                </div>

                <div class="col-md-8">
                    <div class="form-group ms-3">
                        <label for="thumbnail">{{ __('Thumbnail') }}</label>
                        <input type="file" name="thumbnail" class="form-control @error('thumbnail') is-invalid @enderror" id="thumbnail">

                        @error('thumbnail')
                          <span class="text-danger">
                                {{ $message }}
                           </span>
                        @enderror
                        <div id="thumbnailHelpBlock" class="form-text">
                            {{ __('Leave the thumbnail blank if you don`t want to change it.') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="col-md-6">
            <div class="form-group">
                <label for="thumbnail">{{ __('Thumbnail') }}</label>
                <input type="file" name="thumbnail" class="form-control @error('thumbnail') is-invalid @enderror" id="thumbnail" required>

                @error('thumbnail')
                   <span class="text-danger">
                        {{ $message }}
                    </span>
                @enderror
            </div>
        </div>
    @endisset
    <div class="col-md-6">
        <div class="form-group">
            <label for="is-aktif">{{ __('Is Aktif') }}</label>
            <select class="form-select @error('is_aktif') is-invalid @enderror" name="is_aktif" id="is-aktif" class="form-control" required>
                <option value="" selected disabled>-- {{ __('Select is aktif') }} --</option>
                <option value="Yes" {{ isset($informasiManagement) && $informasiManagement->is_aktif == 'Yes' ? 'selected' : (old('is_aktif') == 'Yes' ? 'selected' : '') }}>Yes</option>
		<option value="No" {{ isset($informasiManagement) && $informasiManagement->is_aktif == 'No' ? 'selected' : (old('is_aktif') == 'No' ? 'selected' : '') }}>No</option>			
            </select>
            @error('is_aktif')
                <span class="text-danger">
                    {{ $message }}
                </span>
            @enderror
        </div>
    </div>
</div>