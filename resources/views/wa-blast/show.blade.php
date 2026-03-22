@extends('layouts.app')

@section('title', __('Detail of WA Blast'))

@section('content')
    <div class="page-heading">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-md-8 order-md-1 order-last">
                    <h3>{{ __('WA Blast') }}</h3>
                    <p class="text-subtitle text-muted">
                        {{ __('Detail of WA Blast.') }}
                    </p>
                </div>

                <x-breadcrumb>
                    <li class="breadcrumb-item">
                        <a href="{{route('dashboard')}}">{{ __('Dashboard') }}</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('wa-blast.index') }}">{{ __('WA Blast') }}</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        {{ __('Detail') }}
                    </li>
                </x-breadcrumb>
            </div>
        </div>

        <section class="section">
            <div class="row">
                <div class="col-12 col-xl-8 col-lg-8">
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-center align-items-center" style="height: 25rem" id="digidaw-velixs">
                                @if ($waBlast->status == 'CONNECTED')
                                    <div class="d-block">
                                        <div class="d-flex justify-content-center">
                                            <div class="sk-fold sk-secondary">
                                                <div class="sk-fold-cube"></div>
                                                <div class="sk-fold-cube"></div>
                                                <div class="sk-fold-cube"></div>
                                                <div class="sk-fold-cube"></div>
                                            </div>
                                        </div>
                                        <div class="d-block" style="padding-top:40px">
                                            <div class="text-muted">WAITING FOR SERVER RESPONSE</div>
                                        </div>
                                    </div>
                                @else
                                    <div class="d-block">
                                        <div class="d-flex justify-content-center">
                                            <div class="sk-fold sk-secondary">
                                                <div class="sk-fold-cube"></div>
                                                <div class="sk-fold-cube"></div>
                                                <div class="sk-fold-cube"></div>
                                                <div class="sk-fold-cube"></div>
                                            </div>
                                        </div>
                                        <div class="d-block" style="padding-top:40px">
                                            <div class="text-muted" id="status-waiting">CLICK START SESSION !</div>
                                        </div>
                                    </div>
                                    <div class="d-block">
                                        <div class="text-center" style="position: absolute; right: 0; bottom: 30px; left: 0;">
                                            <button class="btn btn-primary startbutton">START SESSION</button>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="info-container">
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <span class="fw-semibold me-1">Device Name :</span>
                                        <span>{{ $waBlast->session_name }}</span>
                                    </li>
                                    <span id="content-detail">
                                        <li class="mb-2">
                                            <span class="fw-semibold me-1">Session Name :</span>
                                            <span>-</span>
                                        </li>
                                        <li class="mb-2">
                                            <span class="fw-semibold me-1">Whatsapp Number :</span>
                                            <span>-</span>
                                        </li>
                                    </span>
                                </ul>
                                <div class="d-flex justify-content-center mt-5">
                                    <button class="btn w-50 is-logout btn-label-danger suspend-user waves-effect btn-danger">Log out</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Logs</h6>
                    <div class="table-responsive text-nowrap">
                        <table class="table table-striped border-top">
                            <tbody id="logger">

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('css')
    <meta name="device_id" content="{{ $waBlast->id }}">
    <meta name="device_status" content="{{ $waBlast->status }}">
@endpush

@push('js')
    <script src=" {{ asset('libvelixs') }}/ilsya_client.js"></script>

    <script>
        var ilsya_client = new IlsyaClient(socket);

        ilsya_client.init();

        $(document).on('click', ".startbutton", function(e) {
            e.preventDefault()
            $(this).attr('disabled', true)
            $("#status-waiting").html('WAITING FOR SERVER RESPONSE');
            $(this).html('<span class="spinner-grow me-1" role="status" aria-hidden="true"></span>LOADING... ')
            ilsya_client.startSession()
        });

        $(document).on('click', ".refresh-page", function(e) {
            e.preventDefault()
            location.reload();
        });

        $(document).on('click', ".is-logout", function(e) {
            e.preventDefault()
            $(this).attr('disabled', true)
            $("#status-waiting").html('WAITING FOR SERVER RESPONSE');
            $(this).html('<span class="spinner-grow me-1" role="status" aria-hidden="true"></span>Loading... ')
            ilsya_client.logout()

            setTimeout(function() {
                $(".is-logout").attr('disabled', false)
                $(".is-logout").html('Log out')
            }, 8000);
        });
    </script>
@endpush

