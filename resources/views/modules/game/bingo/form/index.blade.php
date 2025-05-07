@extends($layout)
@section('title', $title)
@section('content')
    <div class="col-12 @if (isset($customer)) p-2 @endif">
        <div class="card">
            @include('includes.card-header', [
                'subtitle' => $subtitle,
                'btnCardHeader' => ['route' => route('bingo.configuration'), 'icon' => 'fa-solid fa-gear'],
            ])
            <div class="card-body">
                @include('includes.coin-tabs', ['userToken' => $userToken ?? false])
                @include('includes.games.bingo.report.btn-group', ['view' => $view])
                <div class="tab-content" id="myTabContent">
                    <div class="tab-pane fade show active">
                        @include('includes.games.bingo.report.table-info-bingo-rooms')
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('includes.modal-form', [
        'name' => $name,
        'size' => 'md',
        'dataForm' => view('modules.game.bingo.form.body', [
            'creator' => $creator,
            'timeZone' => $timeZone,
        ])->render(),
    ])
    @include('includes.form-other-options')
    @if ($creator['type'] === 'customer' && $view === 'PRIVATE')
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
    <script src="{{ asset('js/select2.min.js') }}"></script>
    <script>
        const name = @json($name);
        const creation = 'M';
        const roomType = @json($roomType);
        const creatorType = @json($creator['type'] ?? '');
        const creatorID = @json($creator['id'] ?? '');
        const jackpotType = $("#jackpot-type");
        const gameMode = $("#game-mode");
        const bingoPlays = $("#bingo-plays");
        const arrBingoPlays = @json($bingoPlays);
        const arrCoins = @json($coins);
        const arrJackpotType = @json($jackpotType);
        const arrGameModes = @json($gameModes);
        let coinsCtnPrice = false;
        let selectedBingoPlays = [];

        $(function() {
            $('.select2').each(function() {
                $(this).select2({
                    theme: 'bootstrap4',
                    width: 'style',
                    placeholder: $(this).attr('placeholder'),
                    allowClear: Boolean($(this).data('allow-clear')),
                });
            });
        });

        function changeFixedJackpot(val) {
            const container = $("#cont-fixed-jackpot-value");

            if (val > 1) {
                container.removeClass('hide-elem');
            } else {
                container.addClass('hide-elem');
                $("#fixed-jackpot-value").empty();
            }
        }

        jackpotType.on('change', function() {
            changeFixedJackpot($(this).val());
        });

        bingoPlays.on('change', function() {
            const selected = $(this).val() || [];
            const container = $('#container-pct-bingo-plays');

            if (selected.length === 0) {
                container.addClass('hide-elem');
            } else {
                container.removeClass('hide-elem');

                if (selected.includes('random')) {
                    console.log('Random seleccionado');
                }
            }
        });

        function insertBingoPlays() {
            // // Verificar si "random" está seleccionado
            // const randomSelected = selectedBingoPlays.includes('random') ? 'selected' : '';

            // // Agregar opción Random
            // bingoPlays.append(`<option value="random" ${randomSelected}>Random</option>`);

            // Recorrer las jugadas normales
            $.each(arrBingoPlays, function(index, item) {
                const isSelected = selectedBingoPlays.includes(String(item.id)) ? 'selected' : '';
                bingoPlays.append(
                    `<option value="${item.id}" ${isSelected}>${item.name}</option>`
                );
            });

            bingoPlays.trigger('change');
        }

        $(document).on("click", `.btn-${name}`, function() {
            if ($(this).data('type') == 1) {
                extraInfo = `{"creatorType": ${creatorType}}`;
            }
            formCRUD($(this), name, 'Sala de Bingo', otherCasesMap);
        });

        function insertInfo(type, data = '') {
            const isDisabled = type === 2 && data.room.players;
            const isRequired = !isDisabled;

            selectedBingoPlays = [];

            if (type == 1) {
                coinsCtnPrice = false;
                $("#container-ctn-price-config").addClass("hide-elem").data("active", 0);
                $('#container-price-config').html('');
            } else {
                getCtnPriceCoin(data.room.encrypted, true);
            }

            gameMode.prop({
                disabled: isDisabled,
                required: isRequired
            }).empty();

            jackpotType.empty();

            if (type == 1) {
                const defaultOption = `<option value="" disabled selected>Seleccionar:</option>`;

                if (arrGameModes.length > 1) {
                    gameMode.append(defaultOption);
                }

                jackpotType.append(defaultOption);
            }

            $('#room-type').val(roomType);

            $.each(arrJackpotType, function(index, item) {
                const isSelected = (type == 2 && item.id == data.config.jackpot_type) ? 'selected' : '';
                jackpotType.append(`<option value="${item.id}" ${isSelected}>${item.description}</option>`);
            });

            $.each(arrGameModes, function(index, item) {
                const isSelected = (type == 2 && item.id == data.config.game_mode) ? 'selected' : '';

                if ((creatorType === 'customer' && item.id == 1) || creatorType !== 'customer') {
                    gameMode.append(`<option value="${item.id}" ${isSelected}>${item.description}</option>`);
                }
            });

            if (type == 1) {
                changeFixedJackpot(1);
            } else if (type == 2) {
                const config = data.config;

                $('#date').val(config.date);
                $('#time').val(config.time);
                $('#room-commn').val(config.room_commn);
                $('#ctn-price').val(config.ctn_price);
                $('#time-chip').val(config.time_chip);
                $('#pct-serials').val(config.pct_serials);
                $('#num-serials').val(config.num_serials);
                $('#serials-commn').val(config.serials_commn);

                if (config.jackpot_type > 1) {
                    changeFixedJackpot(2);
                    $('#fixed-jackpot-value').val(config.fixed_jackpot_value);
                }

                if (config.types_plays) {
                    selectedBingoPlays = stringToArray(config.types_plays);
                    // $('#max-plays-ramdom').val(config.pct_serials);
                    $('#pct-bingo-plays').val(config.pct_types_plays);
                }
            }

            insertBingoPlays();

            $(`#modal-${name}`).modal('show');
        }

        $(document).on("click", "#btn-confirm", function() {
            submitForm(name, [
                'date', 'room-type', 'jackpot-type', 'game-mode',
                'room-commn', 'ctn-price', 'time-chip', 'pct-serials', 'num-serials'
            ]);
        });

        const otherCasesMap = {
            4: function(encryptedId) {
                showToastOptionBtns(
                    'Auto-Generar esta sala',
                    'question',
                    'Sí, auto-generar',
                    '#form-other-options',
                    route(`${name}.change-replay`, {
                        id: encryptedId
                    })
                );
            },
            5: function(encryptedId) {
                showToastOptionBtns(
                    'Se publicará esta sala al público',
                    'info',
                    'Sí, publicarla',
                    '#form-other-options',
                    route(`${name}.publish`, {
                        id: encryptedId
                    })
                );
            },
            6: function(encryptedId) {
                showToastOptionBtns(
                    'El jackpot de esta sala será reembolsado',
                    'warning',
                    'Sí, reembolsar',
                    '#form-other-options',
                    route(`${name}.refund`, {
                        id: encryptedId
                    })
                );
            },
            7: function(encryptedId) {
                showToastOptionBtns(
                    'Deseas copiar la información de esta sala',
                    'question',
                    'Sí, copiar',
                    '#form-other-options',
                    route(`${name}.copy`, {
                        id: encryptedId
                    })
                );
            },
            8: function(encryptedId) {
                showToastOptionBtns(
                    'Deseas cambiar el status de esta sala',
                    'question',
                    'Sí, cambiar',
                    '#form-other-options',
                    route(`${name}.change-status`, {
                        id: encryptedId
                    })
                );
            }
        };
    </script>
    @vite(['resources/js/games/bingo/form.js', 'resources/js/games/bingo/ctn-price-coins.js'])
    @if ($creator['type'] === 'customer' && $view === 'PRIVATE')
        <script>
            $(document).on("click", '#btn-game-id', function() {
                $('#modal-customer-games').modal('show');
            });

            $(document).on("click", '#btn-confirm-game-id', function() {
                if ($('#customer-game-id').val() && $('#customer-game-product').val()) {
                    $('#form-customer-games').submit();
                } else {
                    showToast('Todos los campos son obligatorios', 'warning');
                }
            });
        </script>
    @endif
@endsection
