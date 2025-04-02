{{-- resources/views/includes/table-info-bingo-draws.blade.php --}}
<div class="table-responsive mt-3">
    <table class="table table-striped table-bordered my-2 w-100">
        <thead>
            <tr>
                <th>Sala</th>
                <th>Tipo</th>
                <th>Ticket</th>
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
            @foreach ($draws as $draw)
                @php
                    $drawID = $draw->encrypted;

                    if ($creator['type'] === 'customer') {
                        $drawRoute = route('bingocash.out-game-customer', [
                            'customer' => $creator['id'],
                            'draw' => $drawID,
                            'coin' => encrypt($coin),
                        ]);
                    } else {
                        $drawRoute = route('bingocash.out-game', [
                            'uuidCode' => $uuidCode,
                            'draw' => $drawID,
                            'coin' => encrypt($coin),
                        ]);
                    }

                    $ctnPrice = $draw->ctn_price * ($draw->GameExchangeRate[0]->exchange_rate ?? $exchangeRate);
                    $allSale = $draw->all_sale * ($draw->GameExchangeRate[0]->exchange_rate ?? $exchangeRate);
                    $jackpot = $draw->jackpot * ($draw->GameExchangeRate[0]->exchange_rate ?? $exchangeRate);

                    $totalAllSale += $allSale;
                    $totalJackpot += $jackpot;
                @endphp
                <tr>
                    <td class="align-middle">
                        <small>Modo: {{ $draw->Config->GameMode->description }}</small>
                        @if ($draw->status > 1)
                            <a href="{{ $drawRoute }}" class="badge badge-dark" target="_blank">
                                <i class="fa-regular fa-eye"></i>
                            </a>
                        @endif
                        <br>
                        <a class="badge bg-navy" title="Ver Reporte"
                            href="{{ route('commerce.reportSalesByRoom', [
                                'draw' => $draw->encrypted,
                                'coin' => encrypt(1),
                            ]) }}">
                            {{ $draw->encrypted }}
                        </a>
                    </td>
                    <td class="align-middle">
                        {{ \Carbon\Carbon::parse($draw->Config->date)->format('d/m/y') }}
                    </td>
                    <td class="align-middle">{{ $draw->Config->time }} H</td>
                    <td class="align-middle">{{ $coinSym }} {{ $ctnPrice }}</td>
                    <td class="align-middle">
                        <i class="fa-solid fa-users mr-1" title="Jugadores"></i>
                        {{ $draw->players }}<br>
                        <i class="fa-solid fa-trophy mr-1" title="Ganadores"></i>
                        {{ count($draw->PlayersRoom) ?? 0 }}
                    </td>
                    <td class="align-middle">{{ $coinSym }}
                        {{ number_format($allSale, 2, '.') }}</td>
                    <td>
                        <small>{{ $draw->Config->JackpotType->description }}</small>
                        <br>{{ $coinSym }} {{ number_format($jackpot, 2, '.') }}
                    </td>
                    {{-- Estado --}}
                    <td class="align-middle">
                        <span
                            class="badge bg-{{ match ($draw->Status->id) {
                                1 => 'gray',
                                2 => 'primary',
                                3 => 'navy',
                                4 => 'success',
                                5 => 'black',
                                default => 'secondary',
                            } }}">{{ $draw->Status->description }}</span>
                    </td>

                    {{-- Acciones --}}
                    <td class="align-middle">
                        <div class="btn-group" role="group">
                            @if ($draw->status <= 2)
                                <button title="Editar" class="btn-{{ $name }} btn btn-sm btn-primary"
                                    data-type="2" data-id="{{ encrypt($draw->id) }}">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                @if ($draw->status == 1)
                                    <button class="btn-{{ $name }} btn btn-sm btn-success"
                                        @if (now()->timestamp + 900 < $draw->Config->unix_date) data-type="5" @else data-block="1" disabled @endif
                                        data-id="{{ encrypt($draw->id) }}" title="Publicar">
                                        <i class="fa-solid fa-circle-up"></i>
                                    </button>
                                @elseif ($draw->status == 2)
                                    @if ($draw->jackpot_prize > 0)
                                        <button class="btn-{{ $name }} btn btn-sm btn-dark" data-type="6"
                                            data-id="{{ encrypt($draw->id) }}" title="Reembolsar">
                                            <i class="fa-solid fa-money-bill-transfer"></i>
                                        </button>
                                    @else
                                        <button class="btn-{{ $name }} btn btn-sm btn-dark" data-type="8"
                                            data-id="{{ encrypt($draw->id) }}" title="Cambiar Status">
                                            <i class="fa-solid fa-rotate"></i>
                                        </button>
                                    @endif
                                @endif
                            @elseif (!$draw->replay)
                                <button class="btn-{{ $name }} btn btn-sm btn-secondary" data-type="4"
                                    data-id="{{ encrypt($draw->id) }}" title="Repetir">
                                    <i class="fa-solid fa-repeat"></i>
                                </button>
                            @endif
                            <button class="btn-{{ $name }} btn btn-sm bg-navy" data-type="7"
                                data-id="{{ encrypt($draw->id) }}" title="Copiar">
                                <i class="fa-solid fa-copy"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="text-right"><b>Total:</b></td>
                <td>{{ $coinSym }} {{ number_format($totalAllSale, 2, '.') }}</td>
                <td>{{ $coinSym }} {{ number_format($totalJackpot, 2, '.') }}</td>
                <td colspan="4"></td>
            </tr>
        </tfoot>
    </table>
</div>
