@extends('layouts.layout-online')
<title>Depositar Tether USDT</title>
@section('content')
    <div class="row">
        <div class="col-sm-8 col-lg-5" style="margin:0 auto;">
            <div class="card bg-rke">
                <div class="card-header">
                    <a href="{{ url('/Wallet') }}" title="Regresar" style="position:relative;top:-1px;font-size:18px;">
                        <i class="fas fa-chevron-circle-left"></i>
                    </a>
                    <span style="font-size:19.5px;">Depósito&nbsppor&nbspBinance&nbspPay&nbsp</span>
                    {{-- <a href="" style="font-size:15.5px;">Red&nbspBlockchain</a> --}}
                </div>
                @if ($BPay != null)
                    <form id="form_dp_bpay" action="{{ route('Wallet.storeBPay') }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        <div class="col-11 mt-2 mb-n1 alert alert-primary text-center" style="margin:0 auto;">
                            @if ($comp_dp != null && $comp_dp->status == 1)
                                ¡Aún tienes un depósito (USDT) en verificación!<br>
                            @endif
                            Depósito mínimo: {{ number_format($limits[2]->valor, 2) }} USDT
                        </div>
                        <div class="card-body mt-n3">
                            <div class="row mt-3">
                                <span style="font-size: 17px" class="col-12">
                                    <b>1°</b> Envia los USDT a esta cuenta:</span>
                                <div class="col-12">
                                    <h5 class="lb_in">Pay ID :
                                        <small>PlayRKE</small>
                                        <span style="user-select: all">{{ $BPay->amount_id }}</span>
                                    </h5>
                                </div>
                            </div>
                            <hr class="mb-1">
                            <span style="font-size: 17px">
                                <b>2°</b> Ingresa los datos del pago:</span>
                            <div class="row">
                                <div class="col-sm-5">
                                    <label class="lb_in">Monto</label>
                                    <input id="crypto_cant" type="number" name="crypto_cant" class="form-control"
                                        placeholder="USDT" step="0.01" max="5000"
                                        onKeyUp="if(this.value>5000){this.value='5000';}else if(this.value<{{ $limits[2]->valor }}){this.value='{{ $limits[2]->valor }}';}"
                                        required>
                                </div>
                                <div class="col-sm-7">
                                    <label class="lb_in">ID de la orden</label>
                                    <input id="id_orden" type="text" name="id_orden" class="form-control"
                                        placeholder="ID de la orden">
                                </div>
                                <div class="col-sm-12">
                                    <label class="lb_in">Comprobante</label>
                                    <input class="form-control" type="file" name="capture" id="capture">
                                </div>
                            </div>
                            <div class="col-12 mt-3 text-center">
                                @if ($comp_dp == null || $comp_dp->status != 1)
                                    <button class="sp_c btn btn-primary profile-button" type="submit">Procesar</button>
                                @endif
                            </div>
                        </div>
                    </form>
                @else
                    <div class="mt-3 col-12">
                        <div class="alert alert-danger alert" style="display: flex">
                            <strong>Actualmente no está disponible este método</strong>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endsection
    @section('scripts')
        <script>
            $().ready(function() {
                $("form").keypress(function(e) {
                    if (e.which == 13) {
                        return false;
                    }
                });
                var inp = document.getElementById("crypto_cant");
                inp.addEventListener('input', function(event) {
                    event.target.value = event.target.valueAsNumber.toFixed(2);
                });

                $("#id_orden").on('input', function(evt) {
                    $(this).val($(this).val().replace(/[^0-9]+/g, ""));
                    if (this.value.length > 18)
                        this.value = this.value.slice(0, 18);
                });
                $("#form_dp_bpay").validate({
                    rules: {
                        id_orden: {
                            required: true,
                            minlength: 18,
                            maxlength: 18
                        },
                        capture: {
                            required: true
                        }
                    },
                    messages: {
                        id_orden: "Se requieren 18 digitos",
                        fecha_recarga: "Información requerida",
                        crypto_cant: "Información requerida"
                    }
                });
            });
        </script>
    @endsection
