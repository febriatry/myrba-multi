@extends('layouts.app')

@section('title', __('Material Pemasangan'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Material Pemasangan') }}</h3>
                    <p class="text-subtitle text-muted">{{ __('Pelanggan: :nama (:no)', ['nama' => $pelanggan->nama, 'no' => formatNoLayananTenant($pelanggan->no_layanan, (int) (auth()->user()->tenant_id ?? 0))]) }}</p>
                    <p class="text-subtitle text-muted">
                        {{ __('Status Validasi Gudang') }}:
                        @if (($pelanggan->material_status ?? 'Pending') === 'Approved')
                            <span class="badge bg-success">Approved</span>
                            @if (!empty($approvedByName))
                                <span>{{ __('oleh :name', ['name' => $approvedByName]) }}</span>
                            @endif
                            @if (!empty($pelanggan->material_approved_at))
                                <span>({{ \Carbon\Carbon::parse($pelanggan->material_approved_at)->format('d-m-Y H:i') }})</span>
                            @endif
                        @else
                            <span class="badge bg-warning text-dark">Pending</span>
                        @endif
                    </p>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('pelanggans-request.index') }}">{{ __('Request Pelanggan') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('Material') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <x-alert></x-alert>
            <div class="card">
                <div class="card-body">
                    <form id="materialForm" method="post" action="{{ route('pelanggans-request.materials.store', $pelanggan->id) }}">
                        @csrf
                        <div class="row g-2 mb-3">
                            <div class="col-md-4">
                                <label class="form-label">{{ __('Barang') }}</label>
                                <select id="barang_id" class="form-control">
                                    <option value="">-- Pilih Barang --</option>
                                    @foreach ($barangs as $b)
                                        <option value="{{ $b->id }}">{{ $b->nama_barang }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('Qty') }}</label>
                                <input type="number" id="qty" class="form-control" min="1" value="1">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('Pemilik') }}</label>
                                <select id="owner_type" class="form-control">
                                    <option value="office">Kantor</option>
                                    <option value="investor">Investor</option>
                                </select>
                            </div>
                            <div class="col-md-4" id="ownerUserWrap" style="display:none;">
                                <label class="form-label">{{ __('Investor/Mitra') }}</label>
                                <select id="owner_user_id" class="form-control">
                                    <option value="">-- Pilih Investor --</option>
                                    @foreach ($investorOwners as $owner)
                                        <option value="{{ $owner->id }}">{{ $owner->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">{{ __('Catatan') }}</label>
                                <input type="text" id="notes" class="form-control" placeholder="Opsional">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="button" id="addItem" class="btn btn-primary w-100">{{ __('Tambah') }}</button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Barang</th>
                                        <th>Qty</th>
                                        <th>Pemilik</th>
                                        <th>Catatan</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="itemsBody"></tbody>
                            </table>
                        </div>
                        <input type="hidden" name="items_payload" id="items_payload">
                        <div id="itemsHidden"></div>

                        <div class="mt-3 text-end">
                            <a href="{{ route('pelanggans-request.index') }}" class="btn btn-secondary">{{ __('Kembali') }}</a>
                            <button type="button" class="btn btn-warning" onclick="document.getElementById('approveMaterialForm').submit()">{{ __('Validasi Tim Gudang') }}</button>
                            <button class="btn btn-success">{{ __('Simpan Material') }}</button>
                        </div>
                    </form>
                    <form id="approveMaterialForm" method="post" action="{{ route('pelanggans-request.materials.approve', $pelanggan->id) }}">
                        @csrf
                    </form>
                </div>
            </div>
        </section>
    </div>

    @php
        $materialRows = $materials->map(function ($m) {
            return [
                'barang_id' => (int) $m->barang_id,
                'barang_nama' => $m->nama_barang,
                'qty' => (int) $m->qty,
                'owner_type' => $m->owner_type,
                'owner_user_id' => $m->owner_user_id ? (int) $m->owner_user_id : null,
                'owner_name' => $m->owner_name,
                'notes' => $m->notes,
            ];
        })->values()->all();
    @endphp
    <script>
        (function () {
            var materials = @json($materialRows);

            function ownerLabel(item) {
                if (item.owner_type === 'investor') {
                    return 'Investor: ' + (item.owner_name || '-');
                }
                return 'Kantor';
            }
            function escAttr(value) {
                return String(value || '')
                    .replace(/&/g, '&amp;')
                    .replace(/"/g, '&quot;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;');
            }
            function toggleOwnerWrap() {
                var type = document.getElementById('owner_type').value;
                document.getElementById('ownerUserWrap').style.display = type === 'investor' ? '' : 'none';
            }
            function render() {
                var tbody = document.getElementById('itemsBody');
                var hiddenWrap = document.getElementById('itemsHidden');
                tbody.innerHTML = '';
                hiddenWrap.innerHTML = '';
                if (materials.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center">Belum ada material.</td></tr>';
                } else {
                    for (var i = 0; i < materials.length; i++) {
                        var m = materials[i];
                        tbody.innerHTML += '<tr>' +
                            '<td>' + m.barang_nama + '</td>' +
                            '<td>' + m.qty + '</td>' +
                            '<td>' + ownerLabel(m) + '</td>' +
                            '<td>' + (m.notes || '-') + '</td>' +
                            '<td><button type="button" class="btn btn-sm btn-danger" data-index="' + i + '">Hapus</button></td>' +
                            '</tr>';
                        hiddenWrap.innerHTML += '<input type="hidden" name="items[' + i + '][barang_id]" value="' + m.barang_id + '">' +
                            '<input type="hidden" name="items[' + i + '][qty]" value="' + m.qty + '">' +
                            '<input type="hidden" name="items[' + i + '][owner_type]" value="' + escAttr(m.owner_type) + '">' +
                            '<input type="hidden" name="items[' + i + '][owner_user_id]" value="' + (m.owner_user_id || '') + '">' +
                            '<input type="hidden" name="items[' + i + '][notes]" value="' + escAttr(m.notes || '') + '">';
                    }
                }
            }
            document.getElementById('owner_type').addEventListener('change', toggleOwnerWrap);
            document.getElementById('addItem').addEventListener('click', function () {
                var barangEl = document.getElementById('barang_id');
                var barangId = parseInt(barangEl.value || '0');
                var barangNama = barangEl.options[barangEl.selectedIndex] ? barangEl.options[barangEl.selectedIndex].text : '';
                var qty = parseInt(document.getElementById('qty').value || '0');
                var ownerType = document.getElementById('owner_type').value || 'office';
                var ownerUserEl = document.getElementById('owner_user_id');
                var ownerUserId = ownerType === 'investor' ? parseInt(ownerUserEl.value || '0') : null;
                var ownerName = ownerType === 'investor' ? (ownerUserEl.options[ownerUserEl.selectedIndex] ? ownerUserEl.options[ownerUserEl.selectedIndex].text : null) : null;
                var notes = document.getElementById('notes').value || '';
                if (!barangId || qty < 1) {
                    return;
                }
                if (ownerType === 'investor' && (!ownerUserId || ownerUserId < 1)) {
                    return;
                }
                materials.push({
                    barang_id: barangId,
                    barang_nama: barangNama,
                    qty: qty,
                    owner_type: ownerType,
                    owner_user_id: ownerUserId,
                    owner_name: ownerName,
                    notes: notes
                });
                render();
                document.getElementById('qty').value = '1';
                document.getElementById('notes').value = '';
            });
            document.getElementById('itemsBody').addEventListener('click', function (e) {
                if (e.target && e.target.dataset && typeof e.target.dataset.index !== 'undefined') {
                    var idx = parseInt(e.target.dataset.index);
                    if (idx >= 0) {
                        materials.splice(idx, 1);
                        render();
                    }
                }
            });
            document.getElementById('materialForm').addEventListener('submit', function (e) {
                if (materials.length === 0) {
                    e.preventDefault();
                }
            });
            toggleOwnerWrap();
            render();
        })();
    </script>
@endsection
