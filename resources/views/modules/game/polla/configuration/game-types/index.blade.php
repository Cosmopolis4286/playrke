@extends($layout)
@section('title', $title)
@section('content')
    <div class="col-12 @if (isset($userToken)) p-2 @endif">
        <div class="card">
            @include('includes.card-header', ['subtitle' => 'Tipos de Juego'])
            <div class="card-body">
                <button type="button" class="btn-{{ $name }} btn btn-sm btn-success" data-type="1"><i
                        class="fas fa-plus-square"></i> Añadir</button>
                <div class="table-responsive mt-2">
                    <table class="table table-striped table-bordered my-2 w-100">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Horas</th>
                                <th>Loterias</th>
                                <th>Status</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($gameTypes as $item)
                                <tr>
                                    <td>{{ $item->name }} ({{ $item->hour_a }}-{{ $item->hour_b }})</td>
                                    <td>{{ $item->name }}</td>
                                    <td>{{ $item->name }}</td>
                                    <td>{{ $item->status == 0 ? 'Inactivo' : 'Activo' }}</td>
                                    <td class="align-middle">
                                        <div class="btn-group" role="group">
                                            <button title="Editar" class="btn-{{ $name }} btn btn-sm btn-primary"
                                                data-type="2" data-id="{{ encrypt($item->id) }}">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button class="btn-{{ $name }} btn btn-sm btn-dark" data-type="8"
                                                data-id="{{ encrypt($item->id) }}" title="Cambiar Status">
                                                <i class="fa-solid fa-rotate"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @include('includes.game.polla.layout.modal-polla-rules')
        @include('includes.modal-form', [
            'name' => $name,
            'size' => 'md',
            'dataForm' => view('modules.game.polla.configuration.game-types.form')->render(),
        ])
        @include('includes.form-other-options')
    </div>
@endsection
@section('scripts')
    <script>
        const name = '{{ $name }}';

        $(document).on("click", `.btn-${name}`, function() {
            formCRUD($(this), name, 'Tipo de Juego', `{{ asset('') }}${name}`, otherCasesMap)
        });

        function insertInfo(type, data = '') {
            const isDisabled = type === 2 && data.room.players;
            const isRequired = !isDisabled;

            if (type == 1) {
                arrCoinsTicketPrice = false;
                $("#container-ctn-price-config").addClass("hide-elem").data("active", 0);
                $('#container-price-config').html('');
            } else {
                getTicketPriceCoin(data.room.encrypted, true);
            }

            gameMode.prop({
                disabled: isDisabled,
                required: isRequired
            }).empty();

            jackpotType.empty();
            gameType.empty();

            if (type == 1) {
                const defaultOption = `<option value="" disabled selected>Seleccionar:</option>`;

                if (arrGameModes.length > 1) {
                    gameMode.append(defaultOption);
                }

                jackpotType.append(defaultOption);
                gameType.append(defaultOption);
            }

            $('#room-type').val('PB');

            $.each(arrGameType, function(index, item) {
                const isSelected = (type == 2 && item.id == data.config.jackpot_type) ? 'selected' : '';
                gameType.append(
                    `<option value="${item.id}" ${isSelected}>${item.name} ${item.hour_a} - ${item.hour_b}</option>`
                );
            });

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
                $('#game-type').val(data.room.game_type);
                $('#ticket-price').val(data.config.ticket_price);
                $('#room-commission').val(data.config.room_commn);
            }

            $(`#modal-${name}`).modal('show');
        }

        $(document).on("click", "#btn-confirm", function() {
            submitForm(name, [
                'date', 'game-type', 'room-type', 'jackpot-type',
                'game-mode', 'ticket-price', 'room-commn'
            ]);
        });

        const otherCasesMap = {
            4: function(encryptedId) {
                showToastOptionBtns(
                    'Se actualizará el Status de este registro',
                    'info',
                    'Sí, actualizar',
                    '#form-other-options',
                    route(`${name}.game-types-change-status`, {
                        id: encryptedId
                    })
                );
            }
        };
    </script>
@endsection
