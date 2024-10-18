{{-- resources/views/components/table-info-bingo-rooms.blade.php --}}
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
                    date_default_timezone_set('America/Caracas');

                    $roomID = $room->encrypted;

                    if ($userType == 'customer') {
                        $roomRoute = route('bingocash.out-game-customer', [
                            'cust' => encrypt($userID),
                            'room' => $roomID,
                        ]);
                    } else {
                        if ($room->all_for_one == 1) {
                            $roomRoute = route('bingocash.out-game-rke', ['dateFilter' => 'today']);
                        } else {
                            $roomRoute = route('bingocash.out-game-commerce', [
                                'commerce' => encrypt($userID),
                                'room' => $roomID,
                            ]);
                        }
                    }

                    $ctnPrice = $room->ctn_price * ($room->GameExchangeRate[0]->exchange_rate ?? $exchangeRate);
                    $allSale = $room->all_sale * ($room->GameExchangeRate[0]->exchange_rate ?? $exchangeRate);
                    $jackpot = $room->jackpot * ($room->GameExchangeRate[0]->exchange_rate ?? $exchangeRate);

                    $totalAllSale += $allSale;
                    $totalJackpot += $jackpot;
                @endphp
                <tr>
                    <td class="align-middle">
                        <small>Modo: {{ $room->GameMode->description }}</small>
                        @if ($room->status > 1)
                            <a href="{{ $roomRoute }}" class="badge badge-dark" target="_blank">
                                <i class="fa-regular fa-eye"></i>
                            </a>
                        @endif
                        <br>
                        <a class="badge bg-navy" title="Ver Reporte"
                            href="{{ route('commerce.reportSalesByRoom', [
                                'room' => $room->encrypted,
                                'coin' => encrypt(1),
                            ]) }}">
                            {{ $room->encrypted }}
                        </a>
                    </td>
                    <td class="align-middle">
                        {{ \Carbon\Carbon::parse($room->date)->format('d/m/y') }}
                    </td>
                    <td class="align-middle">{{ $room->time }} H</td>
                    <td class="align-middle">{{ $coinSym }} {{ $ctnPrice }}</td>
                    <td class="align-middle">
                        <i class="fa-solid fa-users mr-1" title="Jugadores"></i>
                        {{ $room->players }}<br>
                        <i class="fa-solid fa-trophy mr-1" title="Ganadores"></i>
                        {{ count($room->PlayersRoom) ?? 0 }}
                    </td>
                    <td class="align-middle">{{ $coinSym }}
                        {{ rtrim(rtrim(number_format($allSale, 3), '0'), '.') }}</td>
                    <td>
                        <small>{{ $room->Config->JackpotType->description }}</small>
                        <br>{{ $coinSym }} {{ rtrim(rtrim(number_format($jackpot, 3), '0'), '.') }}
                    </td>
                    <td class="align-middle">
                        @if ($room->Status->id == 1)
                            <span class="badge bg-gray">{{ $room->Status->description }}</span>
                        @elseif ($room->Status->id == 2)
                            <span class="badge bg-primary">{{ $room->Status->description }}</span>
                        @elseif ($room->Status->id == 3)
                            <span class="badge bg-navy">{{ $room->Status->description }}</span>
                        @elseif ($room->Status->id == 4)
                            <span class="badge bg-success">{{ $room->Status->description }}</span>
                        @elseif ($room->Status->id == 5)
                            <span class="badge bg-black">{{ $room->Status->description }}</span>
                        @endif
                    </td>
                    <td class="align-middle">
                        <div class="btn-group" role="group" aria-label="Acciones">
                            @if ($room->status <= 2)
                                <button title="Editar" class="btn-{{ $name }} btn btn-sm btn-primary"
                                    data-type="2" data-id="{{ encrypt($room->id) }}">
                                    <i class="fa-solid fa-pen-to-square"></i> </button>
                                @if ($room->status == 1)
                                    @php
                                        $roomDateTime = \Carbon\Carbon::createFromFormat(
                                            'Y-m-d H:i',
                                            "{$room->date} {$room->time}",
                                            'America/Caracas',
                                        );
                                        $currentDateTime = \Carbon\Carbon::now('America/Caracas');
                                    @endphp

                                    @if ($roomDateTime->greaterThan($currentDateTime))
                                        <button class="btn-{{ $name }} btn btn-sm btn-success" data-type="5"
                                            data-id="{{ encrypt($room->id) }}" title="Publicar">
                                            <i class="fa-solid fa-circle-up"></i>
                                        </button>
                                    @endif
                                    {{-- <button class="btn-{{ $name }} btn btn-sm btn-danger" data-type="3"
                                        data-id="{{ encrypt($room->id) }}" title="Eliminar">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button> --}}
                                @elseif ($room->status == 2 && $room->jackpot_prize > 0)
                                    <button class="btn-{{ $name }} btn btn-sm btn-dark" data-type="6"
                                        data-id="{{ encrypt($room->id) }}" title="Reembolsar">
                                        <i class="fa-solid fa-rotate-right"></i>
                                    </button>
                                @endif
                            @else
                                @if ($room->replay == 0)
                                    <button class="btn-{{ $name }} btn btn-sm btn-secondary" data-type="4"
                                        data-id="{{ encrypt($room->id) }}" title="Repetir">
                                        <i class="fa-solid fa-repeat"></i>
                                    </button>
                                @endif
                            @endif
                            <button class="btn-{{ $name }} btn btn-sm bg-navy" data-type="7"
                                data-id="{{ encrypt($room->id) }}" title="Copiar">
                                <i class="fa-solid fa-copy"></i></button>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="text-right"><b>Total:</b></td>
                <td>{{ $coinSym }} {{ rtrim(rtrim(number_format($totalAllSale, 3), '0'), '.') }}</td>
                <td>{{ $coinSym }} {{ rtrim(rtrim(number_format($totalJackpot, 3), '0'), '.') }}</td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
    </table>
</div>
