@extends('layouts.app_rke')
<title>Tether USDT - DP</title>
@section('fnd-content')
    <div class="content-wrapper">
    @endsection
    @section('content')
        <div class="row">
            <div class="col-12">
                {{-- DP-Pendientes --}}
                <div class="card bg-rke">
                    <div class="card-header">
                        <h4 class="my-n2 text-center">Depósitos Pendientes</h4>
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
                                                <th>Usuario</th>
                                                <th>Red</th>
                                                <th>Cantidad</th>
                                                <th>TxID</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @if (!empty($dpsCryptos))
                                                @foreach ($dpsCryptos as $recarga)
                                                    <tr>
                                                        <td>{{ $recarga->fecha }}</td>

                                                        @foreach ($users as $user)
                                                            @if ($user->id == $recarga->usuario_id)
                                                                <td>{{ $user->username }}</td>
                                                            @endif
                                                        @endforeach
                                                        <td>{{ $recarga->red }}</td>
                                                        <td>{{ $recarga->cantidad }} USDT</td>
                                                        <td>{{ $recarga->txid }}</td>

                                                        <td>
                                                            <form action="{{ route('recarga.updateCrypto', $recarga->id) }}"
                                                                method="put" style="display: inline">
                                                                {{ method_field('PUT') }}
                                                                {{ csrf_field() }}
                                                                <button class="btn" type="submit">
                                                                    <svg class="mt-n2 icon icon-tabler icon-tabler-circle-check"
                                                                        width="29" height="29" viewBox="0 0 24 24"
                                                                        stroke-width="1.5" stroke="#00abfb" fill="none"
                                                                        stroke-linecap="round" stroke-linejoin="round">
                                                                        <path stroke="none" d="M0 0h24v24H0z"
                                                                            fill="none" />
                                                                        <circle cx="12" cy="12"
                                                                            r="9" />
                                                                        <path d="M9 12l2 2l4 -4" />
                                                                    </svg>
                                                                </button>
                                                            </form>
                                                            <a href=""data-target="#r-dp" data-toggle="modal">
                                                                <svg class="mt-n2 ml-n2 icon icon-tabler icon-tabler-circle-x"
                                                                    width="29" height="29" viewBox="0 0 24 24"
                                                                    stroke-width="1.5" stroke="#ff2825" fill="none"
                                                                    stroke-linecap="round" stroke-linejoin="round">
                                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                                    <circle cx="12" cy="12" r="9" />
                                                                    <path d="M10 10l4 4m0 -4l-4 4" />
                                                                </svg>
                                                            </a>
                                                            <a href="{{ url('recarga/congelar/usdt/' . $recarga['id']) }}">
                                                                <svg class="mt-n2 ml-1 icon icon-tabler icon-tabler-edit"
                                                                    width="29" height="29" viewBox="0 0 24 24"
                                                                    stroke-width="1.5" stroke="#2c3e50" fill="none"
                                                                    stroke-linecap="round" stroke-linejoin="round">
                                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                                    <path
                                                                        d="M9 7h-3a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-3" />
                                                                    <path
                                                                        d="M9 15h3l8.5 -8.5a1.5 1.5 0 0 0 -3 -3l-8.5 8.5v3" />
                                                                    <line x1="16" y1="5" x2="19"
                                                                        y2="8" />
                                                                </svg>
                                                            </a>

                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @endif

                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>



                {{-- DP-Pendientes --}}

                {{-- DP-Aprobados --}}
                <div class="card bg-rke">
                    <div class="card-header">
                        <h4 class="my-n2 text-center">Depósitos Aprobados</h4>
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
                                                <th>Usuario</th>
                                                <th>Red</th>
                                                <th>Cantidad</th>
                                                <th>TxID</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($dpsACryptos as $recarga)
                                                <tr>
                                                    <td>{{ $recarga->fecha }}</td>
                                                    @foreach ($users as $user)
                                                        @if ($user->id == $recarga->usuario_id)
                                                            <td>{{ $user->username }}</td>
                                                        @endif
                                                    @endforeach
                                                    <td>{{ $recarga->red }}</td>
                                                    <td>{{ $recarga->cantidad }} USDT</td>
                                                    <td>{{ $recarga->txid }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- DP-Rechazados --}}
                <div class="card bg-rke">
                    <div class="card-header">
                        <h4 class="my-n2 text-center">Depósitos Rechazados</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12">
                                <table id="t_1" class="table table-striped table-bordered  nowrap"
                                    style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Usuario</th>
                                            <th>Red</th>
                                            <th>Cantidad</th>
                                            <th>TxID</th>
                                            <th>Motivo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($dpsRCryptos as $recarga)
                                            <tr>
                                                <td>{{ $recarga->fecha }}</td>
                                                @foreach ($users as $user)
                                                    @if ($user->id == $recarga->usuario_id)
                                                        <td>{{ $user->username }}</td>
                                                    @endif
                                                @endforeach
                                                <td>{{ $recarga->red }}</td>
                                                <td>{{ $recarga->cantidad }}</td>
                                                <td>{{ $recarga->txid }}</td>
                                                <td>
                                                    <a href="" data-target="#motivo" data-toggle="modal">Ver
                                                        Motivo</a>
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
        {{-- Modal Motivo Rechazo --}}
        <div class="modal fade" id="motivo" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-sm" role="document">
                <div class="modal-content bg-rke1">
                    <div class="modal-header">PlayRKE © 2022
                        <span class="sub"></span>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        Motivo: @if (count($dpsRCryptos) > 0)
                            {{ $recarga->motivo }}
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal"
                            id="close">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
        {{-- Modal Rechazar DP --}}
        <div class="modal fade" id="r-dp" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-sm" role="document">
                <div class="modal-content bg-rke1">
                    <form @if (count($dpsCryptos) > 0) action="{{ route('recarga.NoCrypto', $recarga->id) }}" @endif
                        method="put" style="display: inline">
                        {{ method_field('PUT') }}
                        {{ csrf_field() }}
                        <div class="modal-header"><b>Depósito Rechazado [MOTIVO]</b>
                            <span class="sub"></span>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <select class="form-control col-md-12" name="motivo_dpr">
                                <option value="N° de referencia inexistente">N° Ref. Inexistente</option>
                                <option value="Cantidad Máx. Superada">Cantidad Máx. Superada</option>
                            </select>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-secondary">Rechazar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endsection
