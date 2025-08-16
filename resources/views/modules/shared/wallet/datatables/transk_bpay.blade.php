 {{-- DP-Cryptos BPay --}}
 <div class="col-md-12 col-12" @if($pais == 234 || $pais == 43) style="display:none;" @endif id="hist_bpay">
     <div class="card bg-rke">
         <div class="card-body">
             <div>
                 <h6>Binance Pay</h6>
                 <h5>Últimos Movimientos</h5>
                 {{-- <select class="ml-3 col-lg-2 form-control estatus_id">
                    <option value="0">Mostrar todo</option>
                    <option value="1">Depósitos</option>
                    <option value="2">Retiros</option>
                </select> --}}
             </div>
             <div class="table-responsive">
                 <table id="t_1" class="table table-striped table-bordered  nowrap" style="width:100%">
                     <thead>
                         <tr>
                             <th>Fecha</th>
                             <th>Tipo</th>
                             <th>Cripto</th>
                             <th>Monto</th>
                             <th>ID de la orden</th>
                             <th>Estatus</th>
                             <th>Comprobante</th>
                         </tr>
                     </thead>
                     <tbody>
                         @foreach ($transactions_BPay as $transk)
                             <tr>
                                 <td>{{ \Carbon\Carbon::parse($transk->fecha)->format('d/m/Y') }}</td>
                                 <td>
                                     @if ($transk->tipo == 1)
                                         Depósito
                                     @elseif($transk->tipo == 2)
                                         Retiro
                                     @endif
                                 </td>
                                 <td>USDT</td>
                                 @if ($transk->tipo == 1)
                                     <td style="color: rgb(0, 128, 28)">
                                         {{ number_format($transk->monto, 2) }}
                                     </td>
                                 @elseif($transk->tipo == 2)
                                     <td style="color: rgb(151, 0, 0)">
                                         {{ number_format($transk->monto, 2) }}
                                     </td>
                                 @endif
                                 <td>{{ $transk->id_orden }}</td>
                                 @if ($transk->status == 1)
                                     <td>Procesando</td>
                                 @endif
                                 @if ($transk->status == 2)
                                     <td>Aprobado</td>
                                 @endif
                                 @if ($transk->status == 3)
                                     <td>
                                         <a href="" data-target="#dpc_usdt" data-toggle="modal">Congelado</a>
                                     </td>
                                 @endif
                                 @if ($transk->status == 4)
                                     <td>
                                         <a href="" data-target="#dp-r" data-toggle="modal">Rechazado</a>
                                     </td>
                                 @endif
                                 @if ($transk->capture != null)
                                     <td>
                                         <a target="_blank" href="{{ asset('/img/refers/bpay/' . $transk->capture) }}">
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
 {{-- DP-Cryptos BPay --}}
