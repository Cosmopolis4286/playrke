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
                                <th>Nombre</th>
                                <th>Casillas</th>
                                <th>Status</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($bingoPlays as $item)
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            @endforeach
                        </tbody>
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
