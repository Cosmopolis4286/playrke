@extends('layouts.app_rke')
<title>Doble - PlayRKE</title>
@section('content')
    <div class="row">
        <div class="col-4 mt-2">
            <div style="display: flex">
                <input id="monto" type="number" name="monto" class="form-control" step="0.01"
                    onKeyUp="if(this.value>4000){this.value='4000';}else if(this.value<7){this.value='7';}"
                    data-val-required="El monto es requerido." data-val="true">
                <input type="text" class="form-control_d col-md-4" value="Bs" disabled style="max-width: 42.5px;">
                <button class="">1/2
                </button>
                <button class="">2x
                </button>
            </div>
            <span data-valmsg-for="monto" data-valmsg-replace="true" style="color: red;"></span>
            <div>
                <label class="lb_in">Seleccionar el Color</label>
            </div>
            <div class="row" style="display: flex; justify-content: center;">
                <button class="">x2R</button>
                <button class="">x14B</button>
                <button class="">x2N</button>
            </div>
            <div class="mt-2" style="display: flex; justify-content: center;"><button>Esperando/Entrar en la
                    ronda</button></div>
        </div>
        <div class="col-8">
            <div class="col-12 bg-gray mt-2" style="height:286px;">
                <div class="lb_in mb-3" style="display: flex; justify-content: center;">RODANDO EN 8.43s</div>
                <div class="row" style="display: flex; justify-content: center;">
                    <div class="bg-danger mr-2">Rojo</div>
                    <div class="mr-2">Blanco</div>
                    <div>Negro</div>
                </div>
            </div>
            <div class="col-12 bg-gray mt-2" style="height:80px;">GIROS ANTERIORES
                <div class="row" style="display: flex; justify-content: center;">
                    <div class="bg-danger mr-2">R</div>
                    <div class="mr-2">B</div>
                    <div>N</div>
                </div>
            </div>
        </div>

    </div>
    <div class="col-12 bg-blue mt-2">
        <div class="row">
            <div class="col-4">Victoria 2X R
                <hr>
                <div style="display: flex">
                    <span>Apuestas totales</span>
                    <div>300USDT</div>
                </div>
                <div>
                    <span>Usuario</span>
                    <span>Monto</span>
                    <div> Cosmos 3000R$
                    </div>
                </div>
            </div>
            <div class="col-4">Victoria 14X B
                <hr>
                <div style="display: flex">
                    <span>Apuestas totales</span>
                    <div>300USDT</div>
                </div>
                <div>
                    <span>Usuario</span>
                    <span>Monto</span>
                </div>
            </div>
            <div class="col-4">Victoria 2X N
                <hr>
                <div style="display: flex">
                    <span>Apuestas totales</span>
                    <div>300USDT</div>
                </div>
                <div>
                    <span>Usuario</span>
                    <span>Monto</span>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
@endsection
