@extends('layouts.app')

@section('title', __('Return Perangkat'))

@section('content')
    <div class="page-body">
        <div class="container-fluid">
            <div class="page-header">
                <div class="row">
                    <div class="col-sm-6">
                        <h3>{{ __('Return Perangkat') }}</h3>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('pelanggans.index') }}">{{ __('Pelanggan') }}</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('pelanggans.show', (int) $pelanggan->id) }}">{{ __('Detail') }}</a></li>
                            <li class="breadcrumb-item active">{{ __('Return Perangkat') }}</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid">
            <x-alert></x-alert>
            <div class="card">
                <div class="card-body">
                    <div class="mb-3">
                        <div><strong>{{ __('Pelanggan') }}</strong>: {{ $pelanggan->nama }} ({{ formatNoLayananTenant($pelanggan->no_layanan, (int) (auth()->user()->tenant_id ?? 0)) }})</div>
                        <div><strong>{{ __('Status') }}</strong>: {{ $pelanggan->status_berlangganan }}</div>
                    </div>

                    <form id="returnDeviceForm" method="post" action="{{ route('pelanggans.return-device.store', (int) $pelanggan->id) }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">{{ __('Status Return') }}</label>
                                    <select name="status_return" id="status_return" class="form-control" required>
                                        <option value="Berhasil">{{ __('Berhasil ditarik') }}</option>
                                        <option value="Gagal">{{ __('Gagal ditarik') }}</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">{{ __('Catatan') }}</label>
                                    <textarea name="notes" class="form-control" rows="3" maxlength="500"></textarea>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="mb-2"><strong>{{ __('Perangkat Terpasang Terdeteksi') }}</strong></div>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th style="width:40px"></th>
                                                <th>{{ __('Barang') }}</th>
                                                <th style="width:120px">{{ __('Qty Terpasang') }}</th>
                                                <th style="width:140px">{{ __('Qty Return') }}</th>
                                                <th style="width:160px">{{ __('Kondisi') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($candidates as $c)
                                                <tr>
                                                    <td class="text-center">
                                                        <input type="checkbox" class="chk-item"
                                                            data-barang-id="{{ (int) $c->barang_id }}"
                                                            data-owner-type="{{ $c->owner_type ?? 'office' }}"
                                                            data-owner-user-id="{{ (int) ($c->owner_user_id ?? 0) }}"
                                                            data-hpp-unit="{{ (int) ($c->hpp_unit ?? 0) }}"
                                                            data-harga-jual-unit="{{ (int) ($c->harga_jual_unit ?? 0) }}">
                                                    </td>
                                                    <td>
                                                        <div>{{ $c->nama_barang }}</div>
                                                        <div class="text-muted">
                                                            {{ ($c->owner_type ?? 'office') === 'investor' ? 'Investor' : 'Kantor' }}
                                                        </div>
                                                    </td>
                                                    <td class="text-center">{{ (int) $c->installed_qty }}</td>
                                                    <td>
                                                        <input type="number" class="form-control qty-input" min="1" max="{{ (int) $c->installed_qty }}"
                                                            name="items_tmp[{{ (int) $c->barang_id }}][qty]" value="1" disabled>
                                                    </td>
                                                    <td>
                                                        <select class="form-control cond-input" name="items_tmp[{{ (int) $c->barang_id }}][condition]" disabled>
                                                            <option value="Good">Good</option>
                                                            <option value="Scrap">Scrap</option>
                                                        </select>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center">{{ __('ONT/Adaptor belum terdeteksi dari histori pemasangan.') }}</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                <div class="mt-4">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <strong>{{ __('Tambah Manual (Data Lama)') }}</strong>
                                        <button type="button" class="btn btn-sm btn-secondary" id="btnAddManual">{{ __('Tambah') }}</button>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>{{ __('Barang') }}</th>
                                                    <th style="width:140px">{{ __('Pemilik') }}</th>
                                                    <th style="width:200px">{{ __('Investor/Mitra') }}</th>
                                                    <th style="width:120px">{{ __('Qty') }}</th>
                                                    <th style="width:140px">{{ __('Kondisi') }}</th>
                                                    <th style="width:70px"></th>
                                                </tr>
                                            </thead>
                                            <tbody id="manualBody">
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="text-muted">{{ __('Gunakan bagian ini jika histori pemasangan lama belum tercatat. Harga akan mengikuti harga di inventory pemilik (kantor/investor).') }}</div>
                                </div>

                                <div id="itemsContainer"></div>
                            </div>
                        </div>

                        <div class="mt-3 d-flex gap-2">
                            <a href="{{ route('pelanggans.show', (int) $pelanggan->id) }}" class="btn btn-secondary">{{ __('Kembali') }}</a>
                            <button class="btn btn-primary">{{ __('Simpan & Set Putus') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const form = document.getElementById('returnDeviceForm');
            const statusReturnEl = document.getElementById('status_return');
            const itemsContainer = document.getElementById('itemsContainer');
            const manualBody = document.getElementById('manualBody');
            const btnAddManual = document.getElementById('btnAddManual');

            const investorOwners = @json($investorOwners ?? []);

            function optionHtml(list, getValue, getLabel, placeholder) {
                let html = '';
                if (placeholder) {
                    html += '<option value="">' + placeholder + '</option>';
                }
                (list || []).forEach(function (it) {
                    html += '<option value="' + getValue(it) + '">' + getLabel(it) + '</option>';
                });
                return html;
            }

            function initBarangSelect(el) {
                if (!window.$ || !$.fn || !$.fn.select2) {
                    return;
                }
                $(el).select2({
                    placeholder: "-- Cari Barang --",
                    width: '100%',
                    ajax: {
                        url: "{{ route('api.search_barang') }}",
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
                                        text: (item.kode_barang ? item.kode_barang + ' - ' : '') + item.nama_barang
                                    };
                                })
                            };
                        },
                        cache: true
                    }
                });
                $(el).on('select2:select select2:clear', function () {
                    setTimeout(function () {
                        rebuild();
                    }, 0);
                });
            }

            function renderManualRow() {
                const investorOptions = optionHtml(investorOwners, (x) => x.id, (x) => x.name, '-- Pilih Investor/Mitra --');
                const tr = document.createElement('tr');
                tr.className = 'manual-item';
                tr.innerHTML =
                    '<td><select class="form-control manual-barang-id"></select></td>' +
                    '<td><select class="form-control manual-owner-type"><option value="office">Kantor</option><option value="investor">Investor</option></select></td>' +
                    '<td><select class="form-control manual-owner-user-id" disabled>' + investorOptions + '</select></td>' +
                    '<td><input type="number" class="form-control manual-qty" min="1" value="1"></td>' +
                    '<td><select class="form-control manual-condition"><option value="Good">Good</option><option value="Scrap">Scrap</option></select></td>' +
                    '<td class="text-center"><button type="button" class="btn btn-sm btn-danger manual-remove">&times;</button></td>';
                manualBody.appendChild(tr);
                const barangSelect = tr.querySelector('.manual-barang-id');
                initBarangSelect(barangSelect);
            }

            function rebuild() {
                const collected = [];
                document.querySelectorAll('.chk-item').forEach(function (chk) {
                    const barangId = chk.getAttribute('data-barang-id');
                    const ownerType = chk.getAttribute('data-owner-type') || 'office';
                    const ownerUserId = chk.getAttribute('data-owner-user-id') || '0';
                    const row = chk.closest('tr');
                    const qtyInput = row.querySelector('.qty-input');
                    const condInput = row.querySelector('.cond-input');
                    if (chk.checked) {
                        qtyInput.disabled = false;
                        condInput.disabled = false;
                        const qty = qtyInput.value || '1';
                        const cond = condInput.value || 'Good';
                        collected.push({
                            barang_id: barangId,
                            qty: qty,
                            condition: cond,
                            owner_type: ownerType,
                            owner_user_id: ownerUserId
                        });
                    } else {
                        qtyInput.disabled = true;
                        condInput.disabled = true;
                    }
                });

                document.querySelectorAll('.manual-item').forEach(function (row) {
                    const barangId = row.querySelector('.manual-barang-id')?.value || '';
                    const ownerType = row.querySelector('.manual-owner-type')?.value || 'office';
                    const ownerUserId = row.querySelector('.manual-owner-user-id')?.value || '0';
                    const qty = row.querySelector('.manual-qty')?.value || '1';
                    const cond = row.querySelector('.manual-condition')?.value || 'Good';
                    if (!barangId) {
                        return;
                    }
                    collected.push({
                        barang_id: barangId,
                        qty: qty,
                        condition: cond,
                        owner_type: ownerType,
                        owner_user_id: ownerUserId
                    });
                });

                itemsContainer.innerHTML = '';
                collected.forEach(function (it, idx) {
                    itemsContainer.insertAdjacentHTML('beforeend',
                        '<input type="hidden" name="items[' + idx + '][barang_id]" value="' + it.barang_id + '">' +
                        '<input type="hidden" name="items[' + idx + '][qty]" value="' + it.qty + '">' +
                        '<input type="hidden" name="items[' + idx + '][condition]" value="' + it.condition + '">' +
                        '<input type="hidden" name="items[' + idx + '][owner_type]" value="' + it.owner_type + '">' +
                        '<input type="hidden" name="items[' + idx + '][owner_user_id]" value="' + it.owner_user_id + '">'
                    );
                });
            }
            document.addEventListener('change', function (e) {
                if (e.target.classList.contains('chk-item') || e.target.classList.contains('qty-input') || e.target.classList.contains('cond-input')) {
                    rebuild();
                }
                if (e.target.classList.contains('manual-owner-type')) {
                    const row = e.target.closest('.manual-item');
                    const ownerUserSelect = row.querySelector('.manual-owner-user-id');
                    if (e.target.value === 'investor') {
                        ownerUserSelect.disabled = false;
                        ownerUserSelect.value = '';
                    } else {
                        ownerUserSelect.disabled = true;
                        ownerUserSelect.value = '';
                    }
                    rebuild();
                }
                if (e.target.closest('.manual-item')) {
                    rebuild();
                }
            });
            document.addEventListener('click', function (e) {
                if (e.target && e.target.id === 'btnAddManual') {
                    renderManualRow();
                    rebuild();
                }
                if (e.target && e.target.classList.contains('manual-remove')) {
                    const row = e.target.closest('.manual-item');
                    if (row) {
                        row.remove();
                        rebuild();
                    }
                }
            });
            if (form) {
                form.addEventListener('submit', function (e) {
                    rebuild();
                    const st = (statusReturnEl ? statusReturnEl.value : 'Berhasil');
                    const count = form.querySelectorAll('input[name^="items["][name$="[barang_id]"]').length;
                    if (st === 'Berhasil' && count < 1) {
                        e.preventDefault();
                        alert('Pilih minimal satu barang untuk return.');
                    }
                });
            }
            rebuild();
        })();
    </script>
@endsection
