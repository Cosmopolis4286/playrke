@extends($layout)
@section('styles')
    <style>
        body {
            background-image: url("{{ asset('img/body/space.png') }}") !important;
        }
    </style>
    @vite('resources/css/games/bingo/bingo-game-rooms.css')
@endsection
@section('title', 'BingoCash')

@section('content')
    <div class="container-fluid">
        <div class="col-12 @if ($layout === 'layouts.app_rke_off') mt-2 @endif">
            <div id="container-spinner" class="mt-1">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only"></span>
                </div>
            </div>
            <div id="container-text-game-rooms">
                <span id="text-game-rooms">SALAS DE JUEGO</span>
                {{-- <a class="mt-n1 badge badge-primary"
                    href="{{ route('bingo.game-rooms-commerce', ['user' => encrypt(Auth::id()), 'coin' => encrypt(2)]) }}">
                    Cambiar a <b>{{ $coinSym }}</b>
                </a> --}}
            </div>
        </div>
        <div class="row">
            @foreach ($rooms as $key => $room)
                <div id="container-card-game-room_{{ $room->encrypted }}"
                    class="d-flex justify-content-center py-4 mb-1 col-sm-6 col-md-4 col-xl-3">
                    <div class="card-game-room" id="card-game-room_{{ $room->encrypted }}"
                        data-room="{{ $room->encrypted }}" data-status="{{ $room->status }}">
                        <div id="start-time_{{ $room->encrypted }}" class="card-game-room__time">
                            {!! $room->status == 2 ? '<i class="fa-regular fa-clock"></i>' : 'EN JUEGO' !!}
                        </div>
                        <span class="card-game-room__game-mode">Modo: Cartón Lleno</span>
                        <div id="number-of-ctns_{{ $room->encrypted }}"
                            class="@if (!count($room->PlayersRoom)) hide-elem @endif card-game-room__number-of-ctns"
                            title="Mis Cartones">
                            @if (count($room->PlayersRoom))
                                +{{ $room->PlayersRoom[0]->ctns }}
                            @endif
                        </div>
                        {{-- @if ($room->PlayersRoom[0]->automarker)
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
                                <h3>
                                    <span id="jackpot-room_{{ $room->encrypted }}">
                                        {{ number_format($room->jackpot_prize * $exchangeRate, 2) }}
                                    </span>
                                    <small id="coin-symbol-room_{{ $room->encrypted }}">
                                        {{ $coinSym }}
                                    </small>
                                </h3>
                            </div>
                            <span class="card-game-room__ctn-price">
                                Cartón: <span id="ctn-price_{{ $room->encrypted }}">
                                    {{ number_format($room->ctn_price * $exchangeRate, 2) }}
                                </span> {{ $coinSym }}
                            </span>
                            <span class="card-game-room__status" id="card-game-room__status_{{ $room->encrypted }}">
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
    @vite('resources/js/rke/format-date-time-unix.js')
    <script>
        const creator = @json($creator);
        const coin = @json($coin);
        const player = @json($player);
        const userToken = @json($userToken ?? null);
        const modalHTML = @json(view('includes.game.bingo.layout.container-purchase-ctns')->render());

        let dateTimeRooms = @json($dateTimeRooms);
        let timestamp = new Date(dateTimeRooms['timestamp']);
        let arrRooms = [];
        let isLogin = false;
        let roomID = false;
        let ctnPrice = 1;
        let numCtns = 1;
        let total = 0;
        let inView = true;
        let inCourse = false;

        window.onblur = function() {
            inView = false;
        }

        window.onfocus = function() {
            if (!inView) {
                inView = true;
                getDateTimeRooms();
            }
        }

        $(document).on('input', '#num-ctns', function() {
            // Filtrar caracteres no numéricos
            $(this).val($(this).val().replace(/[^0-9]/g, ''));

            // Limitar a dos dígitos
            if ($(this).val().length > 2) {
                $(this).val($(this).val().slice(0, 2));
            }

            let value = parseInt($(this).val(), 10);
            if (value < 1) {
                $(this).val(1);
            } else if (value > 30) {
                $(this).val(30);
            }
        });

        const routeGetDatetimeRooms = @json($route);

        function getDateTimeRooms() {
            $.getJSON(routeGetDatetimeRooms, {
                    'X-CSRF-TOKEN': $("#5K3_70K").val()
                })
                .done(res => {
                    timestamp = new Date(res.timestamp);
                    getArrRooms(res.rooms);
                })
                .fail(err => {
                    console.error("Error fetching data:", err);
                });
        }

        function getArrRooms(data) {
            data.forEach((value, index) => {
                arrRooms[index] = {
                    id: value.encrypted,
                    dateTime: value.unix_date
                };
            });
        }

        function generateModalPurchase(roomID) {
            $('#container-purchase-ctns').remove(); // Elimina el contenedor previo

            $(`#container-card-game-room_${roomID}`).prepend(modalHTML); // Inserta el nuevo modal
        }

        $(document).on("click", ".card-game-room", function() {
            // Obtener informacion de Sala al hacer click en ella

            if ($(this).data('status') != 2 || inCourse) return;

            roomID = $(this).data('room');
            total = 0;

            $.ajax({
                url: `{{ route('bingo.getInfoByEncrypt', ['id' => ':id', 'mode' => ':mode', 'coin' => ':coin']) }}`
                    .replace(':id', roomID).replace(':mode', 'game-rooms').replace(':coin', coin),
                method: 'GET',
                dataType: 'json',
                beforeSend: function() {
                    inCourse = true;
                    $('#container-spinner').show();
                },
                success: function(res) {
                    const room = res.room;
                    isLogin = Number(res.isLogin);
                    ctnPrice = room.ctnPrice;
                    numCtns = 1;

                    generateModalPurchase(roomID);

                    $('#purchase-ctns__coin-text').text(room.jackpotType);
                    $('.purchase-ctns__coin-label').text(room.coin);
                    $('#jackpot-amount').text(room.jackpotAmount);
                    formatDateTime(room.dateTime);
                    handleTotalAmount();

                    $(`#jackpot-room_${roomID}`).text(room.jackpotAmount);

                    if ($(`#ctn-price_${roomID}`).text() != ctnPrice) {
                        $(`#ctn-price_${roomID}`).text(ctnPrice);
                    }

                    $('#container-spinner').hide();
                    $('#container-purchase-ctns').show();
                },
                error: function(error) {
                    console.log(error);
                    $('#container-spinner').hide();
                },
                complete: function() {
                    inCourse = false;
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
            if ((isLogin && !validateUserBalance()) || !player) return;

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
                    user_type: creator.type,
                    user_type_val: creator.id,
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
        });

        function handleTotalAmount() {
            total = (numCtns * ctnPrice).toFixed(2);

            if (isLogin && !validateUserBalance(total)) return;

            $('#total-amount').text(total);
        }

        // Manejador de Cantidad de Ctns
        const input = $('#num-ctns');
        const btnUp = $('#quantity-up');
        const btnDown = $('#quantity-down');
        const min = parseFloat(input.attr('min')) || 0;
        const max = parseFloat(input.attr('max')) || 30;
        const step = parseFloat(input.attr('step')) || 1;

        const updateInput = (newValue) => {
            if (isLogin && !validateUserBalance()) return;
            input.val(newValue).trigger('change');
            btnDown.toggle(newValue > min);
            btnUp.toggle(newValue < max);
            numCtns = newValue;
            handleTotalAmount();
        };

        // Función para manejar clics
        const handleButtonClick = (increment) => {
            let currentVal = +input.val();
            const newValue = increment ? Math.min(currentVal + step, max) : Math.max(currentVal - step, min);
            if (newValue !== currentVal) updateInput(newValue);
        };

        // Manejadores de clic para incrementar y decrementar
        btnUp.click(() => handleButtonClick(true));
        btnDown.click(() => handleButtonClick(false));

        // Inicializa la visibilidad de los botones
        btnDown.toggle(+input.val() > min);
        // Manejador de Cantidad de Ctns

        const _second = 1000;
        const _minute = _second * 60;
        const _hour = _minute * 60;
        const _day = _hour * 24;

        // Formato de tiempo
        function formatTime(value, label, className, key) {
            return `<div class="card-game-room__time-column ${className}"><span id="${className}_${key}" class="column-datetime__count">${value}</span><span class="column-datetime__text">${label}</span></div>`;
        }

        // Actualizar tiempo solo si ha cambiado
        function setTimeRemaining(key, timeRemaining) {
            const units = ['days', 'hours', 'minutes', 'seconds'];
            units.forEach(unit => {
                const element = $(`#cnt-${unit}_${key}`);
                const newValue = timeRemaining[unit];
                if (element.length && element.text() != newValue) {
                    element.text(newValue);
                }
            });
        }

        // Mostrar/ocultar días, horas, minutos según su disponibilidad
        function toggleTimeVisibility(roomID, timeRemaining) {
            $(`#start-time_${roomID} .cnt-days`).toggle(timeRemaining.days > 0);
            $(`#start-time_${roomID} .cnt-hours`).toggle(timeRemaining.hours > 0);
            $(`#start-time_${roomID} .cnt-minutes`).toggle(timeRemaining.minutes > 0);
        }

        let waitingTime = false;

        // Procesar tiempo restante para cada sala
        function handleWaitingTime(arrRooms) {
            if (!arrRooms.length) return;

            arrRooms.forEach((elem, key) => {
                const room = elem.id;
                const end = new Date(elem.dateTime * 1000);
                const difference = end - timestamp;

                // Si el tiempo ha terminado
                if (difference <= 0) {
                    const status = $(`#card-game-room_${room}`).data('status');
                    if (status == 2) {
                        $(`#card-game-room_${room}`).data('status', 3);
                        updateRoomStatus(room, 'No disponible', '<span class="start-time__text">En Juego</span>');
                    }

                    // Redirigir a la sala según el tipo de usuario
                    const route = creator.type == 'commerce' ?
                        `{{ route('bingocash.in-game-rke', ['player' => ':player', 'room' => ':room']) }}`
                        .replace(':player', player).replace(':room', room) :

                        `{{ route('bingocash.in-game-customer', ['customer' => ':customer', 'userToken' => ':userToken', 'room' => ':room']) }}`
                        .replace(':customer', creator.id).replace(':userToken', userToken).replace(':room', room);

                    window.location.href = route;

                    if (waitingTime) clearInterval(waitingTime); // Limpiar el intervalo previo si existe
                    return;
                }

                // Obtener tiempo restante
                const timeRemaining = getTimeRemaining(difference);

                // Verificar si no existe cnt-seconds en el DOM
                if (!$(`#start-time_${room} .cnt-seconds`).length) {
                    let displayText = '';
                    if (timeRemaining.days > 0) displayText += formatTime(timeRemaining.days, 'DIAS', 'cnt-days',
                        key);
                    if (timeRemaining.hours > 0) displayText += formatTime(timeRemaining.hours, 'HRS', 'cnt-hours',
                        key);
                    if (timeRemaining.minutes > 0) displayText += formatTime(timeRemaining.minutes, 'MIN',
                        'cnt-minutes', key);
                    displayText += formatTime(timeRemaining.seconds, 'SEG', 'cnt-seconds', key);

                    updateRoomStatus(room, null, displayText);
                } else {
                    setTimeRemaining(key, timeRemaining); // Actualizar valores existentes
                }
                toggleTimeVisibility(room, timeRemaining); // Mostrar/ocultar días, horas, minutos
            });

            timestamp.setMilliseconds(timestamp.getMilliseconds() + 1000); // Incrementar 1 segundo
        }

        // Obtener el tiempo restante
        function getTimeRemaining(difference) {
            return {
                days: Math.floor(difference / _day),
                hours: Math.floor((difference % _day) / _hour),
                minutes: Math.floor((difference % _hour) / _minute),
                seconds: Math.floor((difference % _minute) / _second)
            };
        }

        // Actualizar el estado y tiempo restante de la sala
        function updateRoomStatus(roomID, statusText = null, timeText = null) {
            if (statusText) $(`#card-game-room__status_${roomID}`).text(statusText);
            if (timeText && $(`#start-time_${roomID}`).html() !== timeText) {
                $(`#start-time_${roomID}`).html(timeText);
            }
        }

        // Iniciar el intervalo si hay salas
        if (dateTimeRooms.rooms.length) {
            getArrRooms(dateTimeRooms.rooms);
            waitingTime = setInterval(() => handleWaitingTime(arrRooms), 1000); // Ejecutar cada segundo
        }
    </script>
@endsection
