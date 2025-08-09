@props(['rooms', 'creator', 'coinSym', 'coin', 'uuidCode', 'exchangeRate', 'view', 'name'])

@php
    use Carbon\Carbon;

    $totalAllSale = 0;
    $totalJackpot = 0;
@endphp

<div class="table-responsive mt-3">
    <table class="table table-striped table-bordered my-2 w-100">
        <thead>
            <tr>
                <th>Sala</th>
                <th>Fecha</th>
                <th>Hora</th>
                <th>CTN</th>
                <th>#PJ</th>
                <th>Venta</th>
                <th>Bote</th>
                <th>Status</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rooms as $room)
                @php
                    $roomId = $room->encrypted;

                    $roomRoute =
                        $creator['type'] === 'customer'
                            ? route('bingo.out-game-customer', [
                                'customer' => $creator['id'],
                                'room' => $roomId,
                                'coin' => encrypt($coinSym),
                            ])
                            : route('bingo.out-game', [
                                'uuidCode' => $uuidCode,
                                'room' => $roomId,
                                'coin' => encrypt($coin),
                            ]);

                    $rate = $room->GameExchangeRate[0]->exchange_rate ?? $exchangeRate;
                    $ctnPrice = truncate(($room->Config->ctn_price ?? 0) * $rate);
                    $allSale = truncate(($room->all_sale ?? 0) * $rate);
                    $prizePool = truncate(($room->prize_pool ?? 0) * $rate);

                    $totalAllSale += $allSale;
                    $totalJackpot += $prizePool;

                    $statusColor = match ($room->Status->id) {
                        1 => 'gray',
                        2 => 'primary',
                        3 => 'navy',
                        4 => 'success',
                        5 => 'black',
                        default => 'secondary',
                    };
                @endphp

                <tr>
                    <td class="align-middle">
                        <small>Modo: {{ $room->Config->GameMode->description }}</small>
                        @if ($room->status > 1)
                            <a href="{{ $roomRoute }}" class="badge badge-dark" target="_blank" title="Ver sala">
                                <i class="fa-regular fa-eye"></i>
                            </a>
                        @endif
                        <br>
                        <a class="badge bg-navy" title="Ver Reporte"
                            href="{{ route('bingo.reportSalesByRoom', [
                                'room' => $room->encrypted,
                                'coin' => encrypt(1),
                            ]) }}">
                            {{ $room->encrypted }}
                        </a>
                    </td>

                    <td class="align-middle">
                        {{ Carbon::parse($room->Config->date)->format('d/m/y') }}
                    </td>
                    <td class="align-middle">{{ $room->Config->time }} H</td>
                    <td class="align-middle">{{ $coinSym }} {{ $ctnPrice }}</td>

                    <td class="align-middle">
                        <i class="fa-solid fa-users mr-1" title="Jugadores"></i>
                        {{ $room->players }}<br>
                        <i class="fa-solid fa-trophy mr-1" title="Ganadores"></i>
                        {{ $room->winners }}
                    </td>

                    <td class="align-middle">
                        {{ $coinSym }} {{ number_format($allSale, 2, '.') }}
                    </td>

                    <td class="align-middle">
                        <small>{{ $room->Config->PrizePoolType->description }}</small><br>
                        {{ $coinSym }} {{ number_format($prizePool, 2, '.') }}
                    </td>

                    <td class="align-middle">
                        <span class="badge bg-{{ $statusColor }}">
                            {{ $room->Status->description }}
                        </span>
                    </td>

                    <td class="align-middle">
                        <div class="btn-group" role="group">
                            @if ($room->status == 2 && $view === 'PUBLIC')
                                <button type="button" class="btn btn-sm btn-secondary" title="Comerciantes">
                                    <i class="fa-solid fa-cash-register mr-1"></i>
                                    {{ count($room->Creator) ?? 0 }}
                                </button>
                            @endif

                            @if ($room->status <= 2)
                                <button title="Editar" class="btn-{{ $name }} btn btn-sm btn-primary"
                                    data-action="edit" data-id="{{ encrypt($room->id) }}">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>

                                @if ($room->status == 1)
                                    <button class="btn-{{ $name }} btn btn-sm btn-success"
                                        @if ($room->Config->unix_date) data-type="5" @else data-block="1" disabled @endif
                                        data-id="{{ encrypt($room->id) }}" title="Publicar">
                                        <i class="fa-solid fa-circle-up"></i>
                                    </button>
                                @elseif ($room->status == 2 && $room->prize_pool == 0)
                                    <button class="btn-{{ $name }} btn btn-sm btn-dark" data-type="8"
                                        data-id="{{ encrypt($room->id) }}" title="Cambiar Status">
                                        <i class="fa-solid fa-rotate"></i>
                                    </button>
                                @endif
                            @endif

                            <button class="btn-{{ $name }} btn btn-sm bg-navy" data-type="7"
                                data-id="{{ encrypt($room->id) }}" title="Copiar">
                                <i class="fa-solid fa-copy"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>

        <tfoot>
            <tr>
                <td colspan="5" class="text-right"><b>Total:</b></td>
                <td>{{ $coinSym }} {{ number_format($totalAllSale, 2, '.') }}</td>
                <td>{{ $coinSym }} {{ number_format($totalJackpot, 2, '.') }}</td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
    </table>
</div>
