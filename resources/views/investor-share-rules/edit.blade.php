@extends('layouts.app')

@section('title', __('Edit Rule Bagi Hasil'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('Edit Rule Bagi Hasil') }}</h3>
                </div>
            </div>
        </div>
        <section class="section">
            <x-alert></x-alert>
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('investor-share-rules.update', $rule->id) }}" method="post">
                        @csrf
                        @method('put')
                        <div class="mb-3">
                            <label class="form-label">{{ __('Investor User') }}</label>
                            <select name="user_id" class="form-control" required>
                                @foreach ($users as $u)
                                    <option value="{{ $u->id }}" @selected((int) $u->id === (int) $rule->user_id)>{{ $u->name }} ({{ $u->email }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Rule Type') }}</label>
                            <select name="rule_type" class="form-control" required>
                                <option value="per_customer" @selected($rule->rule_type === 'per_customer')>per_customer</option>
                                <option value="per_area" @selected($rule->rule_type === 'per_area')>per_area</option>
                                <option value="per_package" @selected($rule->rule_type === 'per_package')>per_package</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Coverage Area (opsional)') }}</label>
                            <select name="coverage_area_id" class="form-control">
                                <option value="">{{ __('-') }}</option>
                                @foreach ($areas as $a)
                                    <option value="{{ $a->id }}" @selected(!empty($rule->coverage_area_id) && (int) $a->id === (int) $rule->coverage_area_id)>{{ $a->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Package (opsional)') }}</label>
                            <select name="package_id" class="form-control">
                                <option value="">{{ __('-') }}</option>
                                @foreach ($packages as $p)
                                    <option value="{{ $p->id }}" @selected(!empty($rule->package_id) && (int) $p->id === (int) $rule->package_id)>{{ $p->nama_layanan }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Mulai Periode (YYYY-MM)') }}</label>
                            <input type="text" name="start_period" class="form-control" value="{{ $rule->start_period }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Amount Type') }}</label>
                            <select name="amount_type" class="form-control" required>
                                <option value="fixed" @selected($rule->amount_type === 'fixed')>fixed</option>
                                <option value="percent" @selected($rule->amount_type === 'percent')>percent</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Amount Value') }}</label>
                            <input type="number" step="0.01" name="amount_value" class="form-control" required value="{{ $rule->amount_value }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Aktif') }}</label>
                            <select name="is_aktif" class="form-control" required>
                                <option value="Yes" @selected($rule->is_aktif === 'Yes')>Yes</option>
                                <option value="No" @selected($rule->is_aktif === 'No')>No</option>
                            </select>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary">{{ __('Simpan') }}</button>
                            <a href="{{ route('investor-share-rules.index') }}" class="btn btn-light-secondary">{{ __('Batal') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>
@endsection

