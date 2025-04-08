@extends($layout)
@section('styles')
    @vite('resources/css/games/bingo/bingo-in-game.css')
@endsection
@section('title', $title)
@section('content')
    <div class="col-12 @if (isset($userToken)) p-2 @endif">
        <div class="card">
            @include('includes.card-header', ['subtitle' => 'Jugadas'])
            <div class="card-body">
                <button type="button" class="btn btn-sm btn-success" data-toggle="modal" data-target="#modal-bingo-plays">
                    <i class="fas fa-plus-square"></i> Crear
                </button>
                <div class="table-responsive mt-2">
                    <table class="table table-striped table-bordered my-2 w-100">
                        <thead>
                            <tr>
                                <th>Sala</th>
                                <th>Tipo</th>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Moneda</th>
                                <th>Ctns</th>
                                <th>Venta</th>
                                <th>Vendedor</th>
                                <th>Banca</th>
                                <th>Bote</th>
                            </tr>
                        </thead>
                        {{-- <tbody>
                            @foreach ($roomData as $room)
                                <tr>
                                    <td class="align-middle">
                                        <a class="badge bg-navy" title="Ver Reporte"
                                            href="{{ route('commerce.reportSalesByRoom', ['room' => $room['encrypted'], 'coin' => encrypt($coin)]) }}">
                                            {{ $room['encrypted'] }}
                                        </a>
                                    </td>
                                    <td>{{ $room['roomType'] }}</td>
                                    <td>{{ \Carbon\Carbon::parse($room['date'])->format('d/m/y') }}</td>
                                    <td>{{ $room['time'] }}h</td>
                                    <td>{{ $coinName }}</td>
                                    <td>{{ $room['allCtns'] }}</td>
                                    <td>{{ $coinSym }} {{ $room['allSaleRoom'] }}</td>
                                    <td>{{ $coinSym }} {{ $room['comSaleSellerRoom'] }}</td>
                                    <td>{{ $coinSym }} {{ $room['comBancaRoom'] }}</td>
                                    <td>{{ $coinSym }} {{ $room['jackpotRoom'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5" class="text-right"><b>Total:</b></td>
                                @if (count($roomData))
                                    <td>{{ $totals['allCtns'] }}</td>
                                    <td>{{ $coinSym }} {{ number_format($totals['allSales'], 2, '.') }}</td>
                                    <td>{{ $coinSym }} {{ number_format($totals['comSaleSeller'], 2, '.') }}</td>
                                    <td>{{ $coinSym }} {{ number_format($totals['comBanca'], 2, '.') }}</td>
                                    <td>{{ $coinSym }} {{ number_format($totals['jackpot'], 2, '.') }}</td>
                                @endif
                            </tr>
                        </tfoot> --}}
                    </table>
                </div>
            </div>
        </div>
        @include('includes.game.bingo.layout.modal-bingo-plays')
    </div>
@endsection
@section('scripts')
    <script>
        let bingoBoxes = [];

        $(document).on("click", ".ctn-plays", function() {

        });

        $(document).on("click", "#btn-confirm", function() {
            if (bingoBoxes.length > 3) {
                $.ajax({
                    url: `{{ route('bingocash.generateCarton') }}`,
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('#5K3_70K').val()
                    },
                    data: {
                        room: roomID,
                        coin,
                        num_ctns: numCtns,
                        creator_type: creator.type,
                        creator_id: creator.id,
                        player,
                        is_login: true,
                    },
                    dataType: 'json',
                    beforeSend: function() {
                        $('#loader-btn-purchase-ctns').show();
                    },
                    success: function(res) {
                        $('#jackpot-amount').text(res.jackpot);
                        $(`#jackpot-room_${roomID}`).text(res.jackpot);
                        $(`#number-of-ctns_${roomID}`).text(`+${res.ctns}`).removeClass('hide-elem');
                        showToast(res.message, res.type);
                    },
                    error: function(error) {
                        console.error(error);
                    },
                    complete: function() {
                        $('#loader-btn-purchase-ctns').hide();
                    }
                });
            } else {
                showToast('Es necesario un minimo de 4 casillas', 'info');
            }
        });
    </script>
@endsection
