@extends('layouts.app')

@section('title', __($title ?? ''))

@section('content')
    @php
        $appMode = request()->boolean('app') || (!empty($isWebViewApp) && $isWebViewApp);
        $user = auth()->user();
        $visibleTabs = [];
        foreach (($tabs ?? []) as $t) {
            $perm = $t['permission'] ?? null;
            $ok = true;
            if (!empty($perm) && $user) {
                if (is_array($perm)) {
                    $ok = false;
                    foreach ($perm as $p) {
                        if ($user->can($p)) {
                            $ok = true;
                            break;
                        }
                    }
                } else {
                    $ok = $user->can($perm);
                }
            }
            if ($ok) {
                $visibleTabs[] = $t;
            }
        }
        $activeTab = $tab ?? (($visibleTabs[0]['key'] ?? '') ?: '');
    @endphp

    <div class="page-heading hub-page">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __($title ?? '') }}</h3>
                    @if (!empty($subtitle))
                        <p class="text-subtitle text-muted">{{ __($subtitle) }}</p>
                    @endif
                </div>
                <x-breadcrumb>
                    <li class="breadcrumb-item"><a href="/dashboard">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __($title ?? '') }}</li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <x-alert></x-alert>

            <div class="hub-flex">
                <ul class="nav nav-tabs mb-3 hub-tabs">
                    @foreach ($visibleTabs as $t)
                        <li class="nav-item">
                            <a class="nav-link @if ($activeTab === ($t['key'] ?? '')) active @endif" href="{{ route($routeName, ['tab' => $t['key'], 'app' => $appMode ? 1 : null]) }}">{{ __($t['label'] ?? '') }}</a>
                        </li>
                    @endforeach
                </ul>

                @php
                    $src = null;
                    foreach ($visibleTabs as $t) {
                        if (($t['key'] ?? '') === $activeTab) {
                            $src = $t['src'] ?? null;
                            break;
                        }
                    }
                @endphp
                @if (!empty($src))
                    @php
                        $finalSrc = $src;
                        $tab2 = (string) request()->query('tab2', '');
                        if ($tab2 !== '') {
                            $finalSrc .= (str_contains($finalSrc, '?') ? '&' : '?') . 'tab=' . urlencode($tab2);
                        }
                        $tab3 = (string) request()->query('tab3', '');
                        if ($tab3 !== '') {
                            $finalSrc .= (str_contains($finalSrc, '?') ? '&' : '?') . 'tab2=' . urlencode($tab3);
                        }
                        $extra = request()->query();
                        unset($extra['tab'], $extra['tab2'], $extra['tab3'], $extra['app']);
                        foreach ($extra as $k => $v) {
                            if ($v === null || $v === '') continue;
                            $finalSrc .= (str_contains($finalSrc, '?') ? '&' : '?') . urlencode((string) $k) . '=' . urlencode((string) $v);
                        }
                        if ($appMode && !str_contains($finalSrc, 'app=1')) {
                            $finalSrc .= (str_contains($finalSrc, '?') ? '&' : '?') . 'app=1';
                        }
                    @endphp
                    <iframe class="hub-iframe" src="{{ $finalSrc }}"></iframe>
                @else
                    <div class="text-muted">{{ __('Tidak ada menu yang bisa ditampilkan.') }}</div>
                @endif
            </div>
        </section>
    </div>
@endsection
