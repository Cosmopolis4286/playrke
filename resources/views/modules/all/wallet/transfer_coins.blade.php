@extends('layouts.app_rke')
<title>Transferir Saldo - PlayRKE</title>
@section('content')
    <div class="row">
        <div class="col-md-4" style="margin:0 auto;">
            <div class="card bg-rke">
                <div class="card-header">
                    <div class="sub_ti-tle">
                        <a href="{{ route('Wallet.index') }}" title="Regresar"
                            style="position:relative;top:-1px;font-size:18px;">
                            <i class="fas fa-chevron-circle-left"></i>
                        </a>
                        <span style="margin-left:7px; font-size:20px;">Transferir {{ $wallet->info }}</span>
                        <a href="#" class="ml-2 icon_mone">
                            <i class="fa-solid fa-clock-rotate-left mt-1"></i>
                        </a>
                    </div>
                    <a style="font-size:15.5px;" href="{{ route('Wallet.transfer_usdt') }}">
                        Transferir USDT
                        <img class="mb-1" src="{{ asset('imgSystem/tether.svg') }}" style="width:14.5px;">
                    </a>
                </div>
                <div class="card-body">
                    <div class="mb-n4 mt-n1" style="justify-content:right;">
                        <a href="#" style="color:#D8E2EC;"><i class="fa-solid fa-clock-rotate-left mr-3"></i></a>
                    </div>
                    <div class="col-12 mt-2 mb-1 alert alert-primary text-center">
                        Importe mínimo: {{ $wallet->name }} {{ $ts_cm_bs }}
                    </div>
                    @if (Session::has('message'))
                        <script>
                            const Toast = Swal.mixin({
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 1520,
                                timerProgressBar: true,
                            })
                            if (@json(Session::has('repetido'))) {
                                Toast.fire({
                                    icon: 'error',
                                    html: '<div style="padding-top:1.6% !important; font-size:1rem;">{{ Session::get('message') }}</div>'
                                })

                            } else if (@json(Session::has('exito'))) {
                                Toast.fire({
                                    icon: 'success',
                                    html: '<div style="padding-top:1.6% !important; font-size:1rem;">{{ Session::get('message') }}</div>'
                                })
                            } else if (@json(Session::has('inex'))) {
                                Toast.fire({
                                    icon: 'error',
                                    html: '<div style="padding-top:1.6% !important; font-size:1rem;">{{ Session::get('message') }}</div>'
                                })
                            } else if (@json(Session::has('exce'))) {
                                Toast.fire({
                                    icon: 'warning',
                                    html: '<div style="padding-top:1.6% !important; font-size:1rem;">{{ Session::get('message') }}</div>'
                                })
                            } else if (@json(Session::has('insuf'))) {
                                Toast.fire({
                                    icon: 'warning',
                                    html: '<div style="padding-top:1.6% !important; font-size:1rem;">{{ Session::get('message') }}</div>'
                                })
                            }
                        </script>
                    @endif
                    <form action="{{ route('Wallet.transferUser') }}" method="POST">@csrf
                        <div class="row">
                            <input type="hidden" name="moneda" value="1">
                            <div class="col-md-6">
                                <label class="lb_in">Saldo Disponible</label>
                                <input id="Wallet" type="text" name="Wallet" class="form-control"
                                    value="{{ $wallet->name }} {{ number_format($wallet->saldo, 2) }}"
                                    style="pointer-events: none;">
                            </div>
                            <div class="col-md-6">
                                <label class="lb_in">Monto a Enviar</label>
                                <div style="display: flex">
                                    <div class="form-control_d col-md-4" style="max-width: 43.5px;">{{ $wallet->name }}
                                    </div>
                                    <input id="monto" type="number" min="1" name="monto" class="form-control"
                                        required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="" class="lb_in">Usuario</label>
                                <input type="text" placeholder="Transferir a:" name="username" class="form-control"
                                    value="{{ old('username') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="lb_in">&nbsp</label>
                                <button id="enviar" class="sp_c btn btn-primary form-control" type="submit">
                                    <i class="fas fa-exchange-alt"></i>
                                    &nbspTransferir
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
