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
                <th>Bote</th>
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
                    $roomId = $room->encrypted;

                    if ($creator['type'] === 'customer') {
                        $roomRoute = route('bingo.out-game-customer', [
                            'customer' => $creator['id'],
                            'room' => $roomId,
                            'coin' => encrypt($coin),
                        ]);
                    } else {
                        $roomRoute = route('bingo.out-game', [
                            'uuidCode' => $uuidCode,
                            'room' => $roomId,
                            'coin' => encrypt($coin),
                        ]);
                    }

                    $rate = $room->GameExchangeRate[0]->exchange_rate ?? $exchangeRate;

                    $ctnPrice = ($room->Config->ctn_price ?? 0) * $rate;
                    $allSale = ($room->all_sale ?? 0) * $rate;
                    $prizePool = ($room->prize_pool ?? 0) * $rate;

                    $totalAllSale += $allSale;
                    $totalJackpot += $prizePool;
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
                                'coin' => encrypt($coin),
                            ]) }}">
                            {{ $room->encrypted }}
                        </a>
                    </td>
                    <td class="align-middle">
                        {{ \Carbon\Carbon::parse($room->Config->date)->format('d/m/y') }}
                    </td>
                    <td class="align-middle">{{ $room->Config->time }} H</td>
                    <td class="align-middle">{{ $coinData->iso_code }} {{ $ctnPrice }}</td>
                    <td class="align-middle">
                        <i class="fa-solid fa-users mr-1" title="Jugadores"></i>
                        {{ $room->players }}<br>
                        <i class="fa-solid fa-trophy mr-1" title="Ganadores"></i>
                        {{ count($room->PlayersRoom) ?? 0 }}
                    </td>
                    <td class="align-middle">{{ $coinData->iso_code }}
                        {{ number_format($allSale, 2, '.') }}</td>
                    <td>
                        <small>{{ $room->Config->PrizePoolType->description }}</small>
                        <br>{{ $coinData->iso_code }} {{ number_format($prizePool, 2, '.') }}
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
                                    data-action="edit" data-id="{{ encrypt($room->id) }}">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                @if (!count($room->CtnsLot))
                                    <button title="Salir de la sala"
                                        class="btn-{{ $name }} btn btn-sm btn-danger" data-type="6"
                                        data-id="{{ encrypt($room->id) }}">
                                        <i class="fa-solid fa-right-from-bracket"></i>
                                    </button>
                                @endif
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="text-right"><b>Total:</b></td>
                <td>{{ $coinData->iso_code }} {{ number_format($totalAllSale, 2, '.') }}</td>
                <td>{{ $coinData->iso_code }} {{ number_format($totalJackpot, 2, '.') }}</td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
    </table>
</div>
