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
        'style' => 'style="max-width: 700px;"',
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
        const type_prize_pool = $("#type-prize-pool");
        const gameMode = $("#game-mode");
        const rounds = $("#rounds");
        const roundsPct = $('#rounds-pct');
        const bingoPlays = $("#bingo-plays");
        const arrBingoPlays = @json($bingoPlays);
        const arrCoins = @json($coins);
        const arrTypePrizePool = @json($type_prize_pool);
        const arrGameModes = @json($gameModes);
        let bingoRounds = false;
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
            const container = $("#container-amount-added-prize-pool");

            if (val > 1) {
                container.show();
            } else {
                container.hide();
                $("#amount-added-prize-pool").empty();
            }
        }

        type_prize_pool.on('change', function() {
            changeFixedJackpot($(this).val());
        });

        bingoPlays.on('change', function() {
            const selected = $(this).val() || [];
            const container = $('#container-songs-pct');

            if (selected.length === 0) {
                container.hide();
            } else {
                container.show();

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
                $(".content-rounds-pct, #container-rounds-config, #container-ctn-price-config").hide();
                $("#btn-rounds-config, #btn-ctn-price-config").data("active", '0');
                $('#content-rounds-config, #content-price-config').html('');
            } else {
                getCtnPriceCoin(data.room.encrypted);
                getRoundsConfig(data.room.encrypted);
            }

            gameMode.prop({
                disabled: isDisabled,
                required: isRequired
            }).empty();

            type_prize_pool.empty();

            if (type == 1) {
                const defaultOption = `<option value="" disabled selected>Seleccionar:</option>`;

                if (arrGameModes.length > 1) {
                    gameMode.html(defaultOption);
                }

                type_prize_pool.html(defaultOption);
            }

            $('#room-type').val(roomType);

            $.each(arrTypePrizePool, function(index, item) {
                const isSelected = (type == 2 && item.id == data.config.type_prize_pool) ? 'selected' : '';
                type_prize_pool.append(`<option value="${item.id}" ${isSelected}>${item.description}</option>`);
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
                $('#jackpot-pct').val(config.jackpot_pct);
                $('#ctn-price').val(config.ctn_price);
                $('#time-chip').val(config.time_chip);
                $('#rounds').val(config.rounds);
                $('#rounds_pct').val(config.rounds_pct);
                $('#serials-pct').val(config.serials_pct);
                $('#num-serials').val(config.num_serials);

                if (config.type_prize_pool > 1) {
                    changeFixedJackpot(2);
                    $('#amount-added-prize-pool').val(config.amount_added_prize_pool);
                }

                if (config.types_songs) {
                    selectedBingoPlays = stringToArray(config.types_songs);
                    $('#songs-pct').val(config.songs_pct);
                }
            }

            insertBingoPlays();

            $(`#modal-${name}`).modal('show');
        }

        $(document).on("click", "#btn-confirm", function() {
            submitForm(name, [
                'date', 'room-type', 'type-prize-pool', 'game-mode', 'room-commn',
                'ctn-price', 'time-chip', 'rounds', 'serials-pct', 'num-serials'
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
