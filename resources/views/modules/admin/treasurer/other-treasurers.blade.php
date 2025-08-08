@extends('layouts.layout-online')
@section('title', $title)
@section('content')
    <div class="col-12">
        <div class="card">
            @include('includes.card-header')
            <div class="card-body">
                @include('includes.treasurer.tabs')
                <div class="tab-content">
                    <div class="tab-pane fade show active">
                        <div class="table-responsive mt-3">
                            <table class="table table-striped table-bordered my-2 w-100">
                                <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Usuario</th>
                                        <th>Pais</th>
                                        <th>Status</th>
                                        <th>Accion</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($otherTreasurers as $item)
                                        <tr>
                                            <td>{{ $item->User->user_type_description }}</td>
                                            <td>{{ $item->User->username }}</td>
                                            <td>{{ $item->User->Country->name }}</td>
                                            <td>{{ $item->status == 1 ? 'Activo' : 'Inactivo' }}</td>
                                            <td>
                                                @include('includes.btn-group', [
                                                    'name' => $name,
                                                    'dataId' => encrypt($item->id),
                                                    'buttons' => [
                                                        (object) [
                                                            'dataType' => '2',
                                                            'color' => 'btn-primary',
                                                            'icon' => 'fas fa-edit',
                                                        ],
                                                        (object) [
                                                            'dataType' => '3',
                                                            'color' => 'btn-danger',
                                                            'icon' => 'fa-solid fa-trash-can',
                                                        ],
                                                        (object) [
                                                            'dataType' => '4',
                                                            'color' => 'btn-secondary',
                                                            'icon' => 'fa-solid fa-repeat',
                                                        ],
                                                    ],
                                                ])
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('includes.modal-form', [
        'name' => $name,
        'size' => 'md',
        'dataForm' => view('modules.admin.treasurer.other-treasurers-form')->render(),
    ])
    @include('includes.modal-copy-info', [
        'name' => 'copy-passw',
        'title' => 'Contraseña Tesorero',
    ])
@endsection
@section('scripts')
    <script src="{{ asset('js/select2.min.js') }}"></script>
    <script>
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

        $(function() {
            $('[data-toggle="popover"]').popover()
        })

        $(document).on('click', '.popover-dismiss', function() {
            $(this).popover('toggle');
        });

        const name = '<?php echo $name; ?>';
        const coins = JSON.parse('<?php echo $coins; ?>');
        const otherCoins = $("#other-coins");
        const games = $("#games");
        const arrGames = @json($games);

        $(document).on("click", `.btn-${name}`, function() {
            formCRUD($(this), name, 'Tesorero', otherCasesMap)
        });

        function insertOtherCoins(selectedCoins = []) {
            otherCoins.empty();

            $.each(coins, function(index, item) {
                const isSelected = selectedCoins.some(val => val == item.id) ? 'selected' : '';
                otherCoins.append(
                    `<option value="${item.id}" ${isSelected}>(${item.symbol}) ${item.long_name}</option>`
                );
            });

            $("#cont-other-coins").removeClass('hide-elem');
        }

        function insertGames(selectedGames = '') {
            games.empty();
            $.each(arrGames, function(index, item) {
                const isSelected = selectedGames.includes(item.id) ? 'selected' : '';
                games.append(`<option value="${item.id}" ${isSelected}>${item.name}</option>`);
            });
        }

        function insertInfo(type, data = '') {
            if (type != 2) return;

            $('#username').val(data.username);
            $('#country').val(data.country);

            insertOtherCoins(data.other_coins);

            $(`#modal-${name}`).modal('show');
        }

        $(document).on("click", "#btn-confirm", function() {
            submitForm(name, ['other-coins', 'allowed-games']);
        });

        const otherCasesMap = {
            4: function(encryptedId) {
                showToastOptionBtns(
                    'Deseas cambiar el status de este tesorero',
                    'question',
                    'Sí, cambiar',
                    route(`${name}.change-status`, {
                        id: encryptedId
                    })
                );
            }
        };
    </script>
@endsection
