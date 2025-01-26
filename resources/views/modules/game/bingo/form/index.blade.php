@extends($layout)
@section('title', $title)
@section('content')
    <div class="col-12 @if (isset($customer)) p-2 @endif">
        <div class="card">
            @include('includes.card-header')
            <div class="card-body">
                @include('includes.coin-tabs', ['userToken' => $userToken ?? false])
                @include('includes.game.bingo.report.btn-group', ['automaticRoom' => true])
                <div class="tab-content" id="myTabContent">
                    <div class="tab-pane fade show active">
                        @include('includes.game.bingo.report.table-info-bingo-rooms')
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('includes.modal-form', [
        'name' => $name,
        'size' => 'md',
        'dataForm' => view('modules.game.bingo.form.body', ['creator' => $creator])->render(),
    ])
    @include('includes.form-other-options')
    @if ($creator['type'] === 'customer')
        @include('includes.modal-form', [
            'modalTitle' => 'ID del Juego - API',
            'name' => 'customer-games',
            'size' => 'sm',
            'PUT' => true,
            'action' => route('games.handleCustomerGames', ['id' => $customerGameID->id ?? null]),
            'btnConfirm' => 'btn-confirm-game-id',
            'dataForm' => view('modules.customer.customer-games.form', [
                'creatorID' => $creator['id'],
                'customerGameID' => $customerGameID,
            ])->render(),
        ])
    @endif
@endsection
@section('scripts')
    <script>
        const name = '{{ $name }}';
        const creatorType = @json($creator['type'] ?? '');
        const creatorID = @json($creator['id'] ?? '');
        const jackpotType = $("#jackpot-type");
        const gameMode = $("#game-mode");
        const arrCoins = @json($coins);
        const arrRoomType = @json($roomType);
        const arrJackpotType = @json($jackpotType);
        const arrGameModes = @json($gameModes);
        let inCourse = false;
        let arrCoinsCtnPrice = false;

        function changeFixedJackpot(val) {
            const container = $("#cont-fixed-jackpot-value");

            if (val > 1) {
                container.removeClass('hide-elem');
            } else {
                container.addClass('hide-elem');
                $("#fixed-jackpot-value").empty();
            }
        }

        $(document).on('change', '#jackpot-type', function() {
            changeFixedJackpot($(this).val());
        });

        $(document).on("click", `.btn-${name}`, function() {
            if ($(this).data('type') == 1) {
                extraInfo = `{"creatorType": ${creatorType}}`;
            }
            formCRUD($(this), name, 'Nueva Sala', '{{ asset('') }}bingocash', otherCasesMap);
        });

        function insertInfo(type, data = '') {
            const isDisabled = type === 2 && data.room.players;
            const isRequired = !isDisabled;
            let roomType = false;

            if (type == 1) {
                arrCoinsCtnPrice = false;
                $("#container-ctn-price-config").addClass("hide-elem").data("active", 0);
                $('#container-price-config').html('');
            } else {
                getCtnPriceCoin(data.room.encrypted);
            }

            gameMode.prop({
                disabled: isDisabled,
                required: isRequired
            }).empty();

            if (creatorType === 'commerce' && arrRoomType.length) {
                roomType = $("#room-type");
                roomType.prop({
                    disabled: isDisabled,
                    required: isRequired
                }).empty();
            }

            jackpotType.empty();

            if (type == 1) {
                const defaultOption = `<option value="" disabled selected>Seleccionar:</option>`;

                if (creatorType === 'commerce' && arrRoomType.length) {
                    roomType.append(defaultOption);
                }

                if (arrGameModes.length > 1) {
                    gameMode.append(defaultOption);
                }

                jackpotType.append(defaultOption);
            }

            if (creatorType === 'commerce' && arrRoomType.length) {
                $.each(arrRoomType, function(index, item) {
                    const isSelected = (type == 2 && item.id == data.room.room_type) ? 'selected' : '';
                    roomType.append(`<option value="${item.id}" ${isSelected}>${item.description}</option>`);
                });
            }

            $.each(arrJackpotType, function(index, item) {
                const isSelected = (type == 2 && item.id == data.config.jackpot_type) ? 'selected' : '';
                jackpotType.append(`<option value="${item.id}" ${isSelected}>${item.description}</option>`);
            });

            $.each(arrGameModes, function(index, item) {
                const isSelected = (type == 2 && item.id == data.room.game_mode) ? 'selected' : '';

                if ((creatorType === 'customer' && item.id == 1) || creatorType !== 'customer') {
                    gameMode.append(`<option value="${item.id}" ${isSelected}>${item.description}</option>`);
                }
            });

            if (type == 1) {
                changeFixedJackpot(1);
            } else if (type == 2 && data.config.jackpot_type > 1) {
                changeFixedJackpot(2);
                $('#fixed-jackpot-value').val(data.config.fixed_jackpot_value);
            }

            if (type == 2) {
                $('#date').val(data.room.date);
                $('#time').val(data.room.time);
                $('#ctn-price').val(data.room.ctn_price);
                $('#val-serials').val(data.config.val_serials);
                $('#num-serials').val(data.config.num_serials);
                $('#commerce-commission').val(data.config.room_commn);
                $('#serials-commission').val(data.config.serials_commn);
            }

            $(`#modal-${name}`).modal('show');
        }

        $(document).on("click", "#btn-confirm", function() {
            submitForm(name, [
                'date', 'time', 'room-type', 'jackpot-type', 'game-mode',
                'ctn-price', 'val-serials', 'commerce-commission'
            ]);
        });

        function deleteInfo(encryptedId) {
            showToastOptionBtns(
                'Esta sala será eliminada definitivamente',
                'info',
                'Sí, eliminar',
                '#form-other-options',
                `{{ route('bingo.delete', ['id' => ':id']) }}`,
                encryptedId
            );
        }

        const otherCasesMap = {
            4: function(encryptedId) {
                showToastOptionBtns(
                    'Repetir esta sala cada 24h',
                    'question',
                    'Sí, repetir',
                    '#form-other-options',
                    `{{ route('bingo.replay', ['id' => ':id']) }}`,
                    encryptedId
                );
            },
            5: function(encryptedId) {
                showToastOptionBtns(
                    'Se publicará esta sala al público',
                    'info',
                    'Sí, publicarla',
                    '#form-other-options',
                    `{{ route('bingo.publish', ['id' => ':id']) }}`,
                    encryptedId
                );
            },
            6: function(encryptedId) {
                showToastOptionBtns(
                    'El jackpot de esta sala será reembolsado',
                    'warning',
                    'Sí, reembolsar',
                    '#form-other-options',
                    `{{ route('bingo.refund', ['id' => ':id']) }}`,
                    encryptedId
                );
            },
            7: function(encryptedId) {
                showToastOptionBtns(
                    'Deseas copiar la información de esta sala',
                    'question',
                    'Sí, copiar',
                    '#form-other-options',
                    `{{ route('bingo.copy', ['id' => ':id']) }}`,
                    encryptedId
                );
            },
            8: function(encryptedId) {
                showToastOptionBtns(
                    'Deseas cambiar el status de esta sala',
                    'question',
                    'Sí, cambiar',
                    '#form-other-options',
                    `{{ route('bingo.change-status', ['id' => ':id']) }}`,
                    encryptedId
                );
            }
        };

        function handlePriceCtnForCoins(arrCtnPrice) {
            // Generar el contenido HTML usando map y join
            const dataCoins = arrCtnPrice.map((elem) =>
                `<div class="col col-sm-6 col-md-4 mt-1">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">${elem.isoCode}</span>
                    </div>
                    <input 
                        type="text" 
                        class="form-control" 
                        name="ctn_price_coins[${elem.coin}]" 
                        id="ctn-price-coin-${elem.coin}" 
                        aria-label="Precio para ${elem.isoCode}" 
                        value="${elem.ctnPrice}">
                </div>
            </div>`).join('');

            // Actualizar el contenido del contenedor
            $('#container-price-config').html(dataCoins);

            // Marcar que las monedas ya han sido procesadas
            arrCoinsCtnPrice = true;
        }

        $(document).on("click", "#btn-ctn-price-config", function() {
            const container = $("#container-ctn-price-config");
            // Alternar la clase y el valor del atributo 'data-active'
            const isActive = container.toggleClass("hide-elem").data("active") === 1 ? 0 : 1;
            container.data("active", isActive);

            if (isActive === 0 || arrCoinsCtnPrice) return;

            getCtnPriceCoin();
        });

        function getCtnPriceCoin(room = '') {
            $.ajax({
                url: `{{ route('bingo.getCtnPriceForCoin', ['creatorType' => ':1', 'creatorID' => ':2', 'room' => ':3']) }}`
                    .replace(':1', creatorType).replace(':2', creatorID).replace(':3', room),
                method: 'GET',
                dataType: 'json',
                beforeSend: function() {
                    inCourse = true;
                    $('#container-spinner').show();
                },
                success: function(res) {
                    handlePriceCtnForCoins(res);
                    $('#container-spinner').hide();
                },
                error: function(error) {
                    $('#container-spinner').hide();
                },
                complete: function() {
                    inCourse = false;
                }
            });
        }
    </script>
    @if ($creator['type'] === 'customer')
        <script>
            $(document).on("click", '#btn-game-id', function() {
                $('#modal-customer-games').modal('show');
            });

            $(document).on("click", '#btn-confirm-game-id', function() {
                if ($('#customer-game-id').val()) {
                    $('#form-customer-games').submit();
                } else {
                    $('#customer-game-id').focus();
                    showToast('ID del Juego Obligatorio', 'warning');
                }
            });
        </script>
    @endif
@endsection
