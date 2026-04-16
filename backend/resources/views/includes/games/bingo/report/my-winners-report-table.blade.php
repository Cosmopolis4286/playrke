{{-- resources/views/includes/my-winners-report-table.blade.php  --}}
<div class="table-responsive mt-3">
    <table class="table table-striped table-bordered my-2 w-100">
        <thead>
            <tr>
                <th>Sala</th>
                <th>Tipo</th>
                <th>#RD</th>
                <th>Vendedor</th>
                <th>Jugador</th>
                <th>Premio</th>
                <th>Tipo</th>
                <th>Status</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($winners as $win)
                <tr>
                    <td class="align-middle">
                        <a class="badge bg-navy" title="Ver Reporte"
                            href="{{ route('bingo.reportSalesByRoomWinners', ['room' => $win->Room->encrypted, 'currency' => encrypt($currency)]) }}">
                            {{ $win->Room->encrypted }}
                        </a>
                    </td>
                    <td>{{ $win->Room->room_type_description }}</td>
                    <td>{{ $win->round }}</td>
                    <td>
                        @if ($win->Seller)
                            {{ $win->Seller->name }}
                        @else
                            No Aplica
                        @endif
                    </td>
                    <td>
                        @if ($win->ExternalPlayers)
                            {{ $win->ExternalPlayers->name }} {{ strtoupper(substr($win->ExternalPlayers->lastname, 0, 1)) }}
                        @endif
                    </td>
                    <td>{{ $win->Currency->iso_code }}&nbsp{{ $win->amount }}</td>
                    <td>{{ $win->prize_type_description }}</td>
                    <td>
                        @if ($win->status === 'WT')
                            <span class="badge bg-dark">Pendiente</span>
                        @elseif($win->status === 'CS')
                            <a href="{{ route('image.show-image-payment-prize-ctns', ['filename' => $win->Payments[0]->payment_proof]) }}"
                                title="Ver Comprobante" target="_blank">
                                <span class="badge bg-primary">Liberado</span></a>
                        @elseif($win->status === 'SP')
                            <a href="{{ route('image.show-image-payment-prize-ctns', ['filename' => $win->Payments[1]->payment_proof]) }}"
                                title="Ver Comprobante" target="_blank">
                                <span class="badge bg-success">Pagado</span></a>
                        @endif
                    </td>
                    <td>
                        <div class="btn-group" role="group" aria-label="Acciones">
                            @if ($win->status == null)
                                <button type="button" class="btn btn-danger btn-sm notify-ctn-payment"
                                    title="Notificar Pago" data-currency-symbol="{{ $win->Currency->symbol }}"
                                    data-amount="{{ $win->amount }}" data-ctn='{{ encrypt($win->ctn) }}'>
                                    <i class="fa-solid fa-bell"></i>
                                </button>
                            @endif
                            @if ($win->ExternalPlayers)
                                <a class="btn btn-sm bg-primary show-ctns" target="_blank"
                                    href="{{ route('bingo.game', ['player' => $win->ExternalPlayers->link]) }}">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
