<!-- resources/views/includes/other-winners-report-table.blade.php -->
<div class="table-responsive mt-3">
    <table class="table table-striped table-bordered my-2 w-100">
        <thead>
            <tr>
                <th>Sala</th>
                <th>Tipo</th>
                <th>Comercio</th>
                <th>Jugador</th>
                <th>País</th>
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
                            href="{{ route('bingo.reportSalesByRoomWinners', ['room' => $win->Room->encrypted, 'coin' => encrypt($coin)]) }}">
                            {{ $win->Room->encrypted }}
                        </a>
                    </td>
                    <td>{{ $win->Room->room_type_description }}</td>
                    <td>{{ $win->Seller->Commerce->name }}</td>
                    <td>{{ $win->PlayerLink->name }} {{ strtoupper(substr($win->PlayerLink->lastname, 0, 1)) }}</td>
                    <td>{{ $win->PlayerLink->Country->name }}</td>
                    <td>{{ $win->Coin->symbol }}&nbsp{{ $win->amount }}</td>
                    <td>{{ $win->Prize->description }}</td>
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
                            <a class="btn btn-sm bg-primary show-ctns" target="_blank"
                                href="{{ route('bingo.in-game', ['player' => $win->PlayerLink->link]) }}">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
