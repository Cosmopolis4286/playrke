@extends('layouts.app_rke')
<title>Retirar USDT - Binance Pay</title>
@section('content')
    <div class="row">
        <div class="col-sm-8 col-lg-5" style="margin:0 auto;">
            <form action="{{ route('Wallet.run_rt_PM') }}" method="POST" id="form_dp_bpay">@csrf
                <div class="card bg-rke">
                    <div class="card-header">
                        <a href="{{ url('/Wallet') }}" title="Regresar" style="position:relative;top:-1px;font-size:18px;">
                            <i class="fas fa-chevron-circle-left"></i>
                        </a>
                        <span style="font-size:19.5px;">Retiro&nbsppor&nbspBinance&nbspPay&nbsp</span>
                        {{-- <a href="" style="font-size:15.5px;">Red&nbspBlockchain</a> --}}
                    </div>
                    <div class="col-11 mt-2 mb-n1 alert alert-primary text-center" style="margin:0 auto;">
                        @if ($comp_rtB != null)
                            @if ($comp_rtB->status == 1)
                                <span style="font-size:16px">¡Aún tienes un retiro (USDT) en espera!<br></span>
                            @endif
                        @endif
                        <span style="font-size:18px">
                            Monto mínimo a retirar: {{ number_format($limits[4]->valor, 2) }} USDT</span>
                    </div>
                    <div class="card-body mt-n3">
                        <div class="row mb-n2">
                            <div class="col-12 mt-1">
                                <span style="font-size: 16px; color:rgb(13, 35, 83)">
                                    <b>Tu cuenta receptora:</b>
                                </span>
                            </div>
                            <div class="col-6">
                                <label class="lb_in">Tu Pay ID:</label>
                                <h6>{{ $Bpay->amount_id }}</h6>
                            </div>
                            <div class="col-6">
                                <label class="lb_in">Email Binance:</label>
                                <h6>{{ $Bpay->email }}</h6>
                            </div>
                        </div>
                        <hr class="mb-1">
                        <div class="row">
                            <div class="col-6">
                                <label class="lb_in">Saldo disponible</label>
                                <div class="form-control" id="saldo_mone">
                                    {{ number_format($wallet->usdt, 2) }} USDT</div>
                                <input type="hidden" name="saldo_mone" value="{{ $wallet->usdt }}">
                            </div>
                            <div class="col-6">
                                <label class="lb_in">Monto de retiro</label>
                                <input id="monto" type="number" name="monto" class="form-control" step="0.01"
                                    data-val-required="Monto requerido." data-val-range="Monto no permitido" data-val="true"
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
                            <div class="col-12 mt-3 text-center">
                                @if ($comp_rtB == null || $comp_rtB->status != 0)
                                    <button class="sp_c btn btn-primary profile-button" type="submit">Procesar</button>
                                @endif
                            </div>
                        </div>
                    </div>
            </form>
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
            var inp = document.getElementById("monto")
            inp.addEventListener('input', function(event) {
                event.target.value = event.target.valueAsNumber.toFixed(2);
            });
        });

        $("#monto").click(function() {
            let init = setTimeout(function refres() {
                var saldo = {{ $wallet->usdt }},
                    com_rt = @json($limits[3]->valor);
                var valor = document.getElementsByName("monto")[0].value,
                    com_rt = ((valor * com_rt) / 100).toFixed(2);
                if (!isNaN(valor)) {
                    if (valor <= saldo) {
                        saldo = saldo - valor;
                        document.getElementById("saldo_mone").innerHTML = Intl.NumberFormat("en-US").format(
                            saldo.toFixed(2)) + '<span style="font-size:11px;"> USDT<span>';
                        document.getElementById("com").innerHTML = 'Monto a recibir: ' +
                            (valor - com_rt).toFixed(2) + '<span style="font-size:11.5px;"> USDT<span>';
                    } else {
                        document.getElementById("com").innerHTML = '';
                    }
                }
                setTimeout(refres, 1200);
            })
        })
    </script>
@endsection
