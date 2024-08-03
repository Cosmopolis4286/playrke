    {{-- Bolivares --}}
    <div class="bs_dt col-md-12 col-12" id="hist_ves">
        <div class="card bg-rke" style="z-index:0;">
            <div class="card-body">
                <div>
                    <h6>Bolivares Bs.</h6>
                    <h5>Últimos Movimientos</h5>
                    {{-- <select class="ml-3 col-lg-2 form-control estatus_id">
                        <option value="0">Mostrar todo</option>
                        <option value="1">Depósitos</option>
                        <option value="2">Retiros</option>
                    </select> --}}
                </div>
                <div class="table-responsive">
                    <table id="t_1" class="table table-striped table-bordered nowrap"
                        style="width:100%">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>CommerceExchangeRate</th>
                                <th>Tipo</th>
                                <th>Monto</th>
                                <th>N° Ref.</th>
                                <th>Estatus</th>
                                <th>Comprobante</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($transactions_ves as $transk)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($transk->fecha)->format('d/m/Y') }}</td>
                                    <td>Bs/$ {{ $transk->CommerceExchangeRate }}</td>
                                    <td>
                                        @if ($transk->tipo == 1)
                                            Depósito
                                        @elseif($transk->tipo == 2)
                                            Retiro
                                        @endif
                                    </td>
                                    @if ($transk->tipo == 1)
                                        <td style="color: rgb(0, 128, 28)">
                                            {{ $wallet->name }}
                                            {{ number_format($transk->monto, 2) }}
                                        </td>
                                    @elseif($transk->tipo == 2)
                                        <td style="color: rgb(151, 0, 0)">
                                            {{ $wallet->name }}
                                            {{ number_format($transk->monto, 2) }}
                                        </td>
                                    @endif
                                    <td>{{ $transk->num_ref }}</td>
                                    @if ($transk->status == 1)
                                        <td>Procesando</td>
                                    @endif
                                    @if ($transk->status == 2)
                                        <td>Aprobado</td>
                                    @endif
                                    @if ($transk->status == 3)
                                        <td>
                                            <a href="#" data-motive='{{ $transk->motive }}' data-target="#dp_cold"
                                                data-toggle="modal">Congelado</a>
                                        </td>
                                    @endif
                                    @if ($transk->status == 4)
                                        <td>
                                            <a href="#" id="show_decline" data-motive='{{ $transk->info }}'
                                                data-target="#dp_rchz" data-toggle="modal">Rechazado</a>
                                        </td>
                                    @endif
                                    @if ($transk->capture != null)
                                        <td>
                                            <a target="_blank"
                                                href="{{ asset('/img/refers/ves/' . $transk->capture) }}">
                                                Ver Información
                                            </a>
                                        </td>
                                    @else
                                        <td></td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="dp_rchz" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
            <div class="modal-content bg-rke1">
                <div class="modal-header">PlayRKE © 2023
                    <span class="sub"></span>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    Motivo: <span id="info_mtv"></span>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" id="close">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="mdp_bs" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content bg-rke1" style="max-width:370px; margin:0 auto;">
                <div class="modal-header">
                    <h5>Depositar Saldo PlayRKE</h5>
                    <span class="sub"></span>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="mt-4 mb-4">
                    <div class="grid-dashboard mb-n5 mt-n3">
                        <a class="col-4 col-sm-4 ml-05 mr-n2 pr-1" href="{{ route('Wallet.createPM') }}">
                            <span class="link-item">
                                <img class="mb-1" src="{{ asset('imgSystem/vz.png') }}" style="width:34.2px;"><br>
                                Bolívares
                            </span>
                        </a>
                        <a class="col-4 col-sm-4 mr-n2 pr-1" href="{{ route('Wallet.createBPay') }}">
                            <span class="link-item">
                                <img class="mb-1" src="{{ asset('imgSystem/tether.svg') }}"
                                    style="width:34.2px;"><br>
                                USDT
                            </span>
                        </a>
                        <a class="col-4 pr-1 mr-1 col-sm-4" href="#">
                            <span class="link-item">
                                <img class="mb-1" src="{{ asset('imgSystem/rsv.png') }}"
                                    style="width:34.2px; border-radius:50%;"><br>
                                Reserve
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
    <div class="modal fade" id="mrt_bs" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content bg-rke1" style="max-width:370px; margin:0 auto;">
                <div class="modal-header">
                    <h5>Retirar Saldo PlayRKE</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="mt-4 mb-4">
                    <div class="grid-dashboard mb-n5 mt-n3"><br>
                        <a class="col-4 col-sm-4 ml-05 mr-n2 pr-1"
                            @if ($PagoMovil != null) href="{{ route('Wallet.rt_PM') }}" 
                            @else href="{{ route('pagomovil.index') }}" @endif>
                            <span class="link-item">
                                <img class="mb-1" src="{{ asset('imgSystem/vz.png') }}"
                                    style="width:34.2px;"><br>
                                Bolívares
                            </span>
                        </a>
                        <a class="col-4 col-sm-4 mr-n2 pr-1"
                            @if ($BPay != null) href="{{ route('Wallet.rt_BPay') }}"
                            @else href="{{ route('wallet.BPay') }}" @endif>
                            <span class="link-item">
                                <img class="mb-1" src="{{ asset('imgSystem/tether.svg') }}"
                                    style="width:34.2px;"><br>
                                USDT
                            </span>
                        </a>
                        <a class="col-4 pr-1 mr-1 col-sm-4" href="#">
                            <span class="link-item">
                                <img class="mb-1" src="{{ asset('imgSystem/rsv.png') }}"
                                    style="width:34.2px; border-radius:50%;"><br>
                                Reserve
                            </span>
                        </a>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"
                        id="close">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="dp_cold" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
            <div class="modal-content bg-rke1">
                <div class="modal-header"><b>Depósito Congelado [MOTIVO]</b>
                    <span class="sub"></span>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <span class="motive_transk"></span>
                </div>
            </div>
        </div>
    </div>
