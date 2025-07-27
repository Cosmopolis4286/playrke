@extends('layouts.layout-online')
<title>Recargar Saldo - Pago Móvil</title>
@section('content')
    <div class="row">
        <div class="col-sm-8 col-md-6 col-lg-5" style="margin:0 auto;">
            <div class="card bg-rke">
                <div class="card-header">
                    <a href="{{ url('/Wallet') }}" title="Regresar" style="position:relative;top:-1px;font-size:18px;">
                        <i class="fas fa-chevron-circle-left"></i>
                    </a>
                    <span style="margin-left:5px; font-size:19px;">
                        Recarga&nbsppor&nbspPago&nbspMóvil&nbsp
                    </span>
                </div>
                @if ($pagoMovil != null)
                    <div class="col-11 mt-2 mb-n2 alert alert-primary text-center" style="margin:0 auto;">
                        @if ($comp_dp != null && $comp_dp->status == 1)
                            <span style="font-size:16px">¡Aún tienes un depósito en verificación!<br></span>
                        @endif
                        <span style="font-size:18px">
                            Monto mínimo de depósito: Bs {{ number_format($min_dp_bs, 2) }}
                        </span>
                    </div>
                    <div class="card-body mb-n3">
                        <form action="{{ route('Wallet.storePM') }}" method="post" enctype="multipart/form-data">@csrf
                            <div class="row">
                                <span style="font-size: 17px" class="col-12">
                                    <b>1°</b> Haz el Pago Movil a esta cuenta:</span>
                                <div class="col-6 col-sm-4">
                                    <label class="lb_in">Cédula:</label>
                                    <div>{{ $pagoMovil->type_id }}{{ $pagoMovil->cedula }}</div>
                                </div>
                                <div class="col-6 col-sm-4">
                                    <label class="lb_in">Banco:</label>
                                    <input type="hidden" name="id_banco" value="{{ $pagoMovil->banco_id }}">
                                    <div>{{ $pagoMovil->banco->codigo }} - {{ $pagoMovil->banco->nombre }}</div>
                                </div>
                                <div class="col-6 col-sm-4">
                                    <label class="lb_in">Teléfono:</label>
                                    <div>0{{ $pagoMovil->codigo }}{{ $pagoMovil->telefono }}</div>
                                </div>
                            </div>
                            <hr>
                            <span style="font-size: 17px">
                                <b>2°</b> Ingresa los datos del pago:</span>
                            <div class="row">
                                <div class="col-sm-6">
                                    <label class="lb_in">Monto enviado</label>
                                    <div style="display: flex">
                                        <div class="form-control_d col-md-4" style="max-width: 42.8px;">Bs</div>
                                        <input type="number" id="monto" name="monto" class="form-control"
                                            step="0.01"
                                            onKeyUp="if(this.value>{{ $max_dp_bs }} ){
                                                this.value='{{ $max_dp_bs }}';
                                        }else if(this.value<{{ $min_dp_bs }}){
                                            this.value='{{ $min_dp_bs }}';}"
                                            data-val-required="El monto es requerido." data-val="true">
                                    </div>
                                    <span data-valmsg-for="monto" data-valmsg-replace="true" style="color: red;">
                                </div>
                                <div class="col-sm-6">
                                    <label class="lb_in">N° Referencia</label>
                                    <input type="text" id="ref_n" name="num_ref" class="form-control"
                                        data-val-required="Información requerida." data-val="true"
                                        data-val-length="Se requieren nueve dígitos." data-val-length-min="9"
                                        data-val-length-max="9">
                                    <span data-valmsg-for="num_ref" data-valmsg-replace="true" style="color: red;">
                                </div>
                                <div class="col-sm-12">
                                    <label class="lb_in">Comprobante</label>
                                    <input class="form-control" type="file" name="capture"
                                        data-val-required="Información requerida" data-val="true">
                                    <span data-valmsg-for="capture" data-valmsg-replace="true" style="color: red;">
                                </div>
                            </div>
                            <div class="col-12 mt-3 text-center">
                                @if ($comp_dp == null || $comp_dp->status != 1)
                                    <button class="sp_c btn btn-primary profile-button" type="submit">Procesar</button>
                                @endif
                            </div>
                        </form>
                    </div>
                @else
                    <div class="mt-3 col-12">
                        <div class="alert alert-danger alert" style="display: flex">
                            <strong>Actualmente no está disponible este método</strong>
                        </div>
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
        $("#ref_n").on('input', function() {
            $(this).val($(this).val().replace(/[^0-9.]/g, ''));
            if (this.value.length > 9)
                this.value = this.value.slice(0, 9);
        });
        $(".replay").click(function(e) {});
    </script>
@endsection
