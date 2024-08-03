<div class="col-md-12 col-12" @if ($pais == 234 || $pais == 43) style="display:none;" @endif id="dp_usdt">
    <div class="card bg-rke">
        <div class="card-body">
            <h5 align="center">DEPÓSITOS - RED BLOCKCHAIN</h5>
            <div class="table-responsive">
                <table id="t_1" class="table table-striped table-bordered nowrap" style="width:100%">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Red</th>
                            <th>Cantidad</th>
                            <th>TxID</th>
                            <th>Estatus</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($dp_cryptos as $dpBlock)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($dpBlock->fecha)->format('d/m/Y') }}</td>
                                <td>{{ $dpBlock->red }}</td>
                                <td>{{ $dpBlock->cantidad }} USDT</td>
                                <td>{{ $dpBlock->txid }}</td>
                                @if ($dpBlock->estatus == 0)
                                    <td>EN ESPERA</td>
                                @endif
                                @if ($dpBlock->estatus == 1)
                                    <td>APROBADO</td>
                                @endif
                                @if ($dpBlock->estatus == 2)
                                    <td>
                                        <a href="" data-target="#dpc_usdt" data-toggle="modal">CONGELADO</a>
                                    </td>
                                @endif
                                @if ($dpBlock->estatus == 3)
                                    <td>
                                        <a href="" data-target="#dp-r" data-toggle="modal">RECHAZADO</a>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="col-md-12 col-12" @if ($pais == 234 || $pais == 43) style="display:none;" @endif id="rt_usdt">
    <div class="card bg-rke" style="z-index:0;">
        <div class="card-body">
            <h5 align="center">RETIROS - RED BLOCKCHAIN</h5>
            <div class="table-responsive">
                <table id="t_1" class="table table-striped table-bordered  nowrap"
                    style="width:100%">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Red</th>
                            <th>Cantidad</th>
                            <th>TxID</th>
                            <th>Estatus</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rt_cryptos as $rtBlock)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($rtBlock->fecha)->format('d/m/Y') }}</td>
                                <td>{{ $rtBlock->red }}</td>
                                <td>{{ $rtBlock->cantidad }} USDT</td>
                                <td>{{ $rtBlock->txid }}</td>
                                @if ($rtBlock->estatus == 0)
                                    <td>EN ESPERA</td>
                                @endif
                                @if ($rtBlock->estatus == 1)
                                    <td>APROBADO</td>
                                @endif
                                @if ($rtBlock->estatus == 2)
                                    <td>
                                        <a href="" data-target="#rtc_usdt" data-toggle="modal">CONGELADO</a>
                                    </td>
                                @endif
                                @if ($rtBlock->estatus == 3)
                                    <td>
                                        <a href="" data-target="#dp-r" data-toggle="modal">RECHAZADO</a>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="dpc_usdt" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
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
                <span>
                    @if (count($dp_cryptos) > 0)
                        {{ $dpBlock->motivo }}
                    @endif
                </span>
            </div>
        </div>
    </div>
</div>
