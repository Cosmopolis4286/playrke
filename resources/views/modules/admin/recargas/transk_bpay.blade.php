@extends('layouts.app_rke')
<title>PlayRKE - DP Binance</title>
@section('fnd-content')
    <div class="content-wrapper">
    @endsection
    @section('content')
        <div class="row">
            <div class="mb-2 col-md-12">
                <div class="card bg-rke">
                    <div class="card-header">
                        <h4 class="mb-n1">Depositos en USDT</h4>
                        <h5>Binance Pay</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12">
                                <div class="table-responsive">
                                    <table id="t_1" class="table table-striped table-bordered  nowrap"
                                        style="width:100%">
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Status</th>
                                                <th>Usuario</th>
                                                <th>Cripto</th>
                                                <th>Monto</th>
                                                <th>ID Orden</th>
                                                <th>Capture</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($transactions_bpay_apd as $transk)
                                                <tr>
                                                    <td>{{ \Carbon\Carbon::parse($transk->fecha)->format('d/m/Y') }}</td>
                                                    @if ($transk->status == 1)
                                                        <td>Pendiente</td>
                                                    @endif
                                                    @if ($transk->status == 2)
                                                        <td>Aprobado</td>
                                                    @endif
                                                    @foreach ($users as $user)
                                                        @if ($user->id == $transk->user)
                                                            <td>{{ $user->username }}</td>
                                                        @endif
                                                    @endforeach
                                                    <td>{{ $transk->crypto_mon }}</td>
                                                    <td>{{ $transk->monto }}</td>
                                                    <td>{{ $transk->id_orden }}</td>
                                                    <td>
                                                        <a target="_blank"
                                                            href="{{ asset('/img/refers/ves/' . $transk->capture) }}">
                                                            Ver Info</a>
                                                    </td>
                                                    <td>
                                                        @if ($transk->status == 1)
                                                            <div class="d-flex">
                                                                <a href="#" title="Aprobar" class="opc_transk"
                                                                    data-val="1">
                                                                    <form
                                                                        action="{{ route('recarga.accept_bpay', $transk->id) }}"
                                                                        id="accept" method="get">@csrf
                                                                        <i class="fa-solid fa-circle-check"
                                                                            style="color: #17d673; font-size:21px"></i>
                                                                    </form>
                                                                </a>
                                                                <a href="{{ route('recarga.freeze_bpay', $transk->id) }}"
                                                                    title="Congelar" class="opc_transk mr-2 ml-2">
                                                                    <i class="fa-solid fa-snowflake"
                                                                        style="color: #0068a8; font-size:21px"></i>
                                                                </a>
                                                                <a href="#" id="decline" title="Rechazar"
                                                                    data-target="#decline_dp" data-toggle="modal"
                                                                    data-id="{{ $transk->id }}">
                                                                    <i class="fa-solid fa-circle-xmark"
                                                                        style="color: #e02323; font-size:21px"></i>
                                                                </a>
                                                            </div>
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
                </div>
            </div>
            <div class="col-md-12">
                <div class="card bg-rke">
                    <div class="card-header">
                        <h4 class="mb-n1">Rechazados/Congelados</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12">
                                <div class="table-responsive">
                                    <table id="t_1" class="table table-striped table-bordered  nowrap"
                                        style="width:100%">
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Status</th>
                                                <th>Usuario</th>
                                                <th>Cripto</th>
                                                <th>Monto</th>
                                                <th>ID Orden</th>
                                                <th>Capture</th>
                                                <th>Motivo</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($transactions_bpay_crz as $transk)
                                                <tr>
                                                    <td>{{ \Carbon\Carbon::parse($transk->fecha)->format('d/m/Y') }}</td>
                                                    @if ($transk->status == 3)
                                                        <td>Congelado</td>
                                                    @elseif ($transk->status == 4)
                                                        <td>Rechazado</td>
                                                    @endif
                                                    @foreach ($users as $user)
                                                        @if ($user->id == $transk->user)
                                                            <td>{{ $user->username }}</td>
                                                        @endif
                                                    @endforeach
                                                    <td>{{ $transk->nombre }}</td>
                                                    <td>Bs {{ $transk->monto }}</td>
                                                    <td>Bs/$ {{ $transk->CommerceExchangeRate }}</td>
                                                    <td>{{ $transk->num_ref }}</td>
                                                    <td></td>
                                                    <td>
                                                        <a href="#" id="show_mtv" data-target="#mtv_decline"
                                                            data-toggle="modal" data-info="{{ $transk->info }}">
                                                            Ver Info</a>
                                                    </td>
                                                    <td>
                                                        @if ($transk->status == 3)
                                                            <a title="Liberar"
                                                                href="{{ route('recarga.liberate_bpay', $transk->id) }}">
                                                                <i class="fa-solid fa-unlock" style="color: #22b45a;"></i>
                                                            </a>
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
                </div>
            </div>
        </div>
        {{-- Modal Motivo DP --}}
        <div class="modal fade" id="decline_dp" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-sm" role="document">
                <div class="modal-content">
                    <form @if (count($transactions_bpay_apd) > 0) action="{{ route('recarga.decline_bpay') }}" @endif
                        method="GET" style="display: inline">@csrf
                        <div class="modal-header"><b>Rechazar Depósito (MOTIVO)</b>
                            <span class="sub"></span>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" id="id_transk" name="id_transk">
                            <select class="form-control col-md-12" name="motivo_dpr">
                                <option value="1">N° Ref. Inexistente</option>
                                <option value="2">Deposito Inferior al Mínimo</option>
                                <option value="3">Cantidad Máx. Superada</option>
                                <option value="6">Criptomoneda no admitida</option>
                            </select>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-secondary">Rechazar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="modal fade" id="mtv_decline" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-sm" role="document">
                <div class="modal-content">
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
                        <button type="button" class="btn btn-secondary" data-dismiss="modal"
                            id="close">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    @endsection
    @section('scripts')
        <script>
            $(".opc_transk").click(function() {
                confirm($(this).attr("data-val"));
            });
            $("#decline").click(function() {
                $("#info_mtv").val($(this).attr("data-id"));
            })
            $("#show_mtv").click(function() {
                $("#info_mtv").html($(this).attr("data-info"));
            })

            function confirm(val) {
                Swal.fire({
                    title: '¿Confirmas esta acción?',
                    showDenyButton: true,
                    confirmButtonText: 'Si, lo confirmo',
                    denyButtonText: `Cancelar`,
                }).then((result) => {
                    if (result.isConfirmed) {
                        if (val == 1) {
                            $('#accept').submit();
                        }
                        Swal.fire('¡Proceso exitoso!', '', 'success')
                    } else if (result.isDenied) {
                        Swal.fire('Proceso cancelado', '', 'info')
                    }
                })
            }
        </script>
    @endsection
