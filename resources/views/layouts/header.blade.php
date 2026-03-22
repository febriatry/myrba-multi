<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - {{ config('app.name', 'Laravel') }}</title>
    <link rel="stylesheet" href="{{ asset('mazer') }}/css/main/app.css">
    <link rel="stylesheet" href="{{ asset('mazer') }}/css/main/app-dark.css">
    <link rel="stylesheet" href="{{ asset('mazer') }}/css/shared/iconly.css">
    <link rel="stylesheet" href="{{ asset('css/myrba-layout.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/all.min.css"
    integrity="sha512-SzlrxWUlpfuzQ+pcUCosxcglQRNAq/DZjVsC0lE40xsADsfeQoEypE+enwcOiGjk/bSuGGKHEyjSoQ1zVisanQ=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="{{ asset('css/select2.min.css') }}">

    @stack('css')
</head>

@php
    $appMode = request()->boolean('app') || (!empty($isWebViewApp) && $isWebViewApp);
@endphp
<body class="{{ request()->boolean('embed') ? 'embed' : '' }} {{ $appMode ? 'appview' : '' }}">
<input type="checkbox" id="toggle-dark" style="display:none">
@php
    $user = auth()->user();
    $appCtx = [
        'app_mode' => $appMode,
        'user' => $user ? ['id' => (int) $user->id, 'name' => (string) $user->name] : null,
        'roles' => $user ? $user->getRoleNames()->values()->all() : [],
        'permissions' => $user ? $user->getAllPermissions()->pluck('name')->values()->all() : [],
    ];
@endphp
<script>
    window.__MYRBA_APP_CONTEXT__ = {!! json_encode($appCtx) !!};
</script>
<div id="app">
        @if (!request()->boolean('embed') && !$appMode)
            @include('layouts.sidebar')
        @endif
        <div id="main">
            @if (!request()->boolean('embed') && !$appMode)
                <header class="mb-3">
                    <nav class="navbar navbar-expand navbar-light navbar-top">
                        <div class="container-fluid">
                            <a href="#" class="burger-btn d-block d-lg-none">
                                <i class="bi bi-justify fs-3"></i>
                            </a>
                            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                                data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                                aria-expanded="false" aria-label="Toggle navigation">
                                <span class="navbar-toggler-icon"></span>
                            </button>
                            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                                <ul class="navbar-nav ms-auto mb-lg-0">
                                    <li class="nav-item dropdown me-1">
                                    </li>
                                </ul>
                                @auth
                                    <div class="dropdown">
                                        <a href="#" data-bs-toggle="dropdown" aria-expanded="false">
                                            <div class="user-menu d-flex">
                                                <div class="user-name text-end me-3">
                                                    <h6 class="mb-0 text-gray-600">{{ auth()?->user()?->name }}</h6>
                                                    <p class="mb-0 text-sm text-gray-600">
                                                        {{ isset(auth()?->user()?->roles) ? implode(auth()?->user()?->roles?->map(fn($role) => $role->name)->toArray()) : '-' }}
                                                    </p>
                                                </div>
                                                <div class="user-img d-flex align-items-center">
                                                    <div class="avatar avatar-md">
                                                        @if (!auth()?->user()?->avatar)
                                                            <img src="https://www.gravatar.com/avatar/{{ md5(strtolower(trim(auth()?->user()?->email))) }}&s=500"
                                                                alt="Avatar">
                                                        @else
                                                            <img src="{{ asset('/uploads/images/avatars/' . auth()?->user()?->avatar) }}"
                                                                alt="Avatar">
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton"
                                            style="min-width: 11rem;">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('profile') }}"><i
                                                        class="icon-mid bi bi-person-fill me-2"></i>{{ __('My Profile') }}</a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('logout') }}"
                                                    onclick="event.preventDefault();document.getElementById('logout-form-nav').submit();">
                                                    <i class="bi bi-door-open-fill"></i>
                                                    {{ __('Logout') }}
                                                </a>

                                                <form id="logout-form-nav" action="{{ route('logout') }}" method="POST"
                                                    class="d-none">
                                                    @csrf
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                @endauth
                            </div>
                        </div>
                    </nav>
                </header>
            @endif
