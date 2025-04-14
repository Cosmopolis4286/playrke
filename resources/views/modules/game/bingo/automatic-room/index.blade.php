@extends($layout)
@section('title', $title)
@section('content')
    <div class="col-12 @if (isset($customer)) p-2 @endif">
        <div class="card">
            @include('includes.card-header', ['subtitle' => 'Automáticas'])
            <div class="card-body">
                @include('includes.game.bingo.report.btn-group', ['view' => 'AUTO'])
                <div class="tab-content" id="myTabContent">
                    <div class="tab-pane fade show active">
                        @include('includes.game.bingo.report.table-info-automatic-bingo-rooms')
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
            'automaticRoom' => true,
        ])->render(),
    ])
    @include('includes.form-other-options')
@endsection
@section('scripts')
    <script>
        const name = '{{ $name }}';
        const creation = 'A';
        const routeGenerateBingoRoomsJob = @json($routeGenerateBingoRoomsJob);
        const routeGetCtnPriceForCoin = @json($routeGetCtnPriceForCoin);
        const creatorType = @json($creator['type'] ?? '');
        const creatorID = @json($creator['id'] ?? '');
        const roomType = $("#room-type");
        const jackpotType = $("#jackpot-type");
        const gameMode = $("#game-mode");
        const roomTypes = @json($roomType);
        const existsRoomTypes = !$.isEmptyObject(roomTypes);
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

        $(document).on('click', '#btn-execute-job', function() {
            const btnExecuteJob = $(this);

            // Deshabilitar el botón
            btnExecuteJob.prop('disabled', true);

            // Realizar la solicitud
            $.getJSON(routeGenerateBingoRoomsJob, {
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

        $(document).on('change', '#jackpot-type', function() {
            changeFixedJackpot($(this).val());
        });

        $(document).on("click", `.btn-${name}`, function() {
            if ($(this).data('type') == 1) {
                extraInfo = `{"creatorType": ${creatorType}}`;
            }
            formCRUD($(this), name, 'Nueva Sala', `{{ asset('') }}${name}/automatic-room`, otherCasesMap);
        });

        function insertInfo(type, data = '') {
            if (type == 1) {
                arrCoinsCtnPrice = false;
                $("#container-ctn-price-config").addClass("hide-elem").data("active", 0);
                $('#container-price-config').html('');
            } else {
                getCtnPriceCoin(data.room.id, true);
            }

            gameMode.empty();

            if (creatorType !== 'customer' && existsRoomTypes) {
                roomType.empty();
            }

            jackpotType.empty();

            if (type == 1) {
                const defaultOption = `<option value="" disabled selected>Seleccionar:</option>`;

                if (creatorType !== 'customer' && existsRoomTypes) {
                    roomType.append(defaultOption);
                }

                if (arrGameModes.length > 1 && creatorType !== 'customer') {
                    gameMode.append(defaultOption);
                } else {
                    gameMode.append(`<option value="1" selected>Online</option>`);
                }

                jackpotType.append(defaultOption);
            }

            if (creatorType !== 'customer' && existsRoomTypes) {
                $.each(roomTypes, function(index, item) {
                    const isSelected = (type == 2 && index == data.room.room_type) ? 'selected' : '';
                    roomType.append(`<option value="${index}" ${isSelected}>${item}</option>`);
                });
            }

            $.each(arrJackpotType, function(index, item) {
                const isSelected = (type == 2 && item.id == data.room.jackpot_type) ? 'selected' : '';
                jackpotType.append(`<option value="${item.id}" ${isSelected}>${item.description}</option>`);
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
                $('#pct-serials').val(room.pct_serials);
                $('#num-serials').val(room.num_serials);
                $('#serials-commn').val(room.serials_commn);

                if (room.jackpot_type > 1) {
                    changeFixedJackpot(2);
                    $('#fixed-jackpot-value').val(room.fixed_jackpot_value);
                }
            }

            $(`#modal-${name}`).modal('show');
        }

        $(document).on("click", "#btn-confirm", function() {
            submitForm(name);
        });

        const otherCasesMap = {
            4: function(encryptedId) {
                showToastOptionBtns(
                    'Se actualizará el Status de esta configuración',
                    'info',
                    'Sí, actualizar',
                    '#form-other-options',
                    `{{ route('bingo.automatic-room.change-status', ['id' => ':id']) }}`,
                    encryptedId
                );
            },
            7: function(encryptedId) {
                showToastOptionBtns(
                    'Deseas copiar esta configuración',
                    'question',
                    'Sí, copiar',
                    '#form-other-options',
                    `{{ route('bingo.automatic-room.copy', ['id' => ':id']) }}`,
                    encryptedId
                );
            },
        };
    </script>
    @vite(['resources/js/games/bingo/ctn-price-coins.js'])
@endsection
