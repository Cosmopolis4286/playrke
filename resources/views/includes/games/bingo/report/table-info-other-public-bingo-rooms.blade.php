{{-- resources/views/includes/table-info-bingo-rooms.blade.php --}}
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
                <th>Jackpot</th>
                <th>Status</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalAllSale = 0;
                $totalJackpot = 0;
            @endphp
            @foreach ($rooms as $room)
                @php
                    $roomID = $room->encrypted;

                    if ($creator['type'] === 'customer') {
                        $roomRoute = route('bingocash.out-game-customer', [
                            'customer' => $creator['id'],
                            'room' => $roomID,
                            'coin' => encrypt($coin),
                        ]);
                    } else {
                        $roomRoute = route('bingocash.out-game', [
                            'uuidCode' => $uuidCode,
                            'room' => $roomID,
                            'coin' => encrypt($coin),
                        ]);
                    }

                    $rate = $room->GameExchangeRate[0]->exchange_rate ?? $exchangeRate;

                    $ctnPrice = ($room->Config->ctn_price ?? 0) * $rate;
                    $allSale = ($room->all_sale ?? 0) * $rate;
                    $jackpot = ($room->jackpot ?? 0) * $rate;

                    $totalAllSale += $allSale;
                    $totalJackpot += $jackpot;
                @endphp
                <tr>
                    <td class="align-middle">
                        <small>Modo: {{ $room->Config->GameMode->description }}</small>
                        @if ($room->status > 1)
                            <a href="{{ $roomRoute }}" class="badge badge-dark" target="_blank">
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
                        {{ \Carbon\Carbon::parse($room->Config->date)->format('d/m/y') }}
                    </td>
                    <td class="align-middle">{{ $room->Config->time }} H</td>
                    <td class="align-middle">{{ $coinSym }} {{ $ctnPrice }}</td>
                    <td class="align-middle">
                        <i class="fa-solid fa-users mr-1" title="Jugadores"></i>
                        {{ $room->players }}<br>
                        <i class="fa-solid fa-trophy mr-1" title="Ganadores"></i>
                        {{ count($room->PlayersRoom) ?? 0 }}
                    </td>
                    <td class="align-middle">{{ $coinSym }}
                        {{ number_format($allSale, 2, '.') }}</td>
                    <td>
                        <small>{{ $room->Config->JackpotType->description }}</small>
                        <br>{{ $coinSym }} {{ number_format($jackpot, 2, '.') }}
                    </td>
                    {{-- Estado --}}
                    <td class="align-middle">
                        <span
                            class="badge bg-{{ match ($room->Status->id) {
                                1 => 'gray',
                                2 => 'primary',
                                3 => 'navy',
                                4 => 'success',
                                5 => 'black',
                                default => 'secondary',
                            } }}">{{ $room->Status->description }}</span>
                    </td>

                    {{-- Acciones --}}
                    <td class="align-middle">
                        <div class="btn-group" role="group">
                            @if ($room->status == 2)
                                <button title="Editar" class="btn-{{ $name }} btn btn-sm btn-primary"
                                    data-type="2" data-id="{{ encrypt($room->id) }}">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                            @endif
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
