{{-- resources/views/includes/my-winners-report-table.blade.php  --}}
<div class="table-responsive mt-3">
    <table class="table table-striped table-bordered my-2 w-100">
        <thead>
            <tr>
                <th>Sala</th>
                <th>Horario</th>
                <th>Vendedor</th>
                <th>Jugador</th>
                <th>País</th>
                <th>Moneda</th>
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
                            href="{{ route('bingo.reportSalesByRoom', ['room' => $win->Room->encrypted, 'coin' => encrypt($coin)]) }}">
                            {{ $win->Room->encrypted }}
                        </a>
                    </td>
                    <td class="align-middle">
                        {{ \Carbon\Carbon::parse($win->Room->Config->date)->format('d/m/y') }}
                        {{ $win->Room->Config->time }}
                    </td>
                    <td>
                        @if ($win->Seller)
                            {{ $win->Seller->name }}
                        @else
                            No Aplica
                        @endif
                    </td>
                    <td>
                        @if ($win->PlayerLink)
                            {{ $win->PlayerLink->name }} {{ strtoupper(substr($win->PlayerLink->lastname, 0, 1)) }}
                        @endif
                    </td>
                    <td>
                        @if ($win->PlayerLink)
                            {{ $win->PlayerLink->Country->name }}
                        @endif
                    </td>
                    <td>{{ $win->Coin->long_name }}</td>
                    <td>{{ $win->Coin->symbol }}&nbsp{{ $win->amount }}</td>
                    <td>{{ $win->Prize->description }}</td>
                    <td>
                        @if ($win->status == null)
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
                                    title="Notificar Pago" data-coin_sym="{{ $win->Coin->symbol }}"
                                    data-amount="{{ $win->amount }}" data-ctn='{{ encrypt($win->ctn) }}'>
                                    <i class="fa-solid fa-bell"></i>
                                </button>
                            @endif
                            @if ($win->PlayerLink)
                                <a class="btn btn-sm bg-primary show-ctns" target="_blank"
                                    href="{{ route('bingo.in-game', ['player' => $win->PlayerLink->link]) }}">
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
