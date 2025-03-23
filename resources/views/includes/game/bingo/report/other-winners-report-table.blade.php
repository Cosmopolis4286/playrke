<!-- resources/views/includes/other-winners-report-table.blade.php -->
<div class="table-responsive mt-3">
    <table class="table table-striped table-bordered my-2 w-100">
        <thead>
            <tr>
                <th>Sala</th>
                <th>Comercio</th>
                <th>Jugador</th>
                <th>País</th>
                <th>Moneda</th>
                <th>Premio</th>
                <th>Tipo</th>
                <th>Enviar</th>
                <th>Status</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($winners as $win)
                @php
                    $countCommerces = $win->Room->CtnsLot->pluck('commerce')->unique()->count();
                    $totalWinners =
                        $win->prize_type == 1 ? $winnersData['winnersJackpot'] : $winnersData['winnersSerials'];
                @endphp
                <tr>
                    <td class="align-middle">
                        <a class="badge bg-navy" title="Ver Reporte"
                            href="{{ route('commerce.reportSalesByRoom', ['room' => $win->Room->encrypted, 'coin' => encrypt($wallet->Coin->id)]) }}">
                            {{ $win->Room->encrypted }}
                        </a>
                    </td>
                    <td>{{ $win->Commerce->name }}</td>
                    <td>{{ $win->PlayerLink->name }} {{ strtoupper(substr($win->PlayerLink->lastname, 0, 1)) }}</td>
                    <td>{{ $win->PlayerLink->Country->name }}</td>
                    <td>{{ $win->Coin->long_name }}</td>
                    <td>{{ $win->Coin->symbol }}&nbsp{{ $win->amount }}</td>
                    <td>{{ $win->Prize->description }}</td>
                    <td>{{ $win->Coin->symbol }}&nbsp{{ $win->amount / $totalWinners }}</td>
                    <td>
                        @if ($win->PlayerGame->winner == 1)
                            <span class="badge bg-dark">Pendiente</span>
                        @elseif($win->PlayerGame->winner == 2)
                            <span class="badge bg-primary">Liberado</span>
                        @elseif($win->PlayerGame->winner == 3)
                            <span class="badge bg-success">Pagado</span>
                        @endif
                    </td>
                    <td>
                        <div class="btn-group" role="group" aria-label="Acciones">
                            @if ($win->PlayerGame->winner == 1)
                                @if ($win->commerce == $commerceID)
                                    <button type="button" class="btn btn-danger btn-sm notify-ctn-payment"
                                        title="Notificar Pago" data-payment_type="1" data-count_commerce=""
                                        data-coin_sym="{{ $win->Coin->name }}" data-prize_amount="{{ $win->amount }}"
                                        data-ctn='{{ encrypt($win->ctn) }}'>
                                        Notificar
                                    </button>
                                @else
                                    <button type="button" class="btn btn-danger btn-sm notify-ctn-payment"
                                        title="Notificar Pago" data-payment_type="2"
                                        data-commerce='{{ encrypt($win->Commerce->id) }}'
                                        data-id_coin='{{ encrypt($win->Coin->id) }}'
                                        data-amount-sent='{{ $win->amount / $winnersData['totalWinners'] }}'>
                                        Pago Comercio
                                    </button>
                                @endif
                            @endif
                            <a class="btn btn-sm bg-primary show-ctns" target="_blank"
                                href="{{ route('bingocash.in-game', ['player' => $win->PlayerLink->link]) }}">
                                <i class="mr-1 fa-solid fa-eye"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
