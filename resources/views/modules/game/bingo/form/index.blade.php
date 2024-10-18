@extends($layout)
@section('title', $title)
@section('content')
    <div class="col-12 @if (isset($customer)) p-2 @endif">
        <div class="card">
            @include('components.card-header', ['title' => $title, 'returnBtn' => $returnBtn])
            <div class="card-body">
                @component('components.coin-tabs', [
                    'coins' => $coins,
                    'routeName' => $routeName,
                    'coin' => $coin,
                    'userToken' => $userToken ?? false,
                ])
                @endcomponent
                <div class="d-flex justify-content-between">
                    <button type="button" class="btn-{{ $name }} btn btn-sm btn-success" data-type="1">
                        <i class="fas fa-plus-square"></i> Crear
                    </button>
                    <a href="{{ $activeRooms > 0 ? $GMRoute : '#' }}"
                        class="ml-1 btn btn-sm btn-primary {{ $activeRooms > 0 ? '' : 'disabled' }}">
                        <i class="fas fa-hat-wizard"></i> Ver Salas
                    </a>
                </div>
                <div class="tab-content" id="myTabContent">
                    <div class="tab-pane fade show active">
                        @component('components.game.bingo.report.table-info-bingo-rooms', [
                            'userType' => $userType,
                            'userID' => $userID,
                            'name' => $name,
                            'routeRKE' => $routeRKE,
                            'rooms' => $rooms,
                            'coinSym' => $coinSym,
                            'exchangeRate' => $exchangeRate,
                        ])
                        @endcomponent
                    </div>
                </div>
            </div>
        </div>
    </div>
    @component('components.modal-form', [
        'name' => $name,
        'size' => 'md',
        'dataForm' => view('modules.game.bingo.form.body', ['user' => [$userToken ?? null, $userType]])->render(),
    ])
    @endcomponent
    @include('components.form-other-options')
@endsection
@section('scripts')
    <script>
        const name = '<?php echo $name; ?>';
        const userType = '<?php echo $userType; ?>';
        // const country = $("#country");
        // const coin = $("#coin");
        const jackpotType = $("#jackpot-type");
        const gameMode = $("#game-mode");
        const arrCoins = JSON.parse('<?php echo json_encode($coins); ?>');
        const arrJackpotType = JSON.parse('<?php echo json_encode($jackpotType); ?>')
        const arrGameModes = JSON.parse('<?php echo json_encode($gameModes); ?>')

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
                extraInfo = userType;
            }
            formCRUD($(this), name, 'Nueva Sala', '{{ asset('') }}bingocash', otherCasesMap);
        });

        function insertInfo(type, data = '') {
            gameMode.prop({
                disabled: type == 2 && data.room.players,
                required: !(type == 2 && data.room.players)
            });

            jackpotType.empty();
            gameMode.empty();

            if (type == 1) {
                jackpotType.append(`<option value="" selected>Seleccionar:</option>`);
                if (arrGameModes.length > 1) {
                    gameMode.append(`<option value="" selected>Seleccionar:</option>`);
                }
            }

            $.each(arrJackpotType, function(index, item) {
                const isSelected = (type == 2 && item.id == data.config.jackpot_type) ? 'selected' : '';
                jackpotType.append(`<option value="${item.id}" ${isSelected}>${item.description}</option>`);
            });

            $.each(arrGameModes, function(index, item) {
                const isSelected = (type == 2 && item.id == data.room.game_mode) ? 'selected' : '';
                gameMode.append(`<option value="${item.id}" ${isSelected}>${item.description}</option>`);
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
                'date', 'time', 'game-mode', 'ctn-price',
                'val-serials', 'commerce-commission', 'jackpot-type'
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
        };
    </script>
@endsection
