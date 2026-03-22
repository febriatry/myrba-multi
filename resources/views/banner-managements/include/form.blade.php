<div class="row mb-2">
    @isset($bannerManagement)
        <div class="col-md-6">
            <div class="row">
                <div class="col-md-4 text-center">
                    @if ($bannerManagement->file_banner == null)
                        <img src="https://via.placeholder.com/350?text=No+Image+Avaiable" alt="File Banner" class="rounded mb-2 mt-2" alt="File Banner" width="200" height="150" style="object-fit: cover">
                    @else
                        <img src="{{ asset('storage/uploads/file_banners/' . $bannerManagement->file_banner) }}" alt="File Banner" class="rounded mb-2 mt-2" width="200" height="150" style="object-fit: cover">
                    @endif
                </div>

                <div class="col-md-8">
                    <div class="form-group ms-3">
                        <label for="file_banner">{{ __('File Banner') }}</label>
                        <input type="file" name="file_banner" class="form-control @error('file_banner') is-invalid @enderror" id="file_banner">

                        @error('file_banner')
                          <span class="text-danger">
                                {{ $message }}
                           </span>
                        @enderror
                        <div id="file_bannerHelpBlock" class="form-text">
                            {{ __('Leave the file banner blank if you don`t want to change it.') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="col-md-6">
            <div class="form-group">
                <label for="file_banner">{{ __('File Banner') }}</label>
                <input type="file" name="file_banner" class="form-control @error('file_banner') is-invalid @enderror" id="file_banner" required>

                @error('file_banner')
                   <span class="text-danger">
                        {{ $message }}
                    </span>
                @enderror
            </div>
        </div>
    @endisset
    <div class="col-md-6">
        <div class="form-group">
            <label for="urutan">{{ __('Urutan') }}</label>
            <input type="number" name="urutan" id="urutan" class="form-control @error('urutan') is-invalid @enderror" value="{{ isset($bannerManagement) ? $bannerManagement->urutan : old('urutan') }}" placeholder="{{ __('Urutan') }}" required />
            @error('urutan')
                <span class="text-danger">
                    {{ $message }}
                </span>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="is-aktif">{{ __('Is Aktif') }}</label>
            <select class="form-select @error('is_aktif') is-invalid @enderror" name="is_aktif" id="is-aktif" class="form-control" required>
                <option value="" selected disabled>-- {{ __('Select is aktif') }} --</option>
                <option value="Yes" {{ isset($bannerManagement) && $bannerManagement->is_aktif == 'Yes' ? 'selected' : (old('is_aktif') == 'Yes' ? 'selected' : '') }}>Yes</option>
		<option value="No" {{ isset($bannerManagement) && $bannerManagement->is_aktif == 'No' ? 'selected' : (old('is_aktif') == 'No' ? 'selected' : '') }}>No</option>			
            </select>
            @error('is_aktif')
                <span class="text-danger">
                    {{ $message }}
                </span>
            @enderror
        </div>
    </div>
</div>