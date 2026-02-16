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
        'size' => 'lg',
        'style' => 'style="max-width: 625px;"',
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
        const prizePoolType = $("#prize-pool-type");
        const coins = @json($coins);
        const allowedCoins = $("#allowed-coins");
        const gameMode = $("#game-mode");
        const bingoPlays = $("#bingo-plays");
        const arrBingoPlays = @json($bingoPlays);
        const arrCoins = @json($coins);
        const arrPrizePoolType = @json($typesPrizePool);
        const arrGameModes = @json($gameModes);
        let room = '';
        let totalPct = false;
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

        function changeFixedJackpot(value) {
            const container = $("#container-amount-added-prize-pool");

            if (value > 1) {
                container.show();
            } else {
                container.hide();
                $("#amount-added-prize-pool").empty();
            }
        }

        prizePoolType.on('change', function() {
            changeFixedJackpot($(this).val());
        });

        bingoPlays.on('change', function() {
            const selected = $(this).val() || [];
            const container = $('#container-plays-pct');

            if (selected.length === 0) {
                container.hide();
            } else {
                container.show();

                if (selected.includes('random')) {
                    console.log('Random seleccionado');
                }
            }
        });

        function insertAllowedCoins(selectedCoins = []) {
            allowedCoins.empty();
            $.each(coins, function(index, item) {
                const isSelected = selectedCoins.some(val => val == item.id) ? 'selected' : '';
                allowedCoins.append(
                    `<option value="${item.id}" ${isSelected}>(${item.iso_code}) ${item.long_name}</option>`
                );
            });
        }

        allowedCoins.on('change', function() {
            getCtnPriceCoin(room);
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
            coinsCtnPrice = false;
            room = type == 2 ? data.room.encrypted : '';

            $(".content-rounds-pct, #container-rounds-config, #container-ctn-price-config").hide();
            $('#content-rounds-config, #content-price-config').empty();

            if (data.config.rounds > 1) getRoundsConfig(room);
            insertAllowedCoins(data.config.allowed_coins ?? []);
            getCtnPriceCoin(room);

            gameMode.prop({
                disabled: isDisabled,
                required: isRequired
            }).empty();

            prizePoolType.empty();

            if (type == 1) {
                const defaultOption = `<option value="" disabled selected>Seleccionar:</option>`;

                if (arrGameModes.length > 1) {
                    gameMode.html(defaultOption);
                }

                prizePoolType.html(defaultOption);
            }

            $('#room-type').val(roomType);

            $.each(arrPrizePoolType, function(index, item) {
                const isSelected = (type == 2 && item.id == data.config.prize_pool_type) ? 'selected' : '';
                prizePoolType.append(`<option value="${item.id}" ${isSelected}>${item.description}</option>`);
            });

            $.each(arrGameModes, function(index, item) {
                const isSelected = (type == 2 && item.id == data.config.game_mode) ? 'selected' : '';

                if ((creatorType === 'customer' && item.id == 1) || creatorType !== 'customer') {
                    gameMode.append(`<option value="${item.id}" ${isSelected}>${item.description}</option>`);
                }
            });

            let addedPrizePool = 1;

            if (type == 2) {
                const config = data.config;

                $('#date').val(config.date);
                $('#time').val(config.time);
                $('#room-commn').val(config.room_commission);
                $('#jackpot-pct').val(config.jackpot_pct);
                $('#ctn-price').val(config.ctn_price);
                $('#time-chip').val(config.time_chip);
                $('#rounds').val(config.rounds);
                $('#rounds-pct').val(config.rounds_pct);
                $('#serials-pct').val(config.serials_pct);
                $('#num-serials').val(config.num_serials);

                if (config.prize_pool_type > 1) {
                    addedPrizePool = config.prize_pool_type;
                    $('#amount-added-prize-pool').val(config.amount_added_prize_pool);
                }

                if (config.rounds > 1) {
                    $('.content-rounds-pct').show();
                }

                if (config.play_types) {
                    selectedBingoPlays = stringToArray(config.play_types);
                    $('#plays-pct').val(config.plays_pct);
                }
            }

            changeFixedJackpot(addedPrizePool);

            // insertBingoPlays();

            $(`#modal-${name}`).modal('show');
        }

        $(document).on("click", "#btn-confirm", function() {
            submitForm(name, [
                'date', 'room-type', 'prize-pool-type', 'game-mode', 'room-commn',
                'ctn-price', 'allowed-coins', 'time-chip', 'rounds', 'serials-pct', 'num-serials'
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
                    'El prize_pool de esta sala será reembolsado',
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
    @vite(['resources/js/games/bingo/form.js', 'resources/js/games/bingo/ctn-price-coins.js', 'resources/js/games/bingo/rounds-config.js'])
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
