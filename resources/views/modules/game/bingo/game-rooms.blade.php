@extends('layouts.app_rke')
@section('title', 'BingoCash')
@section('styles')
<link rel="stylesheet" href="{{ asset('css/bingocash-game-rooms.css') }}">@endsection

@section('content')
    <div class="col-12">
        <div id="container-text-game-rooms">
            <span id="text-game-rooms">SALAS DE JUEGO</span>
            <a class="mt-n1 badge badge-primary"
                href="{{ route('bingo.commerce-game-rooms', ['user' => encrypt(Auth::id()), 'coin' => encrypt(2)]) }}">
                Cambiar a <b>{{ $coinSym }}</b>
            </a>
        </div>
        @include('components.game.bingo.layout.container-purchase-ctns', [
            'routeName' => 'commerce.getBingoWinnersByCommerce',
        ])
    </div><br>
    <div class="col-12 mt-2">
        <div class="row justify-content-center">
            @foreach ($rooms as $key => $room)
                <div class="col-sm-6 col-md-4 col-xl-3 d-flex justify-content-center">
                    <div class="card-game-room" data-room="{{ $room->encrypted }}">
                        <div id="bg_tx{{ $room->encrypt }}" class="card-game-room__time">
                            {!! $room->status == 2 ? '<i class="fa-regular fa-clock"></i>' : 'EN JUEGO' !!}
                        </div>
                        <span class="card-game-room__game-mode">Modo: Cartón Lleno</span>
                        @if ($room->ctns > 0)
                            <div class="card-game-room__number-of-ctns" title="Mis Cartones">
                                +<span class="num_ct">{{ $room->ctns }}</span>
                            </div>
                        @endif
                        @if ($room->auto_mc == 1)
                            <div class="card-game-room__auto-marker" title="Tienes el Automarcador">
                                <i class="fa-solid fa-check-to-slot"></i>
                            </div>
                        @endif
                        <img class="card-game-room__room-image" src="{{ asset('img/bg/estelar_fd.png') }}" alt="IMG">
                        <div class="container-card-game-room__jackpot">
                            <span class="card-game-room__jackpot-text">
                                {{ $room->type_boat == 1 ? 'Bote:' : ($room->type_boat == 2 ? 'Premio:' : '') }}
                            </span>
                            <div class="card-game-room__jackpot-amount">
                                @if ($room->jackpot_prize > 0)
                                    <img style="width:24px; margin-bottom:1px;" src="{{ asset('imgSystem/tether.svg') }}">
                                    <span id='montRoom{{ $room->id }}'>{{ number_format($room->jackpot, 2) }}</span>
                                @else
                                    <h3>ESPERANDO</h3>
                                @endif
                            </div>
                            <span class="card-game-room__ctn-price">Cartón: {{ $room->ctn_price }} USDT</span>
                            <span class="card-game-room__status" id="card-game-room__status{{ $room->id }}">
                                {{ $room->status == 2 ? 'Comprar cartones' : 'No disponible' }}
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).on("click", ".card-game-room", function() {
            $.ajax({
                url: `{{ route('bingo.getInfoByEncrypt', ['id' => ':id', 'mode' => ':mode']) }}`
                    .replace(':id', $(this).data('room'))
                    .replace(':mode', 'game-rooms'),
                method: 'GET',
                dataType: 'json',
                success: function(res) {
                    const room = res.room;

                    $('#jackpot-amount').text(room.jackpot);

                    $('#container-purchase-ctns').show();
                },
                error: function(jqXHR, textStatus, errorThrown) {}
            });
        });

        $(document).on("click", "#btn-container-purchase-close", function() {
            $('#container-purchase-ctns').hide();
        });
    </script>
@endsection
