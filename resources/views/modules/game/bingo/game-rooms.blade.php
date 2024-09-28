@extends('layouts.app_rke')
@section('title', 'BingoCash')
@section('styles')
<link rel="stylesheet" href="{{ asset('css/bingocash-game-rooms.css') }}">@endsection

@section('content')
<style>
    /* Modal Compra */
#container-purchase-ctns {
    display: none;
    position: absolute;
    top: 0%;
    left: 50%;
    transform: translate(-50%, 27%);
    z-index: 9999;
    min-width: 300px;
    max-width: 460px;
}
</style>
    <div class="col-12">
        <div id="container-spinner">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only"></span>
            </div>
        </div>
        <div id="container-text-game-rooms">
            <span id="text-game-rooms">SALAS DE JUEGO</span>

            {{-- <a class="mt-n1 badge badge-primary"
                href="{{ route('bingo.commerce-game-rooms', ['user' => encrypt(Auth::id()), 'coin' => encrypt(2)]) }}">
                Cambiar a <b>{{ $coinSym }}</b>
            </a> --}}
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
        function c_tmsvr() {
            $.ajax({
                url: "{{ route('info.getTimeServer') }}",
                headers: {
                    'X-CSRF-TOKEN': $("#token").val()
                },
                method: 'get',
                dataType: 'json',
                success: function(data) {
                    now = new Date(data);
                }
            });
            c_timer = 1;
        }
        window.onblur = function() {
            v_rtn = 1;
        }
        window.onfocus = function() {
            if (v_rtn == 1) {
                c_timer = 0;
                c_tmsvr();
            }
        }
    </script>
    <script src=""></script>
    <script>
        let isLogin = false;
        let purchase = false;
        let ctnPrice = 0;
        let numCtns = 1;

        $(document).on("click", ".card-game-room", function() {
            // Obtener informacion de Sala al hacer click en ella
            $.ajax({
                url: `{{ route('bingo.getInfoByEncrypt', ['id' => ':id', 'mode' => ':mode']) }}`
                    .replace(':id', $(this).data('room'))
                    .replace(':mode', 'game-rooms'),
                method: 'GET',
                dataType: 'json',
                beforeSend: function() {
                    $('#container-spinner').show();
                },
                success: function(res) {
                    const room = res.room;
                    isLogin = res.isLogin;
                    purchase = false;
                    ctnPrice = room.ctnPrice;
                    numCtns = 1;

                    $('#purchase-ctns__coin-text').text(room.jackpotType);
                    $('.purchase-ctns__coin-label').text(room.coin);
                    $('#jackpot-amount').text(room.jackpotAmount);
                    $('#text-date').text(room.dateTime[0]);
                    $('#text-time').text(room.dateTime[1]);

                    $('#total-amount').text(ctnPrice);

                    $('#container-spinner').hide();
                    $('#container-purchase-ctns').show();
                },
                error: function(error) {
                    console.log(error);
                    $('#container-spinner').hide();
                }
            });
        });

        $(document).on("click", "#btn-container-purchase-close", function() {
            //Cerrar Modal de Compra de cartones
            $('#container-purchase-ctns').hide();
        });

        function validateUserBalance(total = 0) {
            if (!purchase || isLogin < total) {
                showToast('Saldo Insuficiente', 'warning');
                return false;
            }
            return true;
        }

        $(document).on("click", "#btn-purchase-ctns", function() {
            // Procesar compra de cartones
            if (isLogin && !validateUserBalance()) return;

            $.ajax({
                url: `{{ route('bingocash.generateCarton') }}`,
                method: 'POST',
                data: {
                    room: ,
                    coin: ,
                    num_ctns: numCtns,
                    user_type,
                    user_type_val,
                    player: ,
                    user_in_game:
                },
                dataType: 'json',
                beforeSend: function() {
                    $('#container-spinner-purchase-ctns').show();
                },
                success: function(res) {
                    const room = res.room;

                    location.reload(); //Refrescar Pagina
                },
                error: function(error) {
                    console.log(error);
                    $('#container-spinner-purchase-ctns').hide();

                }
            });
        });

        function handleTotalAmount(quantity) {
            // Manejador de Monto Total = Multiplica la cantidad actual por el precio
            const total = quantity * ctnPrice;
            numCtns = quantity;

            if (isLogin && !validateUserBalance(total)) return;

            $('#total-amount').text(total);
            purchase = true;
        }

        $('.quantity').each(function() {
            const spinner = $(this),
                input = spinner.find('input[type="number"]'),
                btnUp = spinner.find('.quantity-up'),
                btnDown = spinner.find('.quantity-down'),
                min = parseFloat(input.attr('min')) || 0,
                max = parseFloat(input.attr('max')) || Infinity,
                step = parseFloat(input.attr('step')) || 1;

            const updateInput = (newValue) => {
                if (isLogin && !validateUserBalance()) return;
                input.val(newValue).trigger('change');
                btnDown.toggle(newValue > min);
                handleTotalAmount(newValue);
            };

            // Manejadores de clic para incrementar y decrementar
            btnUp.click(() => {
                let currentVal = +input.val();
                if (currentVal < max) {
                    updateInput(Math.min(currentVal + step, max)); // Incrementa el valor
                }
            });

            btnDown.click(() => {
                let currentVal = +input.val();
                if (currentVal > min) {
                    updateInput(Math.max(currentVal - step, min)); // Decrementa el valor
                }
            });

            // Inicializamos el botón de disminuir según el valor actual del input
            btnDown.toggle(+input.val() > min);
        });


        function handleWaitingTime() {
            // Manejador de tiempo de espera
            if (id_elems.length > 0) {
                id_elems.forEach(function(elem, ind, array) {
                    var end = new Date($("#bg_in" + elem).val());
                    var bing_end = new Date($("#bg_in" + elem).val());
                    new Date(bing_end + (bing_end.setSeconds(bing_end.getSeconds() + 620)));
                    if (c_timer == 0) {
                        c_tmsvr();
                    }
                    distance = end - now;
                    var days = Math.floor(distance / _day);
                    var hours = Math.floor((distance % _day) / _hour);
                    var minutes = Math.floor((distance % _hour) / _minute);
                    var seconds = Math.floor((distance % _minute) / _second);

                    if (c_timer == 1) {
                        if (distance <= 0) {
                            if ($("#bg_in_sts" + elem) != 1) {
                                if (bing_end - now > 0) {
                                    $("#bg_tx" + elem).html(
                                        '<span style="font-size:15px; letter-spacing: 0.25px;">EN JUEGO</span>'
                                    );
                                } else {
                                    $("#bg_tx" + elem).html(
                                        '<span style="font-size:14px; letter-spacing: 0.25px;">FINALIZADA</span>'
                                    );
                                }
                                $("#t_bg_hour" + elem).html('No disponible');
                                $("#bg_in_sts" + elem).val(1);
                                handleRoomStatus(elem);
                            }
                        } else {
                            if (days == 0) {
                                if (hours == 0) {
                                    if (minutes == 0) {
                                        if (seconds != 0) {
                                            $("#bg_tx" + elem).html('<div class="time_dt">' + '<span>' + seconds +
                                                '</span>' + '<span class="tx_time">SEG</span>' + '</div>');
                                        }
                                    } else {
                                        $("#bg_tx" + elem).html(
                                            '<div class="time_dt">' + '<span>' + minutes +
                                            '</span>' + '<span class="tx_time">MIN</span>' + '</div>' +
                                            '<div class="time_dt">' + '<span>' + seconds +
                                            '</span>' + '<span class="tx_time">SEG</span>' + '</div>');
                                    }
                                } else {
                                    $("#bg_tx" + elem).html(
                                        '<div class="time_dt">' + '<span>' + hours + '</span>' +
                                        '<span class="tx_time">HRS</span>' + '</div>' +
                                        '<div class="time_dt tm_minutes">' + '<span>' + minutes +
                                        '</span>' + '<span class="tx_time">MIN</span>' + '</div>' +
                                        '<div class="time_dt">' + '<span>' + seconds +
                                        '</span>' + '<span class="tx_time">SEG</span>' + '</div>');
                                    if (minutes == 0) {
                                        $('.tm_minutes').hide()
                                    }
                                }
                            } else {
                                $("#bg_tx" + elem).html(
                                    '<div class="time_dt">' + '<span>' + days +
                                    '</span>' + '<span class="tx_time">DIAS</span>' + '</div>' +
                                    '<div class="time_dt tm_hours">' + '<span>' + hours + '</span>' +
                                    '<span class="tx_time">HRS</span>' + '</div>' +
                                    '<div class="time_dt  tm_minutes">' + '<span>' + minutes +
                                    '</span>' + '<span class="tx_time">MIN</span>' + '</div>' +
                                    '<div class="time_dt">' + '<span>' + seconds +
                                    '</span>' + '<span class="tx_time">SEG</span>' + '</div>');
                                if (hours == 0) {
                                    $('.tm_hours').hide();
                                }
                                if (minutes == 0) {
                                    $('.tm_minutes').hide()
                                }
                            }
                        }
                    }
                });
                now.setMilliseconds(1000);
            }
        }

        //let timer = setInterval(handleWaitingTime, 1000);

        function handleRoomStatus(room) {
            // Manejador de Status de Sala
            $.ajax({
                url: `{{ route('bingocash.change-status') }}`,
                headers: {
                    'X-CSRF-TOKEN': $("#token").val()
                },
                method: 'POST',
                data: {
                    id: room
                },
                dataType: 'json',
                success: function(data) {
                    if (data['van'] == 1) {
                        window.location.href = 'room/' + id_sala;
                    }
                    if (data['rfs'].length > 0) {
                        $.each(data['rfs'], function(key, value) {
                            $("#bg_rm" + value).hide();
                            if ($("#id_bing").val() == value) {
                                $("#mc_buy").hide();
                            }
                        });
                    }
                }
            });
        }
    </script>
@endsection
