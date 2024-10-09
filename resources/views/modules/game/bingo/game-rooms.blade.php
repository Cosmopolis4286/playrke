@extends('layouts.app_rke')
@section('title', 'BingoCash')
@section('styles')
<link rel="stylesheet" href="{{ asset('css/bingocash-game-rooms.css') }}">@endsection

@section('content')
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
    <div class="col-12 w-100 pb-4">
        <div class="row justify-content-center" style="gap: 2.75rem;">
            @foreach ($rooms as $key => $room)
                <div class="col-sm-6 col-md-4 col-xl-3 d-flex justify-content-center">
                    <div class="card-game-room" data-room="{{ $room->encrypted }}" data-status="{{ $room->status }}">
                        <div id="start-time_{{ $room->encrypt }}" class="card-game-room__time">
                            {!! $room->status == 2 ? '<i class="fa-regular fa-clock"></i>' : 'EN JUEGO' !!}
                        </div>
                        <span class="card-game-room__game-mode">Modo: Cartón Lleno</span>
                        @if ($room->ctns > 0)
                            <div class="card-game-room__number-of-ctns" title="Mis Cartones">
                                <span>+{{ $room->ctns }}</span>
                            </div>
                        @endif
                        {{-- @if ($room->auto_mc == 1)
                            <div class="card-game-room__auto-marker" title="Tienes el Automarcador">
                                <i class="fa-solid fa-check-to-slot"></i>
                            </div>
                        @endif --}}
                        <img class="card-game-room__room-image" src="{{ asset('img/bg/estelar_fd.png') }}" alt="IMG">
                        <div class="container-card-game-room__jackpot">
                            <span class="card-game-room__jackpot-text">
                                {{ $room->Config->jackpot_type == 1 ? 'Bote:' : 'Premio:' }}
                            </span>
                            <div class="card-game-room__jackpot-amount">
                                @if ($room->jackpot_prize > 0 || $room->Config->jackpot_type != 1)
                                    <h3>
                                        <span id="jackpot-room_{{ $room->encrypted }}">
                                            {{ number_format($room->jackpot_prize, 2) }}
                                        </span>
                                        <small id="coin-symbol-room_{{ $room->encrypted }}">
                                            {{ $coinSym }}
                                        </small>
                                    </h3>
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
        const userType = '<?php echo json_encode($userType); ?>';
        const userTypeVal = '<?php echo json_encode($userTypeVal); ?>';
        const coin = '<?php echo json_encode($coin); ?>';
        const player = '<?php echo json_encode($player); ?>';
        const rooms = '<?php echo json_encode($dateTimeRooms); ?>';

        let isLogin = false;
        let room = false;
        let arrRooms = {
            id: [],
            dateTime: []
        };
        let ctnPrice = 0;
        let numCtns = 1;

        // function c_tmsvr() {
        //     $.ajax({
        //         url: "{{ route('info.getTimeServer') }}",
        //         headers: {
        //             'X-CSRF-TOKEN': $("#token").val()
        //         },
        //         method: 'get',
        //         dataType: 'json',
        //         success: function(data) {
        //             now = new Date(data);
        //         }
        //     });
        //     c_timer = 1;
        // }
        // window.onblur = function() {
        //     v_rtn = 1;
        // }
        // window.onfocus = function() {
        //     if (v_rtn == 1) {
        //         c_timer = 0;
        //         c_tmsvr();
        //     }
        // }

        function getDateTimeRooms() {
            const url =
                "{{ route('bingo.get-datetime-rooms', ['userType' => ':userType', 'userID' => ':userID', 'coin' => ':coin']) }}"
                .replace(':userType', userType)
                .replace(':userID', userTypeVal)
                .replace(':coin', coin);

            $.getJSON(url, {
                    'X-CSRF-TOKEN': $("#token").val()
                })
                .done(res => {
                    res.forEach(value => {
                        arrRooms.id.push(value.id);
                        arrRooms.dateTime.push(`${value.date} ${value.time}`);
                    });
                })
                .fail(err => {
                    console.error("Error fetching data:", err);
                });
        }

        $(document).ready(() => rooms.length && getDateTimeRooms());

        $(document).on("click", ".card-game-room", function() {
            // Obtener informacion de Sala al hacer click en ella
            if ($(this).data('status') != 2) return;

            room = $(this).data('room');

            $.ajax({
                url: `{{ route('bingo.getInfoByEncrypt', ['id' => ':id', 'mode' => ':mode']) }}`
                    .replace(':id', room)
                    .replace(':mode', 'game-rooms'),
                method: 'GET',
                dataType: 'json',
                beforeSend: function() {
                    $('#container-spinner').show();
                },
                success: function(res) {
                    const room = res.room;
                    isLogin = Number(res.isLogin);
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
            if (isLogin < total) {
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
                headers: {
                    'X-CSRF-TOKEN': $("#token").val()
                },
                data: {
                    room,
                    coin,
                    num_ctns: numCtns,
                    user_type: userType,
                    user_type_val: userTypeVal,
                    player,
                    connection: true,
                },
                dataType: 'json',
                beforeSend: function() {
                    $('#loader-btn-purchase-ctns').show();
                },
                success: function(res) {
                    location.reload(); // Refrescar página
                },
                error: function(error) {
                    console.error(error);
                },
                complete: function() {
                    $('#loader-btn-purchase-ctns').hide();
                }
            });

        });

        function handleTotalAmount(quantity) {
            const total = quantity * ctnPrice;
            numCtns = quantity;

            if (isLogin && !validateUserBalance(total)) return;

            $('#total-amount').text(total);
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

        }

        // function handleWaitingTime() {
        //     // Manejador de tiempo de espera
        //     if (id_elems.length > 0) {
        //         id_elems.forEach(function(elem, ind, array) {
        //             var end = new Date($("#bg_in" + elem).val());
        //             var bing_end = new Date($("#bg_in" + elem).val());
        //             new Date(bing_end + (bing_end.setSeconds(bing_end.getSeconds() + 620)));
        //             if (c_timer == 0) {
        //                 c_tmsvr();
        //             }
        //             distance = end - now;
        //             var days = Math.floor(distance / _day);
        //             var hours = Math.floor((distance % _day) / _hour);
        //             var minutes = Math.floor((distance % _hour) / _minute);
        //             var seconds = Math.floor((distance % _minute) / _second);

        //             if (c_timer == 1) {
        //                 if (distance <= 0) {
        //                     if ($("#bg_in_sts" + elem) != 1) {
        //                         if (bing_end - now > 0) {
        //                             $("#start-time_" + elem).html(
        //                                 '<span style="font-size:15px; letter-spacing: 0.25px;">EN JUEGO</span>'
        //                             );
        //                         } else {
        //                             $("#start-time_" + elem).html(
        //                                 '<span style="font-size:14px; letter-spacing: 0.25px;">FINALIZADA</span>'
        //                             );
        //                         }
        //                         $("#t_bg_hour" + elem).html('No disponible');
        //                         $("#bg_in_sts" + elem).val(1);
        //                         handleRoomStatus(elem);
        //                     }
        //                 } else {
        //                     if (days == 0) {
        //                         if (hours == 0) {
        //                             if (minutes == 0) {
        //                                 if (seconds != 0) {
        //                                     $("#start-time_" + elem).html('<div class="time_dt">' + '<span>' +
        //                                         seconds +
        //                                         '</span>' + '<span class="tx_time">SEG</span>' + '</div>');
        //                                 }
        //                             } else {
        //                                 $("#start-time_" + elem).html(
        //                                     '<div class="time_dt">' + '<span>' + minutes +
        //                                     '</span>' + '<span class="tx_time">MIN</span>' + '</div>' +
        //                                     '<div class="time_dt">' + '<span>' + seconds +
        //                                     '</span>' + '<span class="tx_time">SEG</span>' + '</div>');
        //                             }
        //                         } else {
        //                             $("#start-time_" + elem).html(
        //                                 '<div class="time_dt">' + '<span>' + hours + '</span>' +
        //                                 '<span class="tx_time">HRS</span>' + '</div>' +
        //                                 '<div class="time_dt tm_minutes">' + '<span>' + minutes +
        //                                 '</span>' + '<span class="tx_time">MIN</span>' + '</div>' +
        //                                 '<div class="time_dt">' + '<span>' + seconds +
        //                                 '</span>' + '<span class="tx_time">SEG</span>' + '</div>');
        //                             if (minutes == 0) {
        //                                 $('.tm_minutes').hide()
        //                             }
        //                         }
        //                     } else {
        //                         $("#start-time_" + elem).html(
        //                             '<div class="time_dt">' + '<span>' + days +
        //                             '</span>' + '<span class="tx_time">DIAS</span>' + '</div>' +
        //                             '<div class="time_dt tm_hours">' + '<span>' + hours + '</span>' +
        //                             '<span class="tx_time">HRS</span>' + '</div>' +
        //                             '<div class="time_dt  tm_minutes">' + '<span>' + minutes +
        //                             '</span>' + '<span class="tx_time">MIN</span>' + '</div>' +
        //                             '<div class="time_dt">' + '<span>' + seconds +
        //                             '</span>' + '<span class="tx_time">SEG</span>' + '</div>');
        //                         if (hours == 0) {
        //                             $('.tm_hours').hide();
        //                         }
        //                         if (minutes == 0) {
        //                             $('.tm_minutes').hide()
        //                         }
        //                     }
        //                 }
        //             }
        //         });
        //         now.setMilliseconds(1000);
        //     }
        // }

        // let timer = setInterval(handleWaitingTime, 1000);
    </script>
@endsection
