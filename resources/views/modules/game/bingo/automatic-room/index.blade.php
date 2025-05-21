@extends($layout)
@section('title', $title)
@section('content')
    <div class="col-12 @if (isset($customer)) p-2 @endif">
        <div class="card">
            @include('includes.card-header', ['subtitle' => 'Automáticas'])
            <div class="card-body">
                @include('includes.games.bingo.report.btn-group', ['view' => 'AUTO'])
                <div class="tab-content" id="myTabContent">
                    <div class="tab-pane fade show active">
                        @include('includes.games.bingo.report.table-info-automatic-bingo-rooms')
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
            'automaticRoom' => true,
        ])->render(),
    ])
    @include('includes.form-other-options')
@endsection
@section('scripts')
    <script src="{{ asset('js/select2.min.js') }}"></script>
    <script>
        const name = @json($name);
        const creation = 'A';
        const creatorType = @json($creator['type'] ?? '');
        const creatorID = @json($creator['id'] ?? '');
        const roomType = $("#room-type");
        const type_prize_pool = $("#type-prize-pool");
        const gameMode = $("#game-mode");
        const roomTypes = @json($roomType);
        const existsRoomTypes = !$.isEmptyObject(roomTypes);
        const arrTypePrizePool = @json($type_prize_pool);
        const arrGameModes = @json($gameModes);
        let coinsCtnPrice = false;

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
            const container = $("#cont-amount-added-prize-pool");

            if (val > 1) {
                container.removeClass('hide-elem');
            } else {
                container.addClass('hide-elem');
                $("#amount-added-prize-pool").empty();
            }
        }

        $(document).on('click', '#btn-execute-job', function() {
            const btnExecuteJob = $(this);

            // Deshabilitar el botón
            btnExecuteJob.prop('disabled', true);

            // Realizar la solicitud
            $.getJSON(route('bingo-automatic-room.executeGenerateBingoRoomsJob', {
                    creatorType,
                    creatorID
                }), {
                    'X-CSRF-TOKEN': $("#5K3_70K").val()
                })
                .done(res => {
                    const message = res.message || 'Operación exitosa.';
                    showToast(message, 'success');
                })
                .fail(err => {
                    const message = err.responseJSON?.message || 'Ha ocurrido un error.';
                    showToast(message, 'warning');
                })
                .always(() => {
                    // Habilitar el botón independientemente del resultado
                    btnExecuteJob.prop('disabled', false);
                });
        });

        type_prize_pool.on('change', function() {
            changeFixedJackpot($(this).val());
        });

        $(document).on("click", `.btn-${name}`, function() {
            if ($(this).data('type') == 1) {
                extraInfo = `{"creatorType": ${creatorType}}`;
            }
            formCRUD($(this), name, 'Nueva Sala', otherCasesMap);
        });

        function insertInfo(type, data = '') {
            if (type == 1) {
                coinsCtnPrice = false;
                $("#container-ctn-price-config").addClass("hide-elem").data("active", 0);
                $('#container-price-config').html('');
            } else {
                getCtnPriceCoin(data.room.id, true);
            }

            gameMode.empty();

            if (creatorType !== 'customer' && existsRoomTypes) {
                roomType.empty();
            }

            type_prize_pool.empty();

            if (type == 1) {
                const defaultOption = `<option value="" disabled selected>Seleccionar:</option>`;

                if (creatorType !== 'customer' && existsRoomTypes) {
                    roomType.html(defaultOption);
                }

                if (arrGameModes.length > 1 && creatorType !== 'customer') {
                    gameMode.html(defaultOption);
                } else {
                    gameMode.html(`<option value="1" selected>Online</option>`);
                }

                type_prize_pool.html(defaultOption);
            }

            if (creatorType !== 'customer' && existsRoomTypes) {
                $.each(roomTypes, function(index, item) {
                    const isSelected = (type == 2 && index == data.room.room_type) ? 'selected' : '';
                    roomType.append(`<option value="${index}" ${isSelected}>${item}</option>`);
                });
            }

            $.each(arrTypePrizePool, function(index, item) {
                const isSelected = (type == 2 && item.id == data.room.type_prize_pool) ? 'selected' : '';
                type_prize_pool.append(`<option value="${item.id}" ${isSelected}>${item.description}</option>`);
            });

            if (creatorType !== 'customer' && arrGameModes.length) {
                $.each(arrGameModes, function(index, item) {
                    const isSelected = (type == 2 && item.id == data.room.game_mode) ? 'selected' : '';

                    if ((creatorType === 'customer' && item.id == 1) || creatorType !== 'customer') {
                        gameMode.append(`<option value="${item.id}" ${isSelected}>${item.description}</option>`);
                    }
                });
            }

            if (type == 1) {
                changeFixedJackpot(1);
            } else if (type == 2) {
                const room = data.room;

                $('#time').val(room.time);
                $('#room-commn').val(room.room_commn);
                $('#ctn-price').val(room.ctn_price);
                $('#time-chip').val(room.time_chip);
                $('#rounds').val(room.rounds);
                $('#pct-serials').val(room.pct_serials);
                $('#num-serials').val(room.num_serials);
                $('#serials-commn').val(room.pct_jackpot);

                if (room.type_prize_pool > 1) {
                    changeFixedJackpot(2);
                    $('#amount-added-prize-pool').val(room.amount_added_prize_pool);
                }
            }

            $(`#modal-${name}`).modal('show');
        }

        $(document).on("click", "#btn-confirm", function() {
            submitForm(name, [
                'time', 'room-type', 'type-prize-pool', 'game-mode', 'room-commn',
                'ctn-price', 'time-chip', 'rounds', 'pct-serials', 'num-serials'
            ]);
        });

        const otherCasesMap = {
            4: function(encryptedId) {
                showToastOptionBtns(
                    'Actualizar el status de esta configuración',
                    'info',
                    'Sí, actualizar',
                    '#form-other-options',
                    route(`${name}.automatic-room.change-status`, {
                        id: encryptedId
                    })
                );
            },
            7: function(encryptedId) {
                showToastOptionBtns(
                    'Deseas copiar esta configuración',
                    'question',
                    'Sí, copiar',
                    '#form-other-options',
                    route(`${name}.automatic-room.copy`, {
                        id: encryptedId
                    })
                );
            },
        };
    </script>
    @vite('resources/js/games/bingo/ctn-price-coins.js')
@endsection
