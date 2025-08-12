@extends('layouts.layout-online')
@section('title', $title)
@section('content')
    <div class="col-md-9 col-lg-6 m-auto" style="max-width:535px">
        <div class="card">
            @include('includes.card-header')
            @if ($status == 1)
                <div class="card-body">
                    <form id="form-ctns" action="{{ route('seller.generateCarton') }}" method="POST">@csrf

                        {{-- Información del Bingo --}}
                        <div class="row">
                            <div class="form-group col-9 col-sm-5">
                                <label for="room">Próximos Bingos</label>
                                <select id="room" name="room" class="form-control" disabled required></select>
                                <div id="cont-tx-prize-pool-room" class="ml-1 mt-1 d-none">
                                    <small><b><span class="prize-pool-type"></span> Actual:</b></small>
                                    <span class="coin-symbol"></span>
                                    <span id="tx-prize-pool-room"></span>
                                </div>
                            </div>

                            <div class="form-group col-6 col-sm-4">
                                <label for="ctn-price-amount">Precio Cartón</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span id="ctn-price-symbol" class="input-group-text"></span>
                                    </div>
                                    <span id="ctn-price-amount" class="form-control bg-light"></span>
                                </div>
                            </div>

                            <div class="form-group col-6 col-sm-3">
                                <label for="num-ctns">#CTNs</label>
                                <input id="num-ctns" type="number" name="num_ctns" class="form-control" min="1"
                                    max="40" required disabled>
                            </div>
                        </div>

                        {{-- Información de la venta --}}
                        <div class="row">
                            @foreach ([['label' => 'Monto Total', 'id' => 'tx-total-amount', 'desc' => 'Costo de los cartones'], ['label' => 'Mi Comisión', 'id' => 'tx-seller-commn', 'desc' => 'Ganancias por venta'], ['label' => 'Total Banca', 'id' => 'tx-commerce-amount', 'desc' => 'Enviar al comercio']] as $info)
                                <div class="col-6 col-sm-4 mt-1">
                                    <label>{{ $info['label'] }}</label>
                                    <div class="d-flex mt-n1">
                                        <span class="coin-symbol"></span>
                                        <span class="sale-info ml-1" id="{{ $info['id'] }}"></span>
                                    </div>
                                    <small>{{ $info['desc'] }}</small>
                                </div>
                            @endforeach
                        </div>

                        {{-- Información del jugador --}}
                        <div class="ctn-player-info d-none">
                            <hr>
                            <div class="alert alert-primary">
                                <h6 class="alert-heading"><strong>¡Atención!</strong></h6>
                                <p class="mb-0">Genera los cartones solo después de confirmar el pago.</p>
                            </div>

                            <div>
                                <h5>Información del Jugador:</h5>
                                <h6 id="text-new-player">Ingresa los datos del nuevo participante</h6>
                                <h6 id="new-player" class="d-none">¡Este usuario está participando!</h6>
                            </div>
                            <div id="info-participating-player" class="col-12 col-md-9 d-none">
                                <label>Cartones comprados: <b id="player-ctns"></b></label>
                                <div class="input-group input-group-sm">
                                    <input id="player-link-rout" class="form-control" disabled style="cursor: pointer;">
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary" type="button" id="button-addon2">
                                            <i class="fa-solid fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Datos adicionales --}}
                        <div class="ctn-player-info mt-1 d-none">
                            <hr>
                            <div class="row">
                                <div class="form-group col-12 col-sm-6">
                                    <label for="type-sale">Venta*</label>
                                    <select id="type-sale" name="type_sale" class="form-control info-form"
                                        required></select>
                                </div>

                                <div id="container-country" class="form-group col-12 col-sm-6">
                                    <label for="country">País*</label>
                                    <select id="country" name="country" class="form-control info-form" required></select>
                                </div>

                                <div class="form-group col-12 col-sm-6 info-player d-none">
                                    <label for="phone">Teléfono*</label>
                                    <div class="input-group">
                                        <input id="phone" name="phone" class="form-control info-form" required>
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-secondary" type="button" id="btn-search-phone"
                                                data-id="1">
                                                <i class="fa-solid fa-magnifying-glass"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group col-12 col-sm-6 info-player d-none">
                                    <label for="name">Nombre*</label>
                                    <input id="name" name="name" class="form-control info-form" type="text">
                                </div>

                                <div class="form-group col-12 col-sm-6 info-player d-none">
                                    <label for="last-name">Apellido*</label>
                                    <input id="last-name" name="last_name" class="form-control info-form" type="text">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="card-footer text-center">
                    <button type="button" id="btn-generate-ctn" class="btn btn-primary" disabled data-block="1">
                        Generar Cartón
                        <span id="loader-btn-ctn" class="spinner-border spinner-border-sm d-none"></span>
                    </button>
                </div>
            @else
                <div class="card-body">
                    <div class="alert alert-info text-center">
                        ¡No puedes vender cartones, contacta a tu gestor!
                    </div>
                </div>
            @endif
        </div>

    </div>
    @include('includes.modal-copy-info', [
        'viewId' => 'copy-link',
        'title' => 'Link de Acceso Jugador',
    ])
@endsection
@section('scripts')
    @vite('resources/js/helpers/time-functions.js')
    <script>
        const form = $("#form-ctns");
        const room = $("#room");
        const rooms = @json($rooms);
        const numCtns = $("#num-ctns");
        const typeSale = $("#type-sale");
        const country = $("#country");
        const sellerCommn = @json($commn);
        const btnGenerate = $("#btn-generate-ctn");
        const btnSearchPhone = $("#btn-search-phone");
        let roomID = '';
        let roomCtnPrice = false;
        let allowsUSDT = false;
        let coinSym = '';
        let ctnPrice = 1;
        let prizePool;
        let playerInfo = '';
        let playerInGame = false;

        if ("{{ Session::has('playerLink') }}") {
            $("#info-to-copy").val({!! json_encode(Session::get('playerLink')) !!});
            setTimeout(function() {
                $("#modal-copy-link").modal('show');
            }, 3000);
        }

        $(document).on("click", "#copy-player-link", function() {
            copyInfo('Enlace Copiado', $("#player-link-rout"));
        });

        $(document).on("click", "#btn-copy", function() {
            copyInfo('Enlace Copiado');
        });

        $("#phone").mask("00000000000");

        $(function() {
            // Agregar la opción predeterminada
            room.append('<option value="" disabled selected>Seleccionar Sala:</option>');

            // Verificar si hay elementos en rooms
            if (!rooms.length) return;

            // Inicializar contadores de salas privadas y públicas
            let hasPrivateRooms = false;
            let hasPublicRooms = false;

            // Crear los grupos de opciones
            const privateGroup = $('<optgroup label="Salas Privadas"></optgroup>');
            const publicGroup = $('<optgroup label="Salas Públicas"></optgroup>');

            // Iterar sobre los elementos rooms
            $.each(rooms, function(index, item) {
                // Convertir la fecha a un formato legible
                const formattedDate = convertAndDisplay(item.config.unix_date);
                // Crear la opción con la fecha y hora
                const option = `<option value="${item.encrypted}">${formattedDate}</option>`;

                // Anexar al grupo correspondiente y marcar que el grupo tiene al menos una sala
                if (item.room_type === 'PV') {
                    privateGroup.append(option);
                    hasPrivateRooms = true;
                } else {
                    publicGroup.append(option);
                    hasPublicRooms = true;
                }
            });

            // Anexar los grupos al select solo si contienen al menos una opción
            if (hasPrivateRooms) room.append(privateGroup);
            if (hasPublicRooms) room.append(publicGroup);

            // Habilitar el selector después de la carga de opciones
            room.prop('disabled', false);
        });

        function formatNumber(num, decimals = 2) {
            // Redondear el número al número de decimales especificado
            const factor = Math.pow(10, decimals);
            const roundedNum = Math.round(num * factor) / factor;
            // Convertir el número a formato de cadena con exactamente los decimales deseados
            let result = roundedNum.toFixed(decimals);

            // Si el tercer decimal es "0", eliminarlo
            if (decimals == 3 && result.endsWith('0')) {
                result = parseFloat(result).toString();
            }

            return result;
        }

        function calculateSale() {
            const ctns = numCtns.val();
            const total = formatNumber(ctnPrice * ctns);
            const totalsellerCommn = formatNumber(total * (sellerCommn / 100));
            const prizePool = formatNumber((total - totalsellerCommn));

            if (roomID) {
                $(".coin-symbol").text(coinSym);
                $("#tx-total-amount").text(total);
                $("#tx-seller-commn").text(totalsellerCommn);
                $("#tx-commerce-amount").text(prizePool);
            }
        }

        function convertCurrency(data) {
            const exchangeRate = data.exchangeRate;
            coinSym = data.sym;
            ctnPrice = formatNumber(data.setPrice ? data.value : exchangeRate * roomCtnPrice);

            $("#ctn-price-symbol").text(coinSym);
            $("#ctn-price-amount").text(ctnPrice);
            $("#tx-prize-pool-room").text(formatNumber((prizePool * exchangeRate), 2));

            calculateSale();
        }

        function validateFormFields() {
            if (country.val() && $("#name").val() && $("#last-name").val() && $("#phone").val()) {
                btnGenerate.prop('disabled', false);
            } else {
                btnGenerate.prop('disabled', true);
            }
        }

        typeSale.on("change", function() {
            const isUSDT = $(this).val() === 'usdt';
            $('#container-country').toggleClass('d-none', isUSDT);
        });

        function handleAllowsUSDT(allowUSDT = false) {
            typeSale.empty().append('<option value="currency" selected>Moneda Local</option>');

            if (allowUSDT) {
                typeSale.append('<option value="usdt">Venta en USDT</option>');
            }
        }

        function getCtnPriceCoin(country) {
            $.getJSON({
                url: route('seller.getCtnPriceCoin', {
                    room: roomID,
                    country
                }),
                success: function(data) {
                    convertCurrency(data);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Failed to fetch exchange rate:', textStatus, errorThrown);
                }
            });
        }

        room.on("change", function() {

            roomID = $(this).val();

            if (!roomID) {
                $(".coin-symbol, .sale-info").empty();
                $(".ctn-player-info, #cont-tx-prize-pool-room").addClass('d-none');
            } else {
                $.getJSON({
                    url: route('seller.getCtnPrice', roomID),
                    success: function(data) {
                        if (data.message) {
                            showToast(data.message, data.type);
                            return;
                        }
                        const {
                            prizePool: prizePoolAmount,
                            prizePoolType,
                            price,
                            coin,
                            countries,
                            USDT
                        } = data;

                        prizePool = formatNumber(prizePoolAmount, 2);
                        roomCtnPrice = formatNumber(price);
                        ctnPrice = roomCtnPrice;
                        coinSym = coin;

                        handleAllowsUSDT(USDT);
                        insertCountries(countries)

                        $("#ctn-price-symbol").text(coinSym);
                        $("#ctn-price-amount").text(ctnPrice);
                        $("#num-ctns").prop('disabled', false).val(1);
                        $(".prize-pool-type").text(prizePoolType == 1 ? 'Bote' : 'Premio');
                        $("#tx-prize-pool-room").text(prizePool);
                        $(".ctn-player-info, #cont-tx-prize-pool-room").removeClass('d-none');

                        calculateSale();
                    }
                });
            }
        });

        numCtns.on('input change', function() {
            validateInput(this, false, function(isValid) {
                if (isValid) calculateSale();
            });
        })

        function clearPlayerInfo() {
            $("#text-new-player").removeClass('d-none');
            $("#player-ctns").text('');
            $('#player-link-rout').val('');
            $("#new-player, #info-participating-player").addClass('d-none');
            btnSearchPhone.html('<i class="fa-solid fa-magnifying-glass"></i>').data('id', '1');
            $('#name, #last-name, #phone').val('').prop('required', false).prop('readonly', false);
        }

        country.on("change", function() {
            const countryVal = $(this).val();
            const country = countryVal || false;

            $("#name, #last-name, #phone").val('').prop('required', true).prop('readonly', false);

            $(".info-player").toggleClass('d-none', !countryVal);

            if (!countryVal) {
                clearPlayerInfo();
            } else {
                getCtnPriceCoin(country);
            }
        });

        function insertCountries(arr) {
            country.empty().append('<option value="" selected>Seleccionar País:</option>');

            arr.forEach(item => {
                country.append(`<option value="${item.id}">${item.name}</option>`);
            });
        }

        $(document).on("input change", ".info-form", function() {
            validateFormFields();
        });

        $(".input-text").on('input', function() {
            $(this).val($(this).val().replace(/[^a-zA-Z]/g, ''));
            if (this.value.length > 15) {
                this.value = this.value.slice(0, 15);
            }
        });

        function getPlayer(playerInfo, action = false) {

            if (!playerInfo) return;

            $.post({
                url: route('seller.getPlayer'),
                headers: {
                    'X-CSRF-TOKEN': $("#5K3_70K").val()
                },
                data: {
                    game: 'BINGO',
                    room: roomID,
                    country: country.val(),
                    player_info: playerInfo,
                },
                dataType: 'json',
                beforeSend: function() {
                    btnGenerate.prop('disabled', true);
                },
                success: function(data) {
                    if (data.response) {
                        showToast('Este usuario está participando', 'warning');

                        const infoPlayer = data.infoPlayer;

                        btnSearchPhone.html('<i class="fa-solid fa-eraser"></i>').data('id', '2');

                        $('#name, #last-name, #phone').prop('required', false).prop('readonly', true);
                        $('#name').val(infoPlayer.name);
                        $('#last-name').val(infoPlayer.lastname);
                        $('#phone').val(infoPlayer.phone);
                        $("#text-new-player, #loader-btn-ctn").addClass('d-none');
                        $("#player-ctns").text(data.ctnsLink[0]);
                        $('#player-link-rout').val(data.ctnsLink[2]);
                        $("#new-player, #info-participating-player").removeClass('d-none');

                        country.val(infoPlayer.country);
                        playerInGame = true;
                        btnGenerate.prop('disabled', false);
                    } else {
                        $('#name, #last-name, #phone').prop('required', true).prop('readonly', false);
                        $("#player-link-rout").val('');
                        $("#new-player, #info-participating-player").addClass('d-none');

                        if (action) {
                            $("#text-new-player, #loader-btn-ctn").removeClass('d-none');
                            form.submit();
                        } else {
                            showToast('Numero no registrado', 'info');
                            validateFormFields();
                        }
                    }
                },
                error: function() {
                    btnGenerate.prop('disabled', false);
                }
            });
        }

        btnSearchPhone.on("click", function() {
            if ($(this).data('id') == 1) {
                getPlayer($("#phone").val());
            } else {
                clearPlayerInfo();
            }
        });

        btnGenerate.on("click", function() {
            if (!playerInGame) {
                getPlayer($("#phone").val(), true);
            } else {
                form.submit();
            }
        });
    </script>
@endsection
