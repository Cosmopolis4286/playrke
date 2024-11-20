@extends($layout)
@section('title', $title)
@section('content')
    <div class="col-12 @if (isset($customer)) p-2 @endif">
        <div class="card">
            @include('includes.card-header')
            <div class="card-body">
                @include('includes.coin-tabs', [
                    'coins' => $coins,
                    'routeName' => $routeName,
                    'coin' => $coin,
                    'userToken' => $userToken ?? false,
                ])
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
@endsection
@section('scripts')
    <script>
        const name = '{{ $name }}';
        const creatorType = @json($creator['type'] ?? '');
        const jackpotType = $("#jackpot-type");
        const gameMode = $("#game-mode");
        const arrCoins = @json($coins);
        const arrRoomType = @json($roomType);
        const arrJackpotType = @json($jackpotType);
        const arrGameModes = @json($gameModes);

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
                    const isSelected = (type == 2 && item.id == data.room.all_for_one) ? 'selected' : '';
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
