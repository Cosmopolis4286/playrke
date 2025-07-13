@extends('layouts.layout-online')
<title>Retirar USDT - Blockchain</title>
@section('content')
    <div class="row">
        <div class="col-sm-8 col-lg-5" style="margin:0 auto;">
            <form action="{{ route('Wallet.s_retiroBlock') }}" method="POST">
                @csrf
                <div class="card bg-rke">
                    <div class="card-header" id="headingOne">
                        <a href="{{ url('/Wallet') }}" title="Regresar"
                            style="position:relative;top:-1px;font-size:18px;">
                            <i class="fas fa-chevron-circle-left"></i>
                        </a>
                        <span style="margin-left:5px; font-size:19px;">Retiro&nbsppor&nbspBlockchain&nbsp<a
                                href="{{ route('Wallet.retiroBPay') }}"
                                style="font-size:15.5px;">Binance&nbspPay</a></span>
                    </div>
                    @if (Count($list_wallet) != 0)
                        <div class="col-11 mt-2 mb-n1 alert alert-primary text-center" style="margin:0 auto;">
                            @if ($comp_rt == null)
                            @else
                                @if ($comp_rt->estatus == 0)
                                    ¡Actualmente tiene un retiro USDT en espera!<br>
                                @endif
                            @endif
                            Monto mínimo a retirar: {{ number_format($limits[4]->valor, 2) }} USDT
                        </div>
                        <div class="card-body mt-n3">
                            <div class="row">
                                <div class="col-6 col-lg-6">
                                    <label class="lb_in">Red de retiro</label>
                                    <div class="form-control">Tron (TRC20)</div>
                                </div>
                                <div class="col-sm-12 col-lg-6">
                                    <label class="lb_in">Dirección de Wallet</label>
                                    <div class="form-control">{{ $list_wallet[0]->crypto_direcc }}</div>
                                </div>
                            </div>
                            <hr class="mb-1">
                            <div class="row">
                                <div class="col-6 col-lg-4">
                                    <label class="lb_in">Saldo disponible</label>
                                    <div class="form-control" id="saldo_mone">
                                        {{ number_format($wallet->usdt, 2) }}
                                        <span style="font-size:11px;">USDT<span>
                                    </div>
                                    <input type="hidden" name="saldo_mone" value="{{ $wallet->usdt }}">
                                </div>
                                <div class="col-6 col-lg-6">
                                    <label class="lb_in">Monto de retiro</label>
                                    <input id="monto" type="number" name="monto" class="form-control" step="0.01"
                                        onclick="retiro()" data-val-required="Monto requerido."
                                        data-val-range="Monto no permitido." data-val="true"
                                        data-val-range-min="{{ $limits[4]->valor }}"
                                        data-val-range-max="{{ $limits[6]->valor }}"
                                        onKeyUp="if(this.value<{{ number_format($limits[4]->valor, 2) }}&&{{ $wallet->usdt }}>={{ $limits[4]->valor }})
                                                {this.value='{{ number_format($limits[4]->valor, 2) }}';}
                                                else if(this.value>{{ $wallet->usdt }}&&{{ $wallet->usdt }}>={{ $limits[6]->valor }})
                                                {this.value='{{ number_format($limits[6]->valor, 2) }}'}
                                                else if(this.value>{{ $wallet->usdt }}&&{{ $wallet->usdt }}<{{ $limits[6]->valor }})
                                                {this.value='{{ number_format($wallet->usdt, 2) }}'}
                                                else if(this.value<{{ number_format($limits[4]->valor, 2) }}&&{{ $wallet->usdt }}<{{ $limits[4]->valor }})
                                                {this.value='{{ number_format($wallet->usdt, 2) }}'}">
                                    <div id="com"style="color: green;"></div>
                                    <span data-valmsg-for="monto" data-valmsg-replace="true" style="color: red;">
                                </div>
                                <div class="col-lg-2 text-center">
                                    <label class="lb_in">&nbsp</label>
                                    @if ($comp_rt == null)
                                        <div>
                                            <button id="save_profile"
                                                class=" form-control sp_c btn btn-primary profile-button" type="submit"><i
                                                    class="fa-solid fa-money-bills"></i></button>
                                        </div>
                                    @else
                                        @if ($comp_rt->estatus != 0)
                                            <div>
                                                <button id="save_profile"
                                                    class="form-control sp_c btn btn-primary profile-button"
                                                    type="submit"><i class="fa-solid fa-money-bills"></i></button>
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="mt-3 col-12">
                                <div class="alert alert-danger alert" style="display: flex">
                                    <strong>Actualmente no está disponible este método.</strong>
                                </div>
                            </div>
                    @endif
                </div>
            </form>
        </div>
    </div>
@endsection
@section('validate')
    <script>
        $(document).ready(function() {
            $("form").keypress(function(e) {
                if (e.which == 13) {
                    return false;
                }
            });
            var inp = document.getElementById("monto")
            inp.addEventListener('input', function(event) {
                event.target.value = event.target.valueAsNumber.toFixed(2);
            });
        });

        function retiro() {
            let init = setTimeout(function refres() {
                var saldo = ({{ $wallet->usdt }}),
                    com_rt = "<?php echo $limits[3]->valor; ?>";
                var valor = document.getElementsByName("monto")[0].value,
                    com_rt = ((valor * com_rt) / 100).toFixed(2);
                if (!isNaN(valor)) {
                    if (valor <= saldo && valor >= {{ $limits[4]->valor }}) {
                        saldo = saldo - valor;
                        document.getElementById("saldo_mone").innerHTML = Intl.NumberFormat("en-US").format(
                            saldo.toFixed(2)) + '<span style="font-size:11px;"> USDT<span>';
                        document.getElementById("com").innerHTML = 'Monto a recibir: ' + (valor - com_rt).toFixed(
                            2) + '<span style="font-size:11.5px;"> USDT<span>';
                    } else {
                        document.getElementById("com").innerHTML = '';
                    }
                }
                setTimeout(refres, 1200);
            })
        };
    </script>
@endsection
