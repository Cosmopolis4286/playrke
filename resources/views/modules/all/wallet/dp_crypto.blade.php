@extends('layouts.layout-online')
<title>Depositar Tether USDT</title>
@section('content')
    <div class="row">
        <div class="col-sm-8 col-lg-5" style="margin:0 auto;">
            <form action="{{ route('Wallet.storeBlock') }}" method="POST" id="form_dp_cryptos">@csrf
                <div class="card bg-rke">
                    <div class="card-header" id="headingOne">
                        <a href="{{ url('/Wallet') }}" title="Regresar"
                            style="position:relative;top:-1px;font-size:18px;">
                            <i class="fas fa-chevron-circle-left"></i>
                        </a>
                        <span style="font-size:20px;">Depósito&nbsppor&nbspBlockchain&nbsp <a
                                href="{{ route('Wallet.createBPay') }}"
                                style="font-size:15.5px;">Binance&nbspPay</a></span>
                    </div>
                    <div class="col-11 mt-2 mb-n1 alert alert-primary text-center" style="margin:0 auto;">
                        @if ($comp_dp == null)
                        @else
                            @if ($comp_dp->estatus == 0)
                                ¡Actualmente tiene un depósito USDT en espera!<br>
                            @endif
                        @endif
                        Depósito mínimo: {{ number_format($limits[2]->valor, 2) }} USDT
                    </div>
                    @if (Count($list_wallet) != 0)
                        <div class="card-body mt-n3">
                            <div class="row">
                                <div class="col-6 col-lg-6">
                                    <label class="lb_in">Moneda</label>
                                    <input type="text" class="form-control_d" value="Tether (USDT)" disabled>
                                </div>
                                <div class="col-6 col-lg-6">
                                    <label class="lb_in">Red de retiro</label>
                                    <input type="text" class="form-control_d" value="Tron (TRC20)" disabled>
                                </div>
                                <div class="col-sm-12 col-lg-12">
                                    <label class="lb_in">Dirección de Wallet</label>
                                    <input type="text" class="form-control" value="{{ $list_wallet[0]->crypto_direcc }}"
                                        disabled>
                                </div>
                            </div>
                            <hr class="mb-1">
                            <div class="row">
                                <div class="col-6 col-lg-6">
                                    <label class="lb_in">Fecha</label>
                                    <input id="fecha" type="date" name="fecha_recarga" class="form-control"
                                        max="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" required>
                                </div>
                                <div class="col-6 col-lg-6">
                                    <label class="lb_in">Cantidad</label>
                                    <input id="crypto_cant" type="number" name="crypto_cant" class="form-control"
                                        placeholder="USDT enviados" step="0.1" max="5000" required
                                        onKeyUp="if(this.value>5000){this.value='5000';}else if(this.value<{{ $limits[2]->valor }}){this.value='{{ $limits[2]->valor }}';}">
                                </div>
                                <div class="col-12 col-lg-12">
                                    <label class="lb_in">TxID</label>
                                    <input id="crypto_txid" type="text" name="crypto_txid" class="form-control"
                                        placeholder="ID de la transacción (TxID)" value="">
                                </div>
                                <div class="col-12 mt-n3 mb-n1 text-center">
                                    @if ($comp_dp == null)
                                        <label class="lb_in">&nbsp</label>
                                        <div>
                                            <button id="save_profile" class="sp_c btn btn-primary profile-button"
                                                type="submit">Procesar</button>
                                        </div>
                                    @else
                                        @if ($comp_dp->estatus != 0)
                                            <div>
                                                <button id="save_profile" class="sp_c btn btn-primary profile-button"
                                                    type="submit">Procesar</button>
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="mt-3 col-12">
                                <div class="alert alert-danger alert" style="display: flex">
                                    <strong>Actualmente no está disponible este método</strong>
                                </div>
                            </div>
                    @endif
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
            var inp = document.getElementById("monto");
            inp.addEventListener('input', function(event) {
                event.target.value = event.target.valueAsNumber.toFixed(2);
            });

            $("#crypto_txid").on('input', function(evt) {
                $(this).val($(this).val().replace(/[^A-Za-z0-9]+/g, ""));
                if (this.value.length > 67)
                    this.value = this.value.slice(0, 67);
            });
            $("#form_dp_cryptos").validate({
                rules: {
                    crypto_txid: {
                        required: true,
                        minlength: 67,
                        maxlength: 67
                    },
                },
                messages: {
                    crypto_txid: "Ingrese un TxID válido.",
                    fecha_recarga: "Información requerida",
                    crypto_cant: "Información requerida"
                }
            });
        });
    </script>
@endsection
