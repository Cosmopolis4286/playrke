{{-- Chile --}}
<div class="col-md-12 col-12" id="hist_clp">
    <div class="card bg-rke" style="z-index:0;">
        <div class="card-body">
            <div>
                <h6>Pesos Chilenos</h6>
                <h5>Últimos Movimientos</h5>
                {{-- <select class="ml-3 col-lg-2 form-control estatus_id">
                   <option value="0">Mostrar todo</option>
                   <option value="1">Depósitos</option>
                   <option value="2">Retiros</option>
               </select> --}}
            </div>
            <div class="table-responsive">
                <table id="t_1" class="table table-striped table-bordered " style="width:100%">
                    <thead>
                        <tr>

                            <th>Fecha</th>
                            <th>Hora</th>
                            <th>Monto</th>
                            <th>Tipo</th>
                            <th>Orden Compra</th>
                            <th>Estatus</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($transactions_clp as $hist_chl)
                            <tr>
                                <td>{{ $hist_chl->transk_date }}</td>
                                <td>{{ $hist_chl->transk_hour }}</td>
                                @if ($hist_chl->type_transk == 1)
                                    @if ($hist_chl->status == 2)
                                        <td style="color: rgb(0, 128, 28)">
                                            ${{ number_format($hist_chl->amount, 2) }}
                                        </td>
                                    @else
                                        <td> ${{ number_format($hist_chl->amount, 2) }}</td>
                                    @endif
                                @elseif($hist_chl->type_transk == 2)
                                    <td style="color: rgb(151, 0, 0)">
                                        ${{ number_format($hist_chl->amount, 2) }}
                                    </td>
                                @endif
                                <td>
                                    @if ($hist_chl->type_transk == 1)
                                        DEPOSITO
                                    @elseif($hist_chl->type_transk == 2)
                                        RETIRO
                                    @endif
                                </td>
                                <td>{{ $hist_chl->buyOrder }}</td>
                                <td>{{ $hist_chl->inf_status }}</td>
                                <td align="center">
                                    @if ($hist_chl->status == 2)
                                        <button class="inf_transk btn bg-cyan" data-target="#view_inf"
                                            data-toggle="modal" data-orden_compra='{{ $hist_chl->buy_order }}'
                                            data-accounting_date='{{ $hist_chl->accounting_Date }}'
                                            data-fecha_transk='{{ $hist_chl->transk_date }}'
                                            data-hora_transk='{{ $hist_chl->transk_hour }}'
                                            data-payment_type='{{ $hist_chl->description }}'
                                            data-amount_transk='{{ $hist_chl->amount }}'
                                            data-num_cuotas='{{ $hist_chl->installmentsNumber }}'
                                            data-val_cuotas='{{ $hist_chl->installmentsAmount }}'
                                            data-cod_autorize='{{ $hist_chl->authorizationCode }}'
                                            data-card_number='{{ $hist_chl->cardNumber }}'>
                                            <i class="fa-solid fa-eye"></i> Ver Detalles
                                        </button>
                                    @elseif($hist_chl->status == 4)
                                        <button class="transk_rhz btn bg-cyan" data-target="#mrhz_clp"
                                            data-toggle="modal" data-orden_compra='{{ $hist_chl->buy_order }}'>
                                            <i class="fa-solid fa-eye"></i> Ver Detalles
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<style>
    .column_mdl_transbank label {
        font-weight: 500 !important;
        color: rgb(14, 14, 15);
        font-size: 14px;
    }
</style>
<div class="modal fade" id="view_inf" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Detalles de la transacción</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div style="display: flex">
                    <div class="column_mdl_transbank" style="display: grid">
                        <label>Comercio:</label>
                        <label>Orden de Compra:</label>
                        <label>Fecha de Creación:</label>
                        <label>Tipo de Pago:</label>
                        <label>Coins:</label>
                        <label>Monto:</label>
                        <label>Código Autorización:</label>
                        <label>Cantidad de cuotas:</label>
                        <label>Monto cuota:</label>
                        <label>Final núm. Tarjeta:</label>
                        <label>Descripción:</label>
                    </div>
                    <div class="column_mdl_transbank" style="display: grid">
                        <span class="ml-3">PlayRKE&nbsp-&nbspplayrke.com</span>
                        <span id="orden_compra" class="ml-3"></span>
                        <span id="fecha_transk"class="ml-3"></span>
                        <span id="payment_type" class="ml-3"></span>
                        <span class="ml-3">CLP</span>
                        <span id="amount_transk" class="ml-3"></span>
                        <span id="cod_autorize" class="ml-3"></span>
                        <span id="num_cuotas" class="ml-3"></span>
                        <span id="val_cuotas" class="ml-3"></span>
                        <span id="card_number" class="ml-3"></span>
                        <span class="ml-3">Depósito</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary mr-2" data-dismiss="modal"
                    id="close">Cerrar</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="amount_chl" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="max-width:310px; margin:0 auto;">
            <div class="modal-header">
                <h5>Depositar Saldo en PlayRKE</h5>
                <span class="sub"></span>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body mb-2">
                <div class="col-12 mt-2 mb-1 alert alert-primary text-center" style="margin:0 auto;">
                    @if (count($transactions_clp) > 0 && $transactions_clp[0]->status == 1)
                        <span style="font-size:14px">¡Tienes un depósito en proceso!<br></span>
                    @endif
                    <span style="font-size:15.25px">
                        Depósito mínimo: $ {{ number_format(5000, 2) }}
                    </span>
                </div>
                <label class="lb_in">Ingrese el monto:</label>
                <div style="display: flex">
                    <div class="form-control_d" style="max-width:35px;">$</div>
                    <input required id="amount" type="number" value="5000" min="5000" step="100"
                        max="280000" class="form-control" placeholder="Pesos Chilenos">
                </div>
            </div>
            <div class="modal-footer mb-2">
                <button @if (count($transactions_clp) > 0 && $transactions_clp[0]->status == 1) disabled @endif id='bt_transk' type="button"
                    class="mt-2 btn btn-primary profile-button">Continuar</button>
            </div>
        </div>
    </div>
</div>
{{-- Chile --}}
<div class="modal fade" id="mdp_clp" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="max-width:370px; margin:0 auto;">
            <div class="modal-header">
                <h5>Depositar Saldo PlayRKE</h5>
                <span class="sub"></span>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="mt-4 mb-4">
                <div class="grid-dashboard mb-n5 mt-n3">
                    <a class="col-4 col-sm-4 ml-05 mr-n2 pr-1" href="#" data-dismiss="modal"
                        data-target="#amount_chl" data-toggle="modal">
                        <span class="link-item">
                            <img class="mb-1" src="{{ asset('imgSystem/ban_chile.png') }}"
                                style="width:34.2px;"><br>
                            $ Pesos
                        </span>
                    </a>
                    <a class="col-4 col-sm-4 mr-n2 pr-1" href="{{ route('Wallet.createBPay') }}">
                        <span class="link-item">
                            <img class="mb-1" src="{{ asset('imgSystem/tether.svg') }}"
                                style="width:34.2px;"><br>USDT</span>
                    </a>
                    <a class="col-4 pr-1 mr-1 col-sm-4" href="#">
                        <span class="link-item">
                            <img class="mb-1" src="{{ asset('imgSystem/rsv.png') }}"
                                style="width:34.2px; border-radius:50%;"><br>
                            Reserve</span>
                    </a>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" id="close">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="mrt_clp" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="max-width:370px; margin:0 auto;">
            <div class="modal-header">
                <h5>Retirar Saldo PlayRKE</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="mt-4 mb-4">
                <div class="grid-dashboard mb-n5 mt-n3"><br>
                    <a class="col-4 col-sm-6 ml-05 mr-n2 pr-1"
                        @if (count($Account_CLP) > 0) href="{{ route('Wallet.rt_CLP') }}" 
                        @else href="{{ route('banco.index') }}" @endif>
                        <span class="link-item">
                            <img class="mb-1" src="{{ asset('imgSystem/ban_chile.png') }}"
                                style="width:34.2px;"><br>
                            $ Pesos
                        </span>
                    </a>
                    <a class="col-4 col-sm-6 mr-n2 pr-1"
                        @if ($BPay != null) href="{{ route('Wallet.rt_BPay') }}" 
                        @else href="{{ route('wallet.BPay') }}" @endif>
                        <span class="link-item">
                            <img class="mb-1" src="{{ asset('imgSystem/tether.svg') }}"
                                style="width:34.2px;"><br>USDT
                        </span>
                    </a>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" id="close">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="mrhz_clp" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header"><b> Orden de Compra rechazada</b>
                <span class="sub"></span>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <span class="motive_transk">
                    La Orden de Compra:<br><b><span class="mr-1" id="order_buy"></span></b>fue rechazada
                    Las posibles causas del rechazo son:<br>
                    <div class="mt-1">
                        <p>* Error en el ingreso de los datos de su tarjeta de crédito o
                            débito (fecha y/o código de seguridad).
                        </p>
                        <p> * Su tarjeta de crédito o débito no cuenta con saldo suficiente.</li>
                        <p>
                            * Tarjeta aún no habilitada en el sistema financiero.
                        </p>
                    </div>
                </span>
            </div>
        </div>
    </div>
</div>
