@extends('layouts.app_rke')
@section('title', 'BingoCash')
@section('styles')
<link rel="stylesheet" href="{{ asset('css/bingocash-game-rooms.css') }}">@endsection
<style>
    .start-time__text {
        font-size: 14.5px;
        letter-spacing: 0.25px;
        text-transform: uppercase;
    }
</style>
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
    </div>
    <div class="col-12 w-100 mt-4 pb-4">
        <div class="row justify-content-center" style="gap: 2.75rem;">
            @foreach ($rooms as $key => $room)
                <div class="col-sm-6 col-md-4 col-xl-3 d-flex justify-content-center">
                    <div class="card-game-room" data-room="{{ $room->encrypted }}" data-status="{{ $room->status }}">
                        <div id="start-time_{{ $room->encrypt }}" class="card-game-room__time">
                            {!! $room->status == 2 ? '<i class="fa-regular fa-clock"></i>' : 'EN JUEGO' !!}
                        </div>
                        <span class="card-game-room__game-mode">Modo: Cartón Lleno</span>
                        @if (count($room->PlayersRoom))
                            <div class="card-game-room__number-of-ctns" title="Mis Cartones">
                                <span>+{{ $room->PlayersRoom[0]->ctns }}</span>
                            </div>
                        @endif
                        {{-- @if ($room->PlayersRoom[0]->auto_mc)
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
                            <span class="card-game-room__status" id="card-game-room__status_{{ $room->id }}">
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
    {{-- @vite('resources/js/rke/format-date-time.js') --}}
    <script>
        const userType = '<?php echo json_encode($userType); ?>';
        const userTypeVal = '<?php echo json_encode($userTypeVal); ?>';
        const coin = '<?php echo json_encode($coin); ?>';
        const player = '<?php echo json_encode($player); ?>';

        let rooms = JSON.parse('<?php echo $dateTimeRooms; ?>');
        let arrRooms = [];
        let isLogin = false;
        let room = false;
        let ctnPrice = 1;
        let numCtns = 0;
        let total = 0;
        let inView = true;

        window.onblur = function() {
            inView = false;
        }

        window.onfocus = function() {
            if (!inView) {
                inView = true;
                getDateTimeRooms();
            }
        }

        function getDateTimeRooms() {
            const url =
                "{{ route('bingo.get-datetime-rooms', ['userType' => ':userType', 'userID' => ':userID']) }}"
                .replace(':userType', userType).replace(':userID', userTypeVal)

            $.getJSON(url, {
                    'X-CSRF-TOKEN': $("#token").val()
                })
                .done(res => {
                    getArrRooms(res);
                })
                .fail(err => {
                    console.error("Error fetching data:", err);
                });
        }

        function getArrRooms(data) {
            data.forEach((value, index) => {
                arrRooms[index] = {
                    id: value.encrypted,
                    dateTime: `${value.date} ${value.time}`
                };
            });
        }

        function formatDateTime(date, time, timeZone = '-04:00') {
            // Crear un objeto Date con la fecha y hora proporcionadas
            const fullDateTime = new Date(`${date}T${time}${timeZone}`);

            // Formatear la fecha en formato "dd/mm/yy"
            const formattedDate = fullDateTime.toLocaleDateString('en-GB', {
                day: '2-digit',
                month: '2-digit',
                year: '2-digit',
            });

            // Convertir la hora a la zona horaria local del equipo
            let formattedTime = fullDateTime.toLocaleTimeString("en-US", {
                hour: "2-digit",
                minute: "2-digit",
                hour12: true,
            });

            // Mostrar la fecha y la hora formateadas en los elementos correspondientes
            $('#text-date').text(formattedDate);
            $('#text-time').text(formattedTime);
        }

        $(document).on("click", ".card-game-room", function() {
            // Obtener informacion de Sala al hacer click en ella
            if ($(this).data('status') != 2) return;

            room = $(this).data('room');
            total = 0;

            $.ajax({
                url: `{{ route('bingo.getInfoByEncrypt', ['id' => ':id', 'mode' => ':mode', 'coin' => ':coin']) }}`
                    .replace(':id', room).replace(':mode', 'game-rooms').replace(':coin', coin),
                method: 'GET',
                dataType: 'json',
                beforeSend: function() {
                    $('#container-spinner').show();
                },
                success: function(res) {
                    const room = res.room;
                    isLogin = Number(res.isLogin);
                    ctnPrice = room.ctnPrice * res.exchangeRate;
                    numCtns = 1;

                    $('#purchase-ctns__coin-text').text(room.jackpotType);
                    $('.purchase-ctns__coin-label').text(room.coin);
                    $('#jackpot-amount').text(room.jackpotAmount);
                    formatDateTime(room.dateTime[0], room.dateTime[1]);
                    handleTotalAmount(numCtns);

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
                    is_login: true,
                },
                dataType: 'json',
                beforeSend: function() {
                    $('#loader-btn-purchase-ctns').show();
                },
                success: function(res) {
                    showToast(res.message, res.msg_type);
                    setTimeout(function() {
                        location.reload();
                    }, 3250);
                },
                error: function(error) {
                    console.error(error);
                },
                complete: function() {
                    $('#loader-btn-purchase-ctns').hide();
                }
            });

        });

        function handleTotalAmount(numCtns) {
            total = (numCtns * ctnPrice).toFixed(2);

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

        let timestamp = '<?php echo json_encode($timestamp); ?>';
        const _second = 1000;
        const _minute = _second * 60;
        const _hour = _minute * 60;
        const _day = _hour * 24;

        function handleWaitingTime() {
            if (!arrRooms.length) return; // Si no hay salas, salir

            const formatTime = (value, label, className) =>
                `<div class="${className}"><span>${value}</span><span class="start-time__datetime">${label}</span></div>`;

            arrRooms.forEach(elem => {

                const roomID = elem.id;
                const end = new Date(elem.dateTime);
                const distance = end - timestamp;

                // Si el tiempo ha terminado
                if (distance <= 0) {
                    //handleRoomStatus(roomID);
                    $(`#card-game-room__status_${roomID}`).text('No disponible');
                    $(`#start-time_${roomID}`).html('<span class="start-time__text">En Juego</span>');
                    return; // Salir de la iteración actual
                }

                // Tiempo restante
                const days = Math.floor(distance / _day);
                const hours = Math.floor((distance % _day) / _hour);
                const minutes = Math.floor((distance % _hour) / _minute);
                const seconds = Math.floor((distance % _minute) / _second);

                // Crear el texto a mostrar según el tiempo restante
                let displayText = '';

                if (days > 0) {
                    displayText += formatTime(days, 'DIAS', 'count-days');
                }
                if (hours > 0) {
                    displayText += formatTime(hours, 'HRS', 'count-hours');
                }
                if (minutes > 0) {
                    displayText += formatTime(minutes, 'MIN', 'count-minutes');
                }

                displayText += formatTime(seconds, 'SEG');

                $(`#start-time_${roomID}`).html(displayText);
                $(".count-hours").toggle(hours > 0);
                $(".count-minutes").toggle(minutes > 0);
            });
        }

        if (rooms.length) {
            getArrRooms(rooms);
            setInterval(handleWaitingTime, 1000);
        }
    </script>
@endsection
