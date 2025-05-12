@extends('layouts.app_rke')
@section('title', $title)
@section('content')
    <div class="col-md-9 col-lg-6 m-auto" style="max-width:535px">
        <div class="card">
            @include('includes.card-header')
            @if ($status == 1)
                <div class="card-body">
                    <form id="form-ctns" action="{{ route('seller.generateCarton') }}" method="POST">@csrf
                        <div class="row">
                            <div class="form-group col-9 col-sm-5">
                                <label for="room">Próximos Bingo</label>
                                <select disabled id="room" class="form-control" name="room" required></select>
                                <div id="cont-tx-prize-pool-room" class="mt-1 hide-elem">
                                    <b><small><span class="jackpot-type"></span> Actual:</small></b>
                                    <span class="coin-symbol"></span>
                                    <span id="tx-prize-pool-room"></span>
                                </div>
                            </div>
                            <div class="form-group col-6 col-sm-4">
                                <label for="ctn-price">Precio Carton</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span id="ctn-price-symbol" class="input-group-text"></span>
                                    </div>
                                    <span id="ctn-price-amount" class="form-control"></span>
                                </div>
                            </div>
                            <div class="form-group col-6 col-sm-3">
                                <label for="num-ctns">#CTNs</label>
                                <input id="num-ctns" class="form-control" type="number" name="num_ctns" min="1"
                                    max="40" step="1" required disabled>
                            </div>
                        </div>
                        <div class="row mt-1">
                            <div class="col-6 col-sm-4">
                                <label for="total-amount">Monto Total</label>
                                <div>
                                    <span class="coin-symbol"></span>
                                    <span class="sale-info" id="tx-total-amount"></span>
                                </div>
                                <small>Costo de los cartones</small>
                            </div>
                            <div class="col-6 col-sm-4">
                                <label for="seller-commn">Mi Comisión</label>
                                <div>
                                    <span class="coin-symbol"></span>
                                    <span class="sale-info" id="tx-seller-commn"></span>
                                </div>
                                <small>Ganancias por venta</small>
                            </div>
                            <div class="col-12 col-sm-4">
                                <label for="commerce-amount">Total Banca</label>
                                <div>
                                    <span class="coin-symbol"></span>
                                    <span class="sale-info" id="tx-commerce-amount"></span>
                                </div>
                                <small>Enviar al Comercio</small>
                            </div>
                        </div>
                        <div class="ctn-player-info hide-elem">
                            <hr>
                            <div class="row">
                                <div class="col-12">
                                    <div class="alert alert-primary" role="alert">
                                        <h6 class="alert-heading"><strong>¡Atencion!</strong></h6>
                                        <p class="mt-n1 mb-0">Genera los cartones solo después de confirmar el pago.</p>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <h5>Informacion del Jugador:</h5>
                                    <h6 id="get-data-player">Ingresa los datos del nuevo
                                        participante
                                    </h6>
                                    <h6 id="new-player" class="hide-elem">
                                        ¡Este usuario esta participando!
                                    </h6>
                                </div>
                                <div id="cont-info-player" class="col-12 col-md-9 hide-elem">
                                    <label class="mb-n1">Cartones comprados: <b id="player-ctns"></b></label>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <label for="copy-player-link" class="mt-2" style="min-width: 120px;">Link de
                                            acceso:</label>
                                        <div id="copy-player-link" class="input-group input-group-sm ml-auto">
                                            <input disabled id="player-link-rout" class="form-control"
                                                aria-label="Link Jugador" aria-describedby="button-addon2"
                                                style="cursor: pointer" />
                                            <div class="input-group-append">
                                                <button class="btn btn-outline-secondary" type="button" id="button-addon2">
                                                    <i class="fa-solid fa-copy"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="ctn-player-info mt-1 hide-elem">

                            <div class="row">
                                <div class="form-group col-12 col-sm-6">
                                    <label for="type-sale">Venta*</label>
                                    <select id="type-sale" class="form-control info-form" name="type_sale"
                                        required></select>
                                </div>
                                <div class="form-group col-12 col-sm-6" id="cont-country">
                                    <label for="country">Pais*</label>
                                    <select id="country" class="form-control info-form" name="country" required></select>
                                </div>
                                <div class="form-group col-12 col-sm-6 hide-elem info-player">
                                    <label for="phone">Teléfono*</label>
                                    <div class="input-group">
                                        <input type="text" id="phone" class="form-control info-form" name="phone"
                                            required>
                                        <div class="input-group-append">
                                            <button id="btn-search-phone" class="btn btn-outline-secondary"
                                                type="button" data-id="1">
                                                <i class="fa-solid fa-magnifying-glass"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group col-12 col-sm-6 hide-elem info-player">
                                    <label for="name">Nombre*</label>
                                    <input id="name" class="form-control input-text info-form" type="text"
                                        name="name">
                                </div>
                                <div class="form-group col-12 col-sm-6 hide-elem info-player">
                                    <label for="last-name">Apellido*</label>
                                    <input id="last-name" class="form-control input-text info-form" type="text"
                                        name="last_name">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-muted text-center">
                    <button type="button" data-block="0" disabled id="btn-generate-ctn" class="btn btn-primary">Generar
                        Cartón <span id="loader-btn-ctn" class="spinner-border spinner-border-sm" role="status"
                            aria-hidden="true" style="display: none"></span>
                    </button>
                </div>
            @else
                <div class="card-body">
                    <div class="alert alert-info alert-dismissible text-center" role="alert">
                        ¡No puedes vender cartones, contacta a tu gestor!
                    </div>
                </div>
            @endif
        </div>
    </div>
    @include('includes.modal-copy-info', [
        'name' => 'copy-link',
        'title' => 'Link de Acceso Jugador',
    ])
@endsection
@section('scripts')
    @vite('resources/js/rke/time-functions.js')
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
        let prizePoolRoom;
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
            let ctns = numCtns.val();
            let total = formatNumber(ctnPrice * ctns);
            let totalsellerCommn = formatNumber(total * (sellerCommn / 100));
            let prizePoolAmount = formatNumber((total - totalsellerCommn));

            if (roomID) {
                $(".coin-symbol").text(coinSym);
                $("#tx-total-amount").html(total);
                $("#tx-seller-commn").html(totalsellerCommn);
                $("#tx-commerce-amount").html(prizePoolAmount);
            }
        }

        function convertCurrency(data) {
            const exchangeRate = data.exchangeRate;
            coinSym = data.sym;
            ctnPrice = formatNumber(data.setPrice ? data.value : exchangeRate * roomCtnPrice);
            $("#ctn-price-symbol").text(coinSym);
            $("#ctn-price-amount").text(ctnPrice);
            $("#tx-prize-pool-room").text(formatNumber((prizePoolRoom * exchangeRate), 3));
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
            if (allowsUSDT) {
                $('#cont-country').hide();
            }
        });

        function handleAllowsUSDT(USDT) {
            allowsUSDT = USDT;
            typeSale.empty().append('<option value="currency" selected>Moneda Local</option>');

            if (allowsUSDT) {
                typeSale.append('<option value="usdt">Venta en USDT</option>');
            }
        }

        function getCtnPriceCoin(country) {
            $.getJSON({
                url: route('seller.getCtnPriceCoin', {
                    room: roomID,
                    country
                }),
                data: {
                    '_token': $("#5K3_70K").val()
                },
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
                $(".ctn-player-info, #cont-tx-prize-pool-room").addClass('hide-elem');
            } else {
                $.getJSON({
                    url: route('seller.getCtnPrice', roomID),
                    headers: {
                        'X-CSRF-TOKEN': $("#5K3_70K").val()
                    },
                    success: function(data) {
                        if (data.message) {
                            showToast(data.message, data.type);
                            return;
                        }
                        const {
                            prizePool,
                            jackpotType,
                            price,
                            coin,
                            countries,
                            USDT
                        } = data;

                        handleAllowsUSDT(USDT);
                        roomCtnPrice = formatNumber(price);
                        ctnPrice = roomCtnPrice;
                        coinSym = coin;
                        $("#ctn-price-symbol").text(coinSym);
                        $("#ctn-price-amount").text(ctnPrice);
                        $("#num-ctns").prop('disabled', false).val(1);
                        insertCountries(countries)
                        prizePoolRoom = formatNumber(prizePool, 3);
                        $(".jackpot-type").text(jackpotType == 1 ? 'Bote' : 'Premio');
                        $("#tx-prize-pool-room").text(prizePoolRoom);
                        calculateSale();
                        $(".ctn-player-info, #cont-tx-prize-pool-room").removeClass('hide-elem');
                    }
                });
            }
        });

        numCtns.on('input change', function() {
            validateInput(this, false, function(isValid) {
                if (isValid) {
                    calculateSale();
                }
            });
        })

        function clearPlayerInfo() {
            $("#get-data-player").show();
            $("#player-ctns").text('');
            $('#player-link-rout').val('');
            $("#new-player, #cont-info-player").addClass('hide-elem');
        }

        country.on("change", function() {
            const countryVal = $(this).val();
            const country = countryVal || 0;

            $("#name, #last-name, #phone").val('').prop('required', true).prop('readonly', false).show();

            $(".info-player").toggleClass('hide-elem', countryVal === '');

            if (countryVal === '') {
                clearPlayerInfo();
            }

            if (countryVal) getCtnPriceCoin(country);
        });

        function insertCountries(arr) {
            country.empty().append('<option value="" selected>Seleccionar País:</option>');

            arr.forEach(item => {
                country.append(`<option value="${item.id}">${item.name}</option>`);
            });
        }

        $(document).on("input", ".info-form", function() {
            validateFormFields();
        });

        $(".input-text").on('input', function() {
            $(this).val($(this).val().replace(/[^a-zA-Z]/g, ''));
            if (this.value.length > 15) {
                this.value = this.value.slice(0, 15);
            }
        });

        function getPlayer(playerInfo, action = false) {
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
                        const infoPlayer = data.infoPlayer;
                        showToast('Este usuario está participando', 'warning');

                        btnSearchPhone.html('<i class="fa-solid fa-eraser"></i>').data('id', 2);

                        $('#name, #last-name, #phone').prop('required', false).prop('readonly', true);
                        country.val(infoPlayer.country);
                        $('#name').val(infoPlayer.name);
                        $('#last-name').val(infoPlayer.lastname);
                        $('#phone').val(infoPlayer.phone);
                        $("#get-data-player, #loader-btn-ctn").hide();
                        $("#player-ctns").text(data.ctnsLink[0]);
                        $('#player-link-rout').val(data.ctnsLink[2]);
                        $("#new-player, #cont-info-player").removeClass('hide-elem');
                        playerInGame = true;
                        btnGenerate.prop('disabled', false);
                    } else {
                        $('#name, #last-name, #phone').prop('required', true).prop('readonly', false);
                        $("#player-link-rout").val('');
                        $("#new-player, #cont-info-player").addClass('hide-elem');

                        if (action) {
                            $("#get-data-player, #loader-btn-ctn").show();
                            form.submit();
                        } else {
                            if (btnSearchPhone.data('id') == 2) {
                                btnSearchPhone.html('fa-solid fa-magnifying-glass').data('id', 2);
                            }
                            showToast('Numero no registrado', 'info');
                            btnGenerate.prop('disabled', false);
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
