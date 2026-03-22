<div class="row">
    {{-- Kolom Kiri: Informasi Dasar --}}
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="form-group">
                    <label for="kode_transaksi">Kode Transaksi</label>
                    <input type="text" name="kode_transaksi" id="kode_transaksi"
                        class="form-control @error('kode_transaksi') is-invalid @enderror"
                        value="{{ old('kode_transaksi', $transaksi->kode_transaksi ?? 'TR-' . strtoupper($type) . '-' . date('YmdHis')) }}"
                        readonly>
                    @error('kode_transaksi')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="tanggal_transaksi">Tanggal Transaksi</label>
                    <input type="date" name="tanggal_transaksi" id="tanggal_transaksi"
                        class="form-control @error('tanggal_transaksi') is-invalid @enderror"
                        value="{{ old('tanggal_transaksi', isset($transaksi) ? $transaksi->tanggal_transaksi->format('Y-m-d') : date('Y-m-d')) }}"
                        required>
                    @error('tanggal_transaksi')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="keterangan">Keterangan</label>
                    <textarea name="keterangan" id="keterangan" class="form-control" rows="3">{{ old('keterangan', $transaksi->keterangan ?? '') }}</textarea>
                </div>
                @if (($type ?? 'in') === 'out')
                    <div class="form-group">
                        <label for="purpose">Kategori Tujuan</label>
                        <select id="purpose" class="form-control">
                            <option value="umum">Umum</option>
                            <option value="aset">Aset</option>
                            <option value="jual">Jual</option>
                            <option value="repair_umum">Repair (Umum)</option>
                            <option value="repair_pelanggan">Repair (Pelanggan)</option>
                        </select>
                    </div>
                    <div class="form-group" id="targetPelangganWrap" style="display:none;">
                        <label for="target_pelanggan_id">Pelanggan</label>
                        <select id="target_pelanggan_id" class="form-control"></select>
                    </div>
                @endif
                <div class="form-group">
                    <label for="owner_type">Pemilik Barang</label>
                    <select id="owner_type" class="form-control">
                        <option value="office">Kantor</option>
                        <option value="investor">Investor / Mitra</option>
                    </select>
                </div>
                <div class="form-group" id="ownerUserWrap" style="display:none;">
                    <label for="owner_user_id">Pilih Investor / Mitra</label>
                    <select id="owner_user_id" class="form-control">
                        <option value="">-- Pilih Investor / Mitra --</option>
                        @foreach (($investorOwners ?? []) as $owner)
                            <option value="{{ $owner->id }}">{{ $owner->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Kolom Kanan: Pencarian Barang --}}
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                {{-- Tampilkan error untuk keranjang --}}
                @error('cart_items_json')
                    <div class="alert alert-danger">{{ $message }}</div>
                @enderror

                <div class="form-group">
                    <label for="barang_id">Cari Barang</label>
                    <select class="form-control" id="barang_id">
                        <option value="">-- Pilih Barang --</option>
                        @foreach ($barangs as $barang)
                            <option value="{{ $barang->id }}" data-stok="{{ $barang->stok }}">
                                {{ $barang->nama_barang }} (Stok: {{ $barang->stok }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="qty">Jumlah</label>
                    <input type="number" id="qty" class="form-control" min="1" value="1">
                </div>
                @if (($type ?? 'in') === 'in')
                    <div class="form-group">
                        <label for="hpp_unit">HPP / Unit</label>
                        <input type="number" id="hpp_unit" class="form-control" min="0" value="0">
                    </div>
                    <div class="form-group">
                        <label for="harga_jual_unit">Harga Jual / Unit</label>
                        <input type="number" id="harga_jual_unit" class="form-control" min="0" value="0">
                    </div>
                @endif
                <button type="button" id="addToCart" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah ke
                    Keranjang</button>
            </div>
        </div>
    </div>
</div>

{{-- Tabel Keranjang --}}
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h4>Keranjang Barang</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Nama Barang</th>
                                <th>Pemilik</th>
                                <th>Jumlah</th>
                                @if (($type ?? 'in') === 'in')
                                    <th>HPP/Unit</th>
                                    <th>Harga Jual/Unit</th>
                                @endif
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="cartTableBody">
                            {{-- Isi keranjang akan ditambahkan oleh JavaScript --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Hidden input untuk mengirim data keranjang --}}
<input type="hidden" name="cart_items_json" id="cart_items_json">

<div class="row">
    <div class="col-md-12 text-end">
        <a href="{{ url()->previous() }}" class="btn btn-secondary">Batal</a>
        <button type="submit" id="submitBtn" class="btn btn-success"><i class="fas fa-save"></i> Simpan
            Transaksi</button>
    </div>
</div>


@push('css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('mazer/extensions/sweetalert2/sweetalert2.min.css') }}">
@endpush

@push('js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{ asset('mazer/extensions/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            // ================== INI PERUBAHAN UTAMANYA ==================
            // Cek apakah ada data dari halaman edit. Jika tidak, buat array kosong.
            window.cart = typeof window.existingCartItems !== 'undefined' ? window.existingCartItems : [];
            // ==========================================================

            $('#barang_id').select2({
                placeholder: "-- Pilih Barang --",
                width: '100%'
            });
            $('#owner_user_id').select2({
                placeholder: "-- Pilih Investor / Mitra --",
                width: '100%'
            });

            const type = '{{ $type }}';
            if (type === 'out') {
                $('#target_pelanggan_id').select2({
                    placeholder: "-- Cari Pelanggan (Nama/No Layanan) --",
                    width: '100%',
                    ajax: {
                        url: "{{ route('api.search_pelanggan') }}",
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                term: params.term
                            };
                        },
                        processResults: function(data) {
                            return {
                                results: (data || []).map(function(item) {
                                    return {
                                        id: item.id,
                                        text: (item.no_layanan ? item.no_layanan + ' - ' : '') + item.nama
                                    };
                                })
                            };
                        },
                        cache: true
                    }
                });
            }
            function ownerLabel(ownerType, ownerName) {
                if (ownerType === 'investor') {
                    return 'Investor: ' + (ownerName || '-');
                }
                return 'Kantor';
            }
            function toggleOwnerUser() {
                const ownerType = $('#owner_type').val();
                if (ownerType === 'investor') {
                    $('#ownerUserWrap').show();
                } else {
                    $('#ownerUserWrap').hide();
                    $('#owner_user_id').val('').trigger('change');
                }
            }
            $('#owner_type').on('change', toggleOwnerUser);
            toggleOwnerUser();

            if (Array.isArray(window.cart) && window.cart.length > 0) {
                const first = window.cart[0] || {};
                if (type === 'out') {
                    if (first.owner_type) {
                        $('#owner_type').val(first.owner_type).trigger('change');
                        if ((first.owner_type || 'office') === 'investor' && first.owner_user_id) {
                            $('#owner_user_id').val(first.owner_user_id).trigger('change');
                        }
                    }
                    if (first.purpose) {
                        $('#purpose').val(first.purpose).trigger('change');
                    }
                    if (first.target_pelanggan_id) {
                        const option = new Option('Pelanggan #' + first.target_pelanggan_id, first.target_pelanggan_id, true, true);
                        $('#target_pelanggan_id').append(option).trigger('change');
                    }
                } else {
                    if (first.owner_type) {
                        $('#owner_type').val(first.owner_type).trigger('change');
                        if ((first.owner_type || 'office') === 'investor' && first.owner_user_id) {
                            $('#owner_user_id').val(first.owner_user_id).trigger('change');
                        }
                    }
                }
            }

            function togglePurposeOptions() {
                if (type !== 'out') {
                    return;
                }
                const ownerType = ($('#owner_type').val() || 'office').toLowerCase();
                const purpose = ($('#purpose').val() || 'umum').toLowerCase();
                if (ownerType === 'investor') {
                    $('#purpose').find('option[value="jual"]').prop('disabled', true);
                    $('#purpose').find('option[value="repair_umum"]').prop('disabled', true);
                    $('#purpose').find('option[value="repair_pelanggan"]').prop('disabled', true);
                    if (purpose === 'jual' || purpose === 'repair_umum' || purpose === 'repair_pelanggan') {
                        $('#purpose').val('umum');
                    }
                } else {
                    $('#purpose').find('option').prop('disabled', false);
                }
                const p = ($('#purpose').val() || 'umum').toLowerCase();
                if (p === 'repair_pelanggan') {
                    $('#targetPelangganWrap').show();
                } else {
                    $('#targetPelangganWrap').hide();
                    $('#target_pelanggan_id').val(null).trigger('change');
                }
            }
            if (type === 'out') {
                $('#purpose').on('change', togglePurposeOptions);
                $('#owner_type').on('change', togglePurposeOptions);
                togglePurposeOptions();
            }

            window.renderCartTable = function() {
                const cartTableBody = $('#cartTableBody');
                cartTableBody.empty();
                if (window.cart.length === 0) {
                    const colspan = type === 'in' ? 6 : 4;
                    cartTableBody.html('<tr><td colspan="' + colspan + '" class="text-center">Keranjang masih kosong.</td></tr>');
                } else {
                    window.cart.forEach(item => {
                        const rowKey = (item.id + '-' + (item.owner_type || 'office') + '-' + (item.owner_user_id || ''));
                        if (type === 'in') {
                            cartTableBody.append(`
                                <tr>
                                    <td>${item.nama_barang}</td>
                                    <td>${item.owner_label}</td>
                                    <td>${item.qty}</td>
                                    <td>${item.hpp_unit || 0}</td>
                                    <td>${item.harga_jual_unit || 0}</td>
                                    <td>
                                        <button type="button" class="btn btn-danger btn-sm remove-item" data-key="${rowKey}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            `);
                        } else {
                            cartTableBody.append(`
                                <tr>
                                    <td>${item.nama_barang}</td>
                                    <td>${item.owner_label}</td>
                                    <td>${item.qty}</td>
                                    <td>
                                        <button type="button" class="btn btn-danger btn-sm remove-item" data-key="${rowKey}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            `);
                        }
                    });
                }
                $('#cart_items_json').val(JSON.stringify(window.cart));
            }

            // --- (Sisa kode seperti addToCart, remove-item, dll, tetap sama persis) ---
            $('#addToCart').on('click', function() {
                const selectedOption = $('#barang_id').find('option:selected');
                const barangId = selectedOption.val();
                const namaBarang = selectedOption.text().split(' (Stok:')[0].trim();
                const stok = parseInt(selectedOption.data('stok'));
                const qty = parseInt($('#qty').val());
                const hppUnit = type === 'in' ? parseInt($('#hpp_unit').val() || '0') : 0;
                const hargaJualUnit = type === 'in' ? parseInt($('#harga_jual_unit').val() || '0') : 0;
                const ownerType = ($('#owner_type').val() || 'office').toLowerCase();
                const ownerUserIdRaw = $('#owner_user_id').val();
                const ownerUserId = ownerType === 'investor' ? parseInt(ownerUserIdRaw) : null;
                const ownerName = ownerType === 'investor' ? $('#owner_user_id').find('option:selected').text() : null;
                const purpose = type === 'out' ? (String($('#purpose').val() || 'umum').toLowerCase()) : null;
                const targetPelangganId = type === 'out' ? parseInt($('#target_pelanggan_id').val() || '0') : 0;

                if (!barangId) {
                    Swal.fire('Peringatan', 'Silakan pilih barang terlebih dahulu.', 'warning');
                    return;
                }
                if (isNaN(qty) || qty <= 0) {
                    Swal.fire('Peringatan', 'Jumlah harus lebih dari 0.', 'warning');
                    return;
                }
                if (ownerType === 'investor' && (!ownerUserId || ownerUserId < 1)) {
                    Swal.fire('Peringatan', 'Silakan pilih investor/mitra untuk kepemilikan investor.', 'warning');
                    return;
                }
                if (type === 'out') {
                    if (ownerType === 'investor' && (purpose === 'jual' || purpose === 'repair_umum' || purpose === 'repair_pelanggan')) {
                        Swal.fire('Peringatan', 'Barang milik investor tidak boleh untuk jual atau repair.', 'warning');
                        return;
                    }
                    if (ownerType === 'office' && purpose === 'repair_pelanggan' && (!targetPelangganId || targetPelangganId < 1)) {
                        Swal.fire('Peringatan', 'Repair pelanggan wajib pilih pelanggan.', 'warning');
                        return;
                    }
                }
                if (type === 'in' && (isNaN(hppUnit) || hppUnit < 0 || isNaN(hargaJualUnit) || hargaJualUnit < 0)) {
                    Swal.fire('Peringatan', 'HPP dan Harga Jual harus angka >= 0.', 'warning');
                    return;
                }
                const existingItem = window.cart.find(item =>
                    item.id == barangId &&
                    (item.owner_type || 'office') === ownerType &&
                    ((item.owner_user_id || null) == (ownerUserId || null))
                );
                if (existingItem) {
                    existingItem.qty = existingItem.qty + qty;
                    if (type === 'in') {
                        existingItem.hpp_unit = hppUnit;
                        existingItem.harga_jual_unit = hargaJualUnit;
                    }
                    if (type === 'out') {
                        existingItem.purpose = purpose;
                        existingItem.target_pelanggan_id = purpose === 'repair_pelanggan' ? targetPelangganId : null;
                    }
                } else {
                    window.cart.push({
                        id: barangId,
                        nama_barang: namaBarang,
                        stok: stok,
                        qty: qty,
                        owner_type: ownerType,
                        owner_user_id: ownerUserId,
                        hpp_unit: type === 'in' ? hppUnit : 0,
                        harga_jual_unit: type === 'in' ? hargaJualUnit : 0,
                        owner_name: ownerName,
                        owner_label: ownerLabel(ownerType, ownerName),
                        purpose: type === 'out' ? purpose : null,
                        target_pelanggan_id: (type === 'out' && purpose === 'repair_pelanggan') ? targetPelangganId : null
                    });
                }
                window.renderCartTable();
                $('#barang_id').val(null).trigger('change');
                $('#qty').val('1');
            });

            $(document).on('click', '.remove-item', function() {
                const keyToRemove = $(this).data('key');
                window.cart = window.cart.filter(item => {
                    const rowKey = (item.id + '-' + (item.owner_type || 'office') + '-' + (item.owner_user_id || ''));
                    return rowKey != keyToRemove;
                });
                window.renderCartTable();
            });

            $('#transactionForm').on('submit', function(e) {
                if (window.cart.length === 0) {
                    e.preventDefault();
                    Swal.fire('Error',
                        'Keranjang tidak boleh kosong. Silakan tambahkan barang terlebih dahulu.',
                        'error');
                    return false;
                }
                $('#cart_items_json').val(JSON.stringify(window.cart));
                $('#submitBtn').prop('disabled', true).html(
                    '<i class="fas fa-spinner fa-spin"></i> Menyimpan...');
            });
            // =================================================================================

            // Panggil renderCartTable() sekali di akhir.
            // Ini akan menampilkan data jika di halaman edit, atau pesan kosong jika di halaman create.
            window.renderCartTable();
        });
    </script>
@endpush
