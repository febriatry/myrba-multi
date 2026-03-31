@extends('layouts.app')

@section('title', __('WA Broadcast Settings'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('WA Broadcast Settings') }}</h3>
                    <p class="text-subtitle text-muted">
                        {{ __('Pengaturan placeholder untuk WhatsApp Broadcast (Ivosight). Ubah nilai di .env.') }}
                    </p>
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="{{ route('platform.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('WA Broadcast Settings') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div>
                                    <h6 class="mb-1">{{ __('Status WA Broadcast') }}</h6>
                                    <span class="badge {{ $is_wa_broadcast_active ? 'bg-success' : 'bg-danger' }}">
                                        {{ $is_wa_broadcast_active ? __('AKTIF') : __('NON AKTIF') }}
                                    </span>
                                </div>
                                <div class="d-flex gap-2">
                                    <form method="POST" action="{{ route('wa-config.test-connection') }}">
                                        @csrf
                                        <button type="submit" class="btn btn-warning">
                                            {{ __('Tes Koneksi Sandbox') }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('wa-config.sync-templates') }}">
                                        @csrf
                                        <button type="submit" class="btn btn-primary">
                                            {{ __('Sync Template Ivosight') }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('wa-config.toggle-status') }}">
                                        @csrf
                                        <input type="hidden" name="is_wa_broadcast_active"
                                            value="{{ $is_wa_broadcast_active ? 'No' : 'Yes' }}">
                                        <button type="submit"
                                            class="btn {{ $is_wa_broadcast_active ? 'btn-danger' : 'btn-success' }}">
                                            {{ $is_wa_broadcast_active ? __('Nonaktifkan WA Broadcast') : __('Aktifkan WA Broadcast') }}
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <div class="card border">
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1">WA Billing</h6>
                                                    <small class="text-muted">Tagihan Otomatis</small>
                                                </div>
                                                <form method="POST" action="{{ route('wa-config.toggle-status') }}">
                                                    @csrf
                                                    <input type="hidden" name="is_wa_billing_active" value="{{ $is_wa_billing_active ? 'No' : 'Yes' }}">
                                                    <button type="submit" class="btn btn-sm {{ $is_wa_billing_active ? 'btn-success' : 'btn-outline-secondary' }}">
                                                        {{ $is_wa_billing_active ? 'ON' : 'OFF' }}
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border">
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1">WA Payment</h6>
                                                    <small class="text-muted">Bukti Bayar Otomatis</small>
                                                </div>
                                                <form method="POST" action="{{ route('wa-config.toggle-status') }}">
                                                    @csrf
                                                    <input type="hidden" name="is_wa_payment_active" value="{{ $is_wa_payment_active ? 'No' : 'Yes' }}">
                                                    <button type="submit" class="btn btn-sm {{ $is_wa_payment_active ? 'btn-success' : 'btn-outline-secondary' }}">
                                                        {{ $is_wa_payment_active ? 'ON' : 'OFF' }}
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border">
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1">WA Welcome</h6>
                                                    <small class="text-muted">Ucapan Selamat Datang</small>
                                                </div>
                                                <form method="POST" action="{{ route('wa-config.toggle-status') }}">
                                                    @csrf
                                                    <input type="hidden" name="is_wa_welcome_active" value="{{ $is_wa_welcome_active ? 'No' : 'Yes' }}">
                                                    <button type="submit" class="btn btn-sm {{ $is_wa_welcome_active ? 'btn-success' : 'btn-outline-secondary' }}">
                                                        {{ $is_wa_welcome_active ? 'ON' : 'OFF' }}
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Provider</label>
                                <input type="text" class="form-control" value="{{ $provider }}" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">IVOSIGHT_BASE_URL</label>
                                <input type="text" class="form-control" value="{{ $base_url }}" placeholder="https://api.ivosight.example">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">IVOSIGHT_API_KEY</label>
                                <input type="text" class="form-control"
                                    value="{{ $api_key ? str_repeat('*', max(strlen($api_key) - 4, 0)) . substr($api_key, -4) : '' }}"
                                    placeholder="API Key dari dashboard ivosight">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">IVOSIGHT_SENDER_ID</label>
                                <input type="text" class="form-control" value="{{ $sender_id }}" placeholder="Sender ID terdaftar">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">IVOSIGHT_USE_TEMPLATE</label>
                                <input type="text" class="form-control" value="{{ $use_template ? 'true' : 'false' }}" placeholder="true/false">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">IVOSIGHT_TEMPLATE_ENDPOINTS</label>
                                <input type="text" class="form-control" value="{{ implode(',', $template_endpoints ?? []) }}"
                                    placeholder="/api/v1/messages/templates,/api/v1/templates">
                            </div>
                            <div class="alert alert-info">
                                {{ __('Nilai di atas hanya tampilan. Untuk mengubah, edit file .env lalu jalankan artisan cache:clear dan config:cache.') }}
                            </div>
                            @if (!empty($connection_report))
                                <div class="alert {{ $connection_report['ok'] ? 'alert-success' : 'alert-danger' }}">
                                    <div><strong>BASE_URL:</strong> {{ $connection_report['base_url'] ?: '-' }}</div>
                                    <div><strong>BASE_URL Valid:</strong> {{ $connection_report['base_url_valid'] ? 'Yes' : 'No' }}</div>
                                    <div><strong>API_KEY Terisi:</strong> {{ $connection_report['api_key_present'] ? 'Yes' : 'No' }}</div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Endpoint') }}</th>
                                                <th>{{ __('HTTP') }}</th>
                                                <th>{{ __('Sukses') }}</th>
                                                <th>{{ __('Pesan') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach (($connection_report['template_endpoints'] ?? []) as $item)
                                                <tr>
                                                    <td>{{ $item['endpoint'] ?? '-' }}</td>
                                                    <td>{{ $item['status'] ?? '-' }}</td>
                                                    <td>{{ !empty($item['success']) ? 'Yes' : 'No' }}</td>
                                                    <td>{{ \Illuminate\Support\Str::limit((string)($item['message'] ?? '-'), 120) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-12 mt-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="mb-3">{{ __('Template Tersinkronisasi (20 terbaru)') }}</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Template ID') }}</th>
                                            <th>{{ __('Nama') }}</th>
                                            <th>{{ __('Bahasa') }}</th>
                                            <th>{{ __('Kategori') }}</th>
                                            <th>{{ __('Status') }}</th>
                                            <th>{{ __('Sync Terakhir') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($templates as $template)
                                            <tr>
                                                <td>{{ $template->template_id }}</td>
                                                <td>{{ $template->name ?? '-' }}</td>
                                                <td>{{ strtoupper($template->language ?? '-') }}</td>
                                                <td>{{ strtoupper($template->category ?? '-') }}</td>
                                                <td>{{ strtoupper($template->status ?? '-') }}</td>
                                                <td>{{ optional($template->synced_at)->format('Y-m-d H:i:s') ?? '-' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center">{{ __('Belum ada data template tersinkronisasi.') }}</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-12 mt-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="mb-3">{{ __('Mapping Variabel Template') }}</h6>
                            <form method="POST" action="{{ route('wa-config.template-mappings.store') }}">
                                @csrf
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <label class="form-label">{{ __('Template') }}</label>
                                        <select name="template_id" class="form-control" required>
                                            <option value="">-</option>
                                            @foreach ($all_templates as $templateOption)
                                                <option value="{{ $templateOption->template_id }}">
                                                    {{ $templateOption->name }} ({{ $templateOption->template_id }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">{{ __('Message Type') }}</label>
                                        <select name="message_type" class="form-control" required>
                                            @foreach ($message_type_options as $typeKey => $typeLabel)
                                                <option value="{{ $typeKey }}">{{ $typeLabel }} ({{ $typeKey }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">{{ __('Komponen') }}</label>
                                        <select name="component_type" class="form-control" required>
                                            <option value="body">body</option>
                                            <option value="header">header</option>
                                            <option value="button">button</option>
                                            <option value="carousel">carousel</option>
                                        </select>
                                    </div>
                                    <div class="col-md-1">
                                        <label class="form-label">{{ __('Comp Idx') }}</label>
                                        <input type="number" min="0" name="component_index" class="form-control" value="0">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">{{ __('Sub Type') }}</label>
                                        <select name="component_sub_type" class="form-control">
                                            <option value="">-</option>
                                            <option value="url">url</option>
                                            <option value="quick_reply">quick_reply</option>
                                            <option value="flow">flow</option>
                                            <option value="phone_number">phone_number</option>
                                            <option value="copy_code">copy_code</option>
                                            <option value="one_tap">one_tap</option>
                                            <option value="spm">spm</option>
                                        </select>
                                    </div>
                                    <div class="col-md-1">
                                        <label class="form-label">{{ __('Index') }}</label>
                                        <input type="number" min="1" name="param_index" class="form-control" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">{{ __('Source Key') }}</label>
                                        <select name="source_key" class="form-control" required>
                                            @foreach ($source_key_options as $sourceValue => $sourceLabel)
                                                <option value="{{ $sourceValue }}">{{ $sourceLabel }} ({{ $sourceValue }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">{{ __('Type') }}</label>
                                        <select name="parameter_type" class="form-control" required>
                                            <option value="text">text</option>
                                            <option value="image">image</option>
                                            <option value="document">document</option>
                                            <option value="video">video</option>
                                            <option value="payload">payload</option>
                                            <option value="action">action</option>
                                            <option value="product">product</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">{{ __('Wajib') }}</label>
                                        <select name="is_required" class="form-control" required>
                                            <option value="Yes">Yes</option>
                                            <option value="No">No</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">{{ __('Default Value') }}</label>
                                        <input type="text" name="default_value" class="form-control">
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label">{{ __('Catatan') }}</label>
                                        <input type="text" name="notes" class="form-control">
                                    </div>
                                    <div class="col-md-12 mt-2">
                                        <button type="submit" class="btn btn-primary">{{ __('Simpan Mapping') }}</button>
                                    </div>
                                    <div class="col-md-12">
                                        <small class="text-muted">{{ __('Source Key dipilih dari opsi supaya mapping konsisten.') }}</small>
                                    </div>
                                </div>
                            </form>

                            <div class="table-responsive mt-3">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Template') }}</th>
                                            <th>{{ __('Type') }}</th>
                                            <th>{{ __('Komponen') }}</th>
                                            <th>{{ __('Comp Idx') }}</th>
                                            <th>{{ __('Sub Type') }}</th>
                                            <th>{{ __('Index') }}</th>
                                            <th>{{ __('Source') }}</th>
                                            <th>{{ __('Param Type') }}</th>
                                            <th>{{ __('Required') }}</th>
                                            <th>{{ __('Default') }}</th>
                                            <th>{{ __('Aksi') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($template_mappings as $mapping)
                                            <tr>
                                                <td>{{ $mapping->template_id }}</td>
                                                <td>{{ $mapping->message_type }}</td>
                                                <td>{{ $mapping->component_type }}</td>
                                                <td>{{ $mapping->component_index }}</td>
                                                <td>{{ $mapping->component_sub_type ?? '-' }}</td>
                                                <td>{{ $mapping->param_index }}</td>
                                                <td>{{ $mapping->source_key }}</td>
                                                <td>{{ $mapping->parameter_type }}</td>
                                                <td>{{ $mapping->is_required }}</td>
                                                <td>{{ $mapping->default_value ?? '-' }}</td>
                                                <td>
                                                    <details class="mb-2">
                                                        <summary class="btn btn-sm btn-warning">{{ __('Edit') }}</summary>
                                                        <form method="POST"
                                                            action="{{ route('wa-config.template-mappings.update', $mapping->id) }}"
                                                            class="mt-2">
                                                            @csrf
                                                            @method('PUT')
                                                            <div class="row g-2">
                                                                <div class="col-md-4">
                                                                    <select name="template_id" class="form-control" required>
                                                                        @foreach ($all_templates as $templateOption)
                                                                            <option value="{{ $templateOption->template_id }}"
                                                                                {{ $mapping->template_id === $templateOption->template_id ? 'selected' : '' }}>
                                                                                {{ $templateOption->name }} ({{ $templateOption->template_id }})
                                                                            </option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                                <div class="col-md-2">
                                                                    <select name="message_type" class="form-control" required>
                                                                        @php($hasTypeInOption = array_key_exists($mapping->message_type, $message_type_options))
                                                                        @if (!$hasTypeInOption)
                                                                            <option value="{{ $mapping->message_type }}" selected>{{ $mapping->message_type }}</option>
                                                                        @endif
                                                                        @foreach ($message_type_options as $typeKey => $typeLabel)
                                                                            <option value="{{ $typeKey }}"
                                                                                {{ $mapping->message_type === $typeKey ? 'selected' : '' }}>
                                                                                {{ $typeLabel }} ({{ $typeKey }})
                                                                            </option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                                <div class="col-md-2">
                                                                    <select name="component_type" class="form-control" required>
                                                                        @foreach (['body','header','button','carousel'] as $componentType)
                                                                            <option value="{{ $componentType }}"
                                                                                {{ $mapping->component_type === $componentType ? 'selected' : '' }}>
                                                                                {{ $componentType }}
                                                                            </option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                                <div class="col-md-1">
                                                                    <input type="number" min="0" name="component_index"
                                                                        value="{{ $mapping->component_index }}" class="form-control">
                                                                </div>
                                                                <div class="col-md-2">
                                                                    <select name="component_sub_type" class="form-control">
                                                                        <option value="">-</option>
                                                                        @foreach (['url','quick_reply','flow','phone_number','copy_code','one_tap','spm'] as $subType)
                                                                            <option value="{{ $subType }}"
                                                                                {{ $mapping->component_sub_type === $subType ? 'selected' : '' }}>
                                                                                {{ $subType }}
                                                                            </option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                                <div class="col-md-1">
                                                                    <input type="number" min="1" name="param_index"
                                                                        value="{{ $mapping->param_index }}" class="form-control" required>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <select name="source_key" class="form-control" required>
                                                                        @php($hasSourceInOption = array_key_exists($mapping->source_key, $source_key_options))
                                                                        @if (!$hasSourceInOption)
                                                                            <option value="{{ $mapping->source_key }}" selected>
                                                                                {{ $mapping->source_key }}
                                                                            </option>
                                                                        @endif
                                                                        @foreach ($source_key_options as $sourceValue => $sourceLabel)
                                                                            <option value="{{ $sourceValue }}"
                                                                                {{ $mapping->source_key === $sourceValue ? 'selected' : '' }}>
                                                                                {{ $sourceLabel }} ({{ $sourceValue }})
                                                                            </option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                                <div class="col-md-2">
                                                                    <select name="parameter_type" class="form-control" required>
                                                                        @foreach (['text','image','document','video','payload','action','product'] as $parameterType)
                                                                            <option value="{{ $parameterType }}"
                                                                                {{ $mapping->parameter_type === $parameterType ? 'selected' : '' }}>
                                                                                {{ $parameterType }}
                                                                            </option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                                <div class="col-md-2">
                                                                    <select name="is_required" class="form-control" required>
                                                                        <option value="Yes" {{ $mapping->is_required === 'Yes' ? 'selected' : '' }}>Yes</option>
                                                                        <option value="No" {{ $mapping->is_required === 'No' ? 'selected' : '' }}>No</option>
                                                                    </select>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <input type="text" name="default_value" class="form-control"
                                                                        value="{{ $mapping->default_value }}">
                                                                </div>
                                                                <div class="col-md-10">
                                                                    <input type="text" name="notes" class="form-control"
                                                                        value="{{ $mapping->notes }}">
                                                                </div>
                                                                <div class="col-md-2">
                                                                    <button type="submit" class="btn btn-sm btn-primary w-100">{{ __('Update') }}</button>
                                                                </div>
                                                            </div>
                                                        </form>
                                                    </details>
                                                    <form method="POST" action="{{ route('wa-config.template-mappings.destroy', $mapping->id) }}"
                                                        onsubmit="return confirm('Hapus mapping ini?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger">{{ __('Hapus') }}</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="11" class="text-center">{{ __('Belum ada mapping template.') }}</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
