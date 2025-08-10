@extends('layouts.layout-online')
<title>PlayRKE - Precio USD</title>
@section('fnd-content')
    <div class="content-wrapper">
    @endsection
    @section('content')
        <div class="row">
            <div class="col-md-4" align="center" style="margin:0 auto;">
                <div class="card bg-rke">
                    <div class="card-header" title="CommerceExchangeRate de Cambio">
                        <h4 class="my-n2">CommerceExchangeRate de Cambio</h4>
                    </div>
                    <div class="card-body">
                        <div class="info-box-content">
                            <div class="fecha">
                                <h5 id="al"></h5>
                            </div>
                            <div class="cotizacion">
                                <h5>DolarToday: Bs <span id="texto"></span></h5>
                                <h5>Sicad: Bs <span id="bcv"></span></h5>
                            </div>
                            <hr>
                            <div class="CommerceExchangeRate_rke" style="margin-bottom:-2px;">
                                <h5><b>CommerceExchangeRate actual: </b>
                                    @if (count($CommerceExchangeRate) > 0)
                                        <span>Bs {{ $CommerceExchangeRate[0]->CommerceExchangeRate_RKE }}</span>
                                    @else
                                        <span class="alert alert-danger">
                                            PlayRKE, no tiene una CommerceExchangeRate de cambio disponible actualmente
                                        </span>
                                    @endif
                                </h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6" style="margin:0 auto;">
                <div class="card bg-rke">
                    <div class="card-header" title="CommerceExchangeRate de Cambio">
                        <h4 class="my-n2" align="center">CommerceExchangeRate Dólar RKE</h4>
                    </div>
                    <form action="{{ route('Commerceexchange-rate.store') }}" method="POST">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <div class="card-body">
                            <div class="info-box-content">
                                <div class="row">
                                    <div class="col-6 col-sm-3">
                                        <label class="labels"style="margin-top:7.5px;margin-bottom:1px;">Dólar API</label>
                                        <input value="{{ $CommerceExchangeRate[0]->CommerceExchangeRate_RKE }}" id="" type="text"
                                            name="dolar_api" class="form-control" style="pointer-events: none;">
                                    </div>
                                    <div class="col-6 col-sm-3">
                                        <label class="labels"style="margin-top:7.5px;margin-bottom:1px;">%Porcentaje</label>
                                        <input id="p_CommerceExchangeRate" type="number" name="porcentaje_dolar" class="form-control"
                                            onchange="porcentaje()" step="0.01" min="-2.5" max="4"
                                            onKeyUp="if(this.value>4){this.value='4';}else if(this.value<-2.5){this.value='-2.5';}">
                                    </div>
                                    <div class="col-6 col-sm-3">
                                        <label class="labels"style="margin-top:7.5px;margin-bottom:1px;">Total</label>
                                        <input id="dolar_rke" type="text" name="dolar_rke" class="form-control"
                                            style="pointer-events: none;">
                                    </div>
                                    <div class="save_pf col-6 col-sm-3">
                                        <label class="lb_in">&nbsp</label>
                                        <div>
                                            <button id="saveCommerceExchangeRate" class="btn btn-primary" type="submit">Guardar</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endsection

    @section('scripts')
        <script>
            var el = document.getElementById('p_CommerceExchangeRate')
            el.value = el.valueAsNumber.toFixed(2)
            el.addEventListener('input', function(event) {
                event.target.value = event.target.valueAsNumber.toFixed(2)
            });

            function porcentaje() {
                const api_dolar = document.querySelector(`input[name='dolar_api']`).value;
                const porcentaje = document.querySelector(`input[name='porcentaje_dolar'`).value;
                let ecuacion = 0;
                ecuacion = (Number(api_dolar) * Number(porcentaje)) / 100;
                let dolar_rke = Number(api_dolar) + ecuacion;
                document.querySelector(`input[name='dolar_rke']`).value = dolar_rke.toFixed(2);
            }
        </script>
    @endsection