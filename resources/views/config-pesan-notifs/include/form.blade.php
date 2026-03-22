<div class="row mb-2">
    {{-- Pesan Pendaftaran --}}
    <div class="col-md-6">
        <div class="form-group">
            <label for="pesan-notif-pendaftaran">{{ __('Pesan Notif Pendaftaran') }}</label>
            <small class="form-text text-muted mb-1">
                Pesan ini akan dikirim saat pelanggan mendaftar layanan baru.
            </small>
            <textarea name="pesan_notif_pendaftaran" id="pesan-notif-pendaftaran" rows="18" class="form-control @error('pesan_notif_pendaftaran') is-invalid @enderror" placeholder="{{ __('Pesan Notif Pendaftaran') }}" required>{{ isset($configPesanNotif) ? $configPesanNotif->pesan_notif_pendaftaran : old('pesan_notif_pendaftaran') }}</textarea>
            @error('pesan_notif_pendaftaran')
                <span class="text-danger">{{ $message }}</span>
            @enderror
            <small class="text-muted d-block mt-1">
                Placeholder yang dapat digunakan:<br>
                <code>{nama_pelanggan}</code>, <code>{alamat}</code>, <code>{paket_layanan}</code>, <code>{no_layanan}</code>, <code>{no_wa}</code>, <code>{email}</code>, <code>{nama_admin}</code>, <code>{nama_perusahaan}</code>
            </small>
        </div>
    </div>

    {{-- Pesan Tagihan --}}
    <div class="col-md-6">
        <div class="form-group">
            <label for="pesan-notif-tagihan">{{ __('Pesan Notif Tagihan') }}</label>
            <small class="form-text text-muted mb-1">
                Pesan ini akan dikirim saat tagihan baru dibuat untuk pelanggan.
            </small>
            <textarea name="pesan_notif_tagihan" id="pesan-notif-tagihan" rows="18" class="form-control @error('pesan_notif_tagihan') is-invalid @enderror" placeholder="{{ __('Pesan Notif Tagihan') }}" required>{{ isset($configPesanNotif) ? $configPesanNotif->pesan_notif_tagihan : old('pesan_notif_tagihan') }}</textarea>
            @error('pesan_notif_tagihan')
                <span class="text-danger">{{ $message }}</span>
            @enderror
            <small class="text-muted d-block mt-1">
                Placeholder yang dapat digunakan:<br>
                <code>{nama_perusahaan}</code>, <code>{nama_pelanggan}</code>, <code>{periode}</code>, <code>{no_layanan}</code>, <code>{total_bayar}</code>, <code>{tanggal_jatuh_tempo}</code>
            </small>
        </div>
    </div>

    {{-- Pesan Pembayaran --}}
    <div class="col-md-6">
        <div class="form-group">
            <label for="pesan-notif-pembayaran">{{ __('Pesan Notif Pembayaran') }}</label>
            <small class="form-text text-muted mb-1">
                Pesan ini dikirim sebagai bukti bahwa pembayaran pelanggan telah diterima.
            </small>
            <textarea name="pesan_notif_pembayaran" id="pesan-notif-pembayaran" rows="18" class="form-control @error('pesan_notif_pembayaran') is-invalid @enderror" placeholder="{{ __('Pesan Notif Pembayaran') }}" required>{{ isset($configPesanNotif) ? $configPesanNotif->pesan_notif_pembayaran : old('pesan_notif_pembayaran') }}</textarea>
            @error('pesan_notif_pembayaran')
                <span class="text-danger">{{ $message }}</span>
            @enderror
            <small class="text-muted d-block mt-1">
                Placeholder yang dapat digunakan:<br>
                <code>{nama_pelanggan}</code>, <code>{no_layanan}</code>, <code>{no_tagihan}</code>, <code>{nominal}</code>, <code>{metode_bayar}</code>, <code>{tanggal_bayar}</code>, <code>{link_invoice}</code>
            </small>
        </div>
    </div>

    {{-- Pesan Kirim Invoice --}}
    <div class="col-md-6">
        <div class="form-group">
            <label for="pesan-notif-kirim-invoice">{{ __('Pesan Notif Kirim Invoice') }}</label>
            <small class="form-text text-muted mb-1">
                Pesan ini dikirim saat invoice dikirim ulang ke pelanggan.
            </small>
            <textarea name="pesan_notif_kirim_invoice" id="pesan-notif-kirim-invoice" rows="18" class="form-control @error('pesan_notif_kirim_invoice') is-invalid @enderror" placeholder="{{ __('Pesan Notif Kirim Invoice') }}" required>{{ isset($configPesanNotif) ? $configPesanNotif->pesan_notif_kirim_invoice : old('pesan_notif_kirim_invoice') }}</textarea>
            @error('pesan_notif_kirim_invoice')
                <span class="text-danger">{{ $message }}</span>
            @enderror
            <small class="text-muted d-block mt-1">
                Placeholder yang dapat digunakan:<br>
                <code>{nama_pelanggan}</code>, <code>{no_layanan}</code>, <code>{no_tagihan}</code>, <code>{nominal}</code>, <code>{metode_bayar}</code>, <code>{tanggal_bayar}</code>, <code>{link_invoice}</code>
            </small>
        </div>
    </div>
</div>
