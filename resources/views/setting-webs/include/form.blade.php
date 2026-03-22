<div class="row mb-2">
    <div class="col-md-6">
        <div class="form-group">
            <label for="nama-perusahaan">{{ __('Nama Perusahaan') }}</label>
            <input type="text" name="nama_perusahaan" id="nama-perusahaan"
                class="form-control @error('nama_perusahaan') is-invalid @enderror"
                value="{{ isset($settingWeb) ? $settingWeb->nama_perusahaan : old('nama_perusahaan') }}"
                placeholder="{{ __('Nama Perusahaan') }}" required />
            @error('nama_perusahaan')
                <span class="text-danger">
                    {{ $message }}
                </span>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="telepon-perusahaan">{{ __('Telepon Perusahaan') }}</label>
            <input type="text" name="telepon_perusahaan" id="telepon-perusahaan"
                class="form-control @error('telepon_perusahaan') is-invalid @enderror"
                value="{{ isset($settingWeb) ? $settingWeb->telepon_perusahaan : old('telepon_perusahaan') }}"
                placeholder="{{ __('Telepon Perusahaan') }}" required />
            @error('telepon_perusahaan')
                <span class="text-danger">
                    {{ $message }}
                </span>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="email">{{ __('Email') }}</label>
            <input type="email" name="email" id="email"
                class="form-control @error('email') is-invalid @enderror"
                value="{{ isset($settingWeb) ? $settingWeb->email : old('email') }}" placeholder="{{ __('Email') }}"
                required />
            @error('email')
                <span class="text-danger">
                    {{ $message }}
                </span>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="no-wa">{{ __('No Wa') }}</label>
            <input type="tel" name="no_wa" id="no-wa"
                class="form-control @error('no_wa') is-invalid @enderror"
                value="{{ isset($settingWeb) ? $settingWeb->no_wa : old('no_wa') }}" placeholder="{{ __('No Wa') }}"
                required />
            @error('no_wa')
                <span class="text-danger">
                    {{ $message }}
                </span>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="alamat">{{ __('Alamat') }}</label>
            <textarea name="alamat" id="alamat" class="form-control @error('alamat') is-invalid @enderror"
                placeholder="{{ __('Alamat') }}" required>{{ isset($settingWeb) ? $settingWeb->alamat : old('alamat') }}</textarea>
            @error('alamat')
                <span class="text-danger">
                    {{ $message }}
                </span>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="deskripsi-perusahaan">{{ __('Deskripsi Perusahaan') }}</label>
            <textarea name="deskripsi_perusahaan" id="deskripsi-perusahaan"
                class="form-control @error('deskripsi_perusahaan') is-invalid @enderror"
                placeholder="{{ __('Deskripsi Perusahaan') }}" required>{{ isset($settingWeb) ? $settingWeb->deskripsi_perusahaan : old('deskripsi_perusahaan') }}</textarea>
            @error('deskripsi_perusahaan')
                <span class="text-danger">
                    {{ $message }}
                </span>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="video-url-1">{{ __('Video URL 1') }}</label>
            <input type="url" name="video_url_1" id="video-url-1"
                class="form-control @error('video_url_1') is-invalid @enderror"
                value="{{ isset($settingWeb) ? $settingWeb->video_url_1 : old('video_url_1') }}"
                placeholder="{{ __('Video URL 1') }}" />
            @error('video_url_1')
                <span class="text-danger">
                    {{ $message }}
                </span>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="video-url-2">{{ __('Video URL 2') }}</label>
            <input type="url" name="video_url_2" id="video-url-2"
                class="form-control @error('video_url_2') is-invalid @enderror"
                value="{{ isset($settingWeb) ? $settingWeb->video_url_2 : old('video_url_2') }}"
                placeholder="{{ __('Video URL 2') }}" />
            @error('video_url_2')
                <span class="text-danger">
                    {{ $message }}
                </span>
            @enderror
        </div>
    </div>
    @isset($settingWeb)
        <div class="col-md-6">
            <div class="row">
                <div class="col-md-4 text-center">
                    @if ($settingWeb->logo == null)
                        <img src="https://via.placeholder.com/350?text=No+Image+Avaiable" alt="Logo"
                            class="rounded mb-2 mt-2" alt="Logo" width="200" height="150"
                            style="object-fit: cover">
                    @else
                        <img src="{{ asset('storage/uploads/logos/' . $settingWeb->logo) }}" alt="Logo"
                            class="rounded mb-2 mt-2" width="200" height="150" style="object-fit: cover">
                    @endif
                </div>

                <div class="col-md-8">
                    <div class="form-group ms-3">
                        <label for="logo">{{ __('Logo') }}</label>
                        <input type="file" name="logo" class="form-control @error('logo') is-invalid @enderror"
                            id="logo">

                        @error('logo')
                            <span class="text-danger">
                                {{ $message }}
                            </span>
                        @enderror
                        <div id="logoHelpBlock" class="form-text">
                            {{ __('Leave the logo blank if you don`t want to change it.') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="col-md-6">
            <div class="form-group">
                <label for="logo">{{ __('Logo') }}</label>
                <input type="file" name="logo" class="form-control @error('logo') is-invalid @enderror" id="logo"
                    required>

                @error('logo')
                    <span class="text-danger">
                        {{ $message }}
                    </span>
                @enderror
            </div>
        </div>
    @endisset
    <div class="col-md-6">
        <div class="form-group">
            <label for="url-tripay">{{ __('Url Tripay') }}</label>
            <input type="text" name="url_tripay" id="url-tripay"
                class="form-control @error('url_tripay') is-invalid @enderror"
                value="{{ isset($settingWeb) ? $settingWeb->url_tripay : old('url_tripay') }}"
                placeholder="{{ __('Url Tripay') }}" required />
            @error('url_tripay')
                <span class="text-danger">
                    {{ $message }}
                </span>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="api-key-tripay">{{ __('Api Key Tripay') }}</label>
            <input type="text" name="api_key_tripay" id="api-key-tripay"
                class="form-control @error('api_key_tripay') is-invalid @enderror"
                value="{{ isset($settingWeb) ? $settingWeb->api_key_tripay : old('api_key_tripay') }}"
                placeholder="{{ __('Api Key Tripay') }}" required />
            @error('api_key_tripay')
                <span class="text-danger">
                    {{ $message }}
                </span>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="api-key-ivosight">{{ __('Api Key Ivosight') }}</label>
            <input type="text" id="api-key-ivosight" class="form-control"
                value="{{ config('whatsapp.ivosight.api_key') }}"
                placeholder="Set di .env: IVOSIGHT_API_KEY=your_api_key" readonly />
            <div class="form-text">
                {{ __('Nilai ini berasal dari file .env dan tidak disimpan lewat form Setting Web.') }}
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="kode-merchant">{{ __('Kode Merchant') }}</label>
            <input type="text" name="kode_merchant" id="kode-merchant"
                class="form-control @error('kode_merchant') is-invalid @enderror"
                value="{{ isset($settingWeb) ? $settingWeb->kode_merchant : old('kode_merchant') }}"
                placeholder="{{ __('Kode Merchant') }}" required />
            @error('kode_merchant')
                <span class="text-danger">
                    {{ $message }}
                </span>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="private-key">{{ __('Private Key') }}</label>
            <input type="text" name="private_key" id="private-key"
                class="form-control @error('private_key') is-invalid @enderror"
                value="{{ isset($settingWeb) ? $settingWeb->private_key : old('private_key') }}"
                placeholder="{{ __('Private Key') }}" required />
            @error('private_key')
                <span class="text-danger">
                    {{ $message }}
                </span>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label" for="nominal_referal">{{ __('Nominal Referal') }}</label>
            <input type="number" class="form-control @error('nominal_referal') is-invalid @enderror"
                name="nominal_referal" id="nominal_referal" placeholder="{{ __('Nominal Referal') }}"
                value="{{ old('nominal_referal', $settingWeb->nominal_referal) }}" required />
            @error('nominal_referal')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>
</div>
