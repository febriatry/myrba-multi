@extends('layouts.app')

@section('title', __('Bulk Return Perangkat'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Bulk Return Perangkat') }}</h3>
                    <p class="text-subtitle text-muted">{{ __('Untuk data lama tanpa histori pemasangan. Hanya memproses pelanggan status Non Aktif dan akan diubah menjadi Putus.') }}</p>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="/dashboard">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('pelanggans.index') }}">{{ __('Pelanggan') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Bulk Return') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <x-alert></x-alert>

            @if (!empty($bulkErrors))
                <div class="card mb-3">
                    <div class="card-header">
                        <h4 class="mb-0">{{ __('Error') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('Detail') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($bulkErrors as $i => $e)
                                        <tr>
                                            <td>{{ $i + 1 }}</td>
                                            <td>{{ $e }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            <div class="card">
                <div class="card-body">
                    <form method="post" action="{{ route('pelanggans.return-device.bulk.store') }}">
                        @csrf
                        <div class="row g-2">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">{{ __('Pemilik') }}</label>
                                    <select name="owner_type" id="owner_type" class="form-control" required>
                                        <option value="office">{{ __('Kantor') }}</option>
                                        <option value="investor">{{ __('Investor') }}</option>
                                    </select>
                                </div>
                                <div class="form-group" id="investorWrap" style="display:none;">
                                    <label class="form-label">{{ __('Investor/Mitra') }}</label>
                                    <select name="owner_user_id" id="owner_user_id" class="form-control">
                                        <option value="">{{ __('-- Pilih Investor/Mitra --') }}</option>
                                        @foreach ($investorOwners as $inv)
                                            <option value="{{ $inv->id }}">{{ $inv->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">{{ __('Status Return') }}</label>
                                    <select name="status_return" id="status_return" class="form-control" required>
                                        <option value="Berhasil">{{ __('Berhasil ditarik') }}</option>
                                        <option value="Gagal">{{ __('Gagal ditarik') }}</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">{{ __('Catatan (opsional)') }}</label>
                                    <textarea name="notes" class="form-control" rows="3" maxlength="500"></textarea>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label class="form-label">{{ __('Daftar No Layanan (1 per baris)') }}</label>
                                    <textarea name="no_layanan_list" class="form-control" rows="10" required>{{ old('no_layanan_list') }}</textarea>
                                </div>

                                <div class="mt-3" id="itemsSection">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <strong>{{ __('Barang Return (dipakai untuk semua pelanggan)') }}</strong>
                                        <button type="button" class="btn btn-sm btn-secondary" id="btnAddItem">{{ __('Tambah Item') }}</button>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>{{ __('Barang') }}</th>
                                                    <th style="width:120px">{{ __('Qty') }}</th>
                                                    <th style="width:140px">{{ __('Kondisi') }}</th>
                                                    <th style="width:70px"></th>
                                                </tr>
                                            </thead>
                                            <tbody id="itemsBody"></tbody>
                                        </table>
                                    </div>
                                    <div class="text-muted">{{ __('Jika status return Gagal, item tidak wajib.') }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 d-flex gap-2">
                            <a href="{{ route('pelanggans.index') }}" class="btn btn-secondary">{{ __('Kembali') }}</a>
                            <button class="btn btn-primary">{{ __('Proses Bulk') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>

    <script>
        (function () {
            const inventoryRows = @json($inventoryRows ?? []);
            const ownerTypeEl = document.getElementById('owner_type');
            const investorWrap = document.getElementById('investorWrap');
            const investorEl = document.getElementById('owner_user_id');
            const statusEl = document.getElementById('status_return');
            const itemsSection = document.getElementById('itemsSection');
            const itemsBody = document.getElementById('itemsBody');
            const btnAddItem = document.getElementById('btnAddItem');

            function optionHtml(list, placeholder) {
                let html = '';
                html += '<option value="">' + placeholder + '</option>';
                (list || []).forEach(function (it) {
                    const label = (it.kode_barang ? (it.kode_barang + ' - ') : '') + it.nama_barang;
                    html += '<option value="' + it.id + '">' + label + '</option>';
                });
                return html;
            }

            function listForOwner(ownerType, ownerUserId) {
                const type = String(ownerType || 'office').toLowerCase();
                const uid = parseInt(ownerUserId || '0');
                const map = new Map();
                (inventoryRows || []).forEach(function (row) {
                    const rowType = String(row.owner_type || 'office').toLowerCase();
                    const rowUid = parseInt(row.owner_user_id || '0');
                    if (rowType !== type) {
                        return;
                    }
                    if (type === 'investor' && rowUid !== uid) {
                        return;
                    }
                    if (!map.has(row.id)) {
                        map.set(row.id, {id: row.id, kode_barang: row.kode_barang, nama_barang: row.nama_barang});
                    }
                });
                return Array.from(map.values()).sort(function (a, b) {
                    return String(a.nama_barang || '').localeCompare(String(b.nama_barang || ''));
                });
            }

            function renderItemRow() {
                const ownerType = ownerTypeEl.value;
                const ownerUserId = investorEl.value;
                const list = ownerType === 'investor' ? (ownerUserId ? listForOwner('investor', ownerUserId) : []) : listForOwner('office', null);
                const tr = document.createElement('tr');
                tr.className = 'item-row';
                tr.innerHTML =
                    '<td><select class="form-control item-barang-id">' + optionHtml(list, ownerType === 'investor' && !ownerUserId ? '-- Pilih Investor dulu --' : '-- Pilih Barang --') + '</select></td>' +
                    '<td><input type="number" class="form-control item-qty" min="1" value="1"></td>' +
                    '<td><select class="form-control item-condition"><option value="Good">Good</option><option value="Scrap">Scrap</option></select></td>' +
                    '<td class="text-center"><button type="button" class="btn btn-sm btn-danger item-remove">&times;</button></td>';
                itemsBody.appendChild(tr);
                rebuildHidden();
            }

            function rebuildHidden() {
                document.querySelectorAll('input[name="items[][barang_id]"],input[name="items[][qty]"],input[name="items[][condition]"]').forEach(function (el) {
                    el.remove();
                });
                document.querySelectorAll('.item-row').forEach(function (row) {
                    const barangId = row.querySelector('.item-barang-id')?.value || '';
                    const qty = row.querySelector('.item-qty')?.value || '1';
                    const cond = row.querySelector('.item-condition')?.value || 'Good';
                    if (!barangId) {
                        return;
                    }
                    itemsSection.insertAdjacentHTML('beforeend',
                        '<input type="hidden" name="items[][barang_id]" value="' + barangId + '">' +
                        '<input type="hidden" name="items[][qty]" value="' + qty + '">' +
                        '<input type="hidden" name="items[][condition]" value="' + cond + '">'
                    );
                });
            }

            function toggleOwner() {
                const type = ownerTypeEl.value;
                if (type === 'investor') {
                    investorWrap.style.display = '';
                } else {
                    investorWrap.style.display = 'none';
                    investorEl.value = '';
                }
                itemsBody.innerHTML = '';
                renderItemRow();
            }

            function toggleItems() {
                const st = statusEl.value;
                itemsSection.style.display = st === 'Gagal' ? 'none' : '';
            }

            ownerTypeEl.addEventListener('change', toggleOwner);
            investorEl.addEventListener('change', function () {
                itemsBody.innerHTML = '';
                renderItemRow();
            });
            statusEl.addEventListener('change', toggleItems);
            document.addEventListener('change', function (e) {
                if (e.target.closest('.item-row')) {
                    rebuildHidden();
                }
            });
            document.addEventListener('click', function (e) {
                if (e.target && e.target.id === 'btnAddItem') {
                    renderItemRow();
                }
                if (e.target && e.target.classList.contains('item-remove')) {
                    const row = e.target.closest('.item-row');
                    if (row) {
                        row.remove();
                        rebuildHidden();
                    }
                }
            });

            toggleOwner();
            toggleItems();
        })();
    </script>
@endsection

