@extends('layouts.app_rke')
<title>Retirar Saldo - Pago Móvil</title>
@section('content')
    <div class="row">
        <div class="col-sm-7 col-md-8 col-lg-6" style="margin:0 auto;">
            <div class="card bg-rke">
                <div class="card-header">
                    <a href="{{ url('/Wallet') }}" title="Regresar" style="position:relative;top:-1px;font-size:18px;">
                        <i class="fas fa-chevron-circle-left"></i>
                    </a>
                    <span style="margin-left:5px; font-size:19px;">
                        Retiro&nbsppor&nbspPago&nbspMóvil&nbsp
                    </span>
                </div>
                @if ($pagoMovil != null)
                    <div class="col-11 mt-2 mb-n3 alert alert-primary text-center" style="margin:0 auto;">
                        @if ($comp_rt == null)
                        @else
                            @if ($comp_rt->estatus == 0)
                                <span style="font-size:16px">¡Actualmente tiene un retiro en espera!<br></span>
                            @endif
                        @endif
                        <span style="font-size:18px">Monto mínimo a retirar: Bs {{ $min_dp_bs }}</span>
                    </div>
                    <form action="{{ route('Wallet.run_rt_PM') }}" method="POST" id="form">@csrf
                        <div class="card-body mb-n1">
                            <div class="row">
                                <div class="col-12 mt-1">
                                    <span style="font-size: 16px; color:rgb(13, 35, 83)">
                                        <b>Tu cuenta receptora:</b>
                                    </span>
                                </div>
                                <div class="col-6 col-md-4">
                                    <label class="lb_in">Cédula</label>
                                    <div class="form-control_d">
                                        {{ $pagoMovil->type_id }}{{ $pagoMovil->cedula }}
                                    </div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <label class="lb_in">Banco</label>
                                    <input type="hidden" name="id_banco" value="{{ $pagoMovil->banco->id }}">
                                    <div class="form-control_d">
                                        {{ $pagoMovil->banco->codigo }}&nbsp{{ $pagoMovil->banco->nombre }}
                                    </div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <label class="lb_in">Teléfono</label>
                                    <div class="form-control_d">
                                        0{{ $pagoMovil->codigo }}{{ $pagoMovil->telefono }}
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-6 col-md-4">
                                    <label class="lb_in">Saldo disponible</label>
                                    <div class="form-control" id="saldo_mone">Bs
                                        {{ number_format($wallet->balance, 2) }}</div>
                                    <input type="hidden" name="saldo_mone" value="{{ $wallet->balance }}">
                                </div>
                                <div class="col-6 col-md-4">
                                    <label class="lb_in">Monto de retiro</label>
                                    <div style="display: flex">
                                        <div class="form-control_d" style="max-width:42.8px;">Bs</div>
                                        <input id="monto" type="number" name="monto" class="form-control"
                                            step="0.01" data-val-required="Monto requerido."
                                            data-val-range="Monto no permitido." data-val="true"
                                            data-val-range-min="{{ $min_dp_bs }}"
                                            data-val-range-max="{{ $limits[5]->valor * $CommerceExchangeRate->CommerceExchangeRate_RKE }}"
                                            onKeyUp="if(this.value<{{ $min_dp_bs }}&&{{ $wallet->balance }}>={{ $min_dp_bs }})
                                                {this.value='{{ $min_dp_bs }}';}
                                                else if(this.value>{{ $wallet->balance }}&&{{ $wallet->balance }}>={{ $limits[5]->valor * $CommerceExchangeRate->CommerceExchangeRate_RKE }})
                                                {this.value='{{ number_format($limits[5]->valor * $CommerceExchangeRate->CommerceExchangeRate_RKE, 2) }}'}
                                                else if(this.value>{{ $wallet->balance }}&&{{ $wallet->balance }}<{{ $limits[5]->valor * $CommerceExchangeRate->CommerceExchangeRate_RKE }})
                                                {this.value='{{ number_format($wallet->balance, 2) }}'}
                                                else if(this.value<{{ $min_dp_bs }}&&{{ $wallet->balance }}<{{ $min_dp_bs }})
                                                {this.value='{{ number_format($wallet->balance, 2) }}'}">
                                    </div>
                                    <div id="com"style="color: green;"></div>
                                    <span data-valmsg-for="monto" data-valmsg-replace="true" style="color: red;">
                                </div>
                                <div class="col-sm-6 col-md-4">
                                    @if ($comp_rt == null || $comp_rt->estatus != 0)
                                        <label class="lb_in">&nbsp</label>
                                        <div>
                                            <button id="sub" class="sp_c form-control btn btn-primary profile-button"
                                                type="submit">Procesar</button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </form>
                @else
                    <div class="mt-3 col-12">
                        <div class="alert alert-danger alert" style="display: flex">
                            <strong>Actualmente no posee un Pago Móvil asociado</strong>
                        </div>
                @endif
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        $(document).ready(function() {
            $("form").keypress(function(e) {
                if (e.which == 13) {
                    return false;
                }
            });
            var inp = document.getElementById("monto");
            inp.addEventListener('input', function(event) {
                event.target.value = event.target.valueAsNumber.toFixed(2);
            });
        });
        $(document).ready(function() {
            var screen = $('#loading-screen');
            loader_show(screen);

            function loader_show(screen) {
                $(document)
                    .ajaxStart(function() {
                        screen.fadeIn();
                    })
                    .ajaxStop(function() {
                        screen.fadeOut();
                    });
            }
        });
        $("#monto").click(function() {
            let init = setTimeout(function refres() {
                var saldo = {{ $wallet->balance }},
                    com_rt = @json($limits[3]->valor);
                var valor = document.getElementsByName("monto")[0].value,
                    com_rt = ((valor * com_rt) / 100).toFixed(2);
                if (!isNaN(valor)) {
                    if (valor <= saldo) {
                        saldo = saldo - valor;
                        document.getElementById("saldo_mone").innerHTML = 'Bs ' + Intl.NumberFormat("en-US")
                            .format(
                                saldo.toFixed(2));
                        document.getElementById("com").innerHTML = 'Recibes ' + 'Bs ' + (valor - com_rt)
                            .toFixed(2);
                    } else {
                        document.getElementById("com").innerHTML = '';
                    }
                }
                setTimeout(refres, 1200);
            })
        })
    </script>
@endsection
