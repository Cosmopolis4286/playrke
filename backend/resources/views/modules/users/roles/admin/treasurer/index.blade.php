@extends('layouts.auth-layout')
@section('title', $title)
@section('content')
    <div class="col-12">
        <div class="card">
            @include('includes.card-header')
            <div class="card-body">
                @include('includes.treasurer.tabs')
                <div class="tab-content">
                    <div class="tab-pane fade show active">
                        <button type="button" class="btn-{{ $moduleId }} btn btn-sm btn-success" data-action="create">
                            <i class="fas fa-plus-square mr-1"></i>Añadir
                        </button>
                        <div class="table-responsive mt-3">
                            <table class="table table-striped table-bordered my-2 w-100">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Pais</th>
                                        <th>Telefono</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                        <th>Accion</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($treasurers as $item)
                                        <tr>
                                            <td>{{ $item->name }}</td>
                                            <td>{{ $item->Country->name }}</td>
                                            <td>{{ $item->phone }}</td>
                                            <td>{{ $item->User->email }}</td>
                                            <td>{{ $item->status == 1 ? 'Activo' : 'Inactivo' }}</td>
                                            <td>
                                                @include('includes.btn-group', [
                                                    'moduleId' => $moduleId,
                                                    'dataId' => encrypt($item->id),
                                                    'buttons' => [
                                                        (object) [
                                                            'action' => 'edit',
                                                            'color' => 'btn-primary',
                                                            'icon' => 'fas fa-edit',
                                                        ],
                                                        (object) [
                                                            'action' => 'delete',
                                                            'color' => 'btn-danger',
                                                            'icon' => 'fa-solid fa-trash-can',
                                                        ],
                                                        (object) [
                                                            'action' => '4',
                                                            'color' => 'btn-secondary',
                                                            'icon' => 'fa-solid fa-repeat',
                                                        ],
                                                        (object) [
                                                            'action' => '5',
                                                            'color' => 'bg-navy',
                                                            'icon' => 'fa-solid fa-lock',
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
        'moduleId' => $moduleId,
        'size' => 'md',
        'dataForm' => view('modules.users.roles.admin.treasurer.form')->render(),
    ])
    @include('includes.modal-copy-info', [
        'moduleId' => 'copy-passw',
        'title' => 'Contraseña Tesorero',
    ])
@endsection
@section('scripts')
    <script src="{{ asset('js/select2.min.js') }}"></script>
    <script>
        if (@json(Session::has('pawss'))) {
            const newPassw = {!! json_encode(Session::get('pawss')) !!};
            $("#info-to-copy").val(newPassw);
            setTimeout(function() {
                $("#modal-copy-passw").modal('show');
            }, 3000);
        }

        $(document).on("click", "#btn-copy", function() {
            copyInfo('Contraseña Copiada');
        });

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

        $("#phone").mask("00000000000");

        const name = '<?php echo $moduleId; ?>';
        const arrCountries = JSON.parse('<?php echo $countries; ?>');
        const arrTimeZones = JSON.parse('<?php echo $timeZones; ?>');
        const currencies = JSON.parse('<?php echo $currencies; ?>');
        const userRole = $("#select-user-role");
        const country = $("#country");
        const timeZone = $("#time-zone");
        const currency = $("#currency");
        const currencyText = $("#currency-text");
        const otherCurrencies = $("#other-currencies");
        const games = $("#games");
        const arrGames = @json($games);
        let update = false;

        if (@json(Session::has('msg-error'))) {
            const error = {!! json_encode(Session::get('msg-error')) !!};

            // Asignar elem basado en el valor de error
            let elem = error == 1 ? $(`#btn-${name}-insert`) :
                (error == 2 ? [2, {!! json_encode(Session::get('encryptedId')) !!}] : null);

            formCRUD(elem, name, 'Tesorero');
        }

        $(document).on("click", `.btn-${name}`, function() {
            formCRUD($(this), name, 'Tesorero', otherCasesMap)
        });

        country.on("change", function() {
            const selectedCountry = country.val(); // Obtener el valor seleccionado del país

            if (selectedCountry) {
                $.each(currencies, function(index, item) {
                    if (item.country == selectedCountry) {
                        currencyText.val(`(${item.symbol}) ${item.name}`);
                        currency.val(item.id);
                    }
                });
                if (currencies.length > 1 && !update) insertOtherCurrencies();
            } else {
                currencyText.val('');
                currency.val('');
                $("#cont-other-currencies").addClass('hide-elem');
            }
        });

        function insertOtherCurrencies(selectedCurrencies = []) {
            otherCurrencies.empty();
            $.each(currencies, function(index, item) {
                if (item.id != currency.val()) {
                    const isSelected = selectedCurrencies.includes(item.id) ? 'selected' : '';
                    otherCurrencies.append(
                        `<option value="${item.id}" ${isSelected}>(${item.symbol}) ${item.description}</option>`
                    );
                }
            });
            $("#cont-other-currencies").removeClass('hide-elem');
        }

        function insertGames(selectedGames = '') {
            games.empty();
            $.each(arrGames, function(index, item) {
                const isSelected = selectedGames.includes(item.id) ? 'selected' : '';
                games.append(`<option value="${item.id}" ${isSelected}>${item.name}</option>`);
            });
        }

        function insertInfo(type, data = '') {
            update = false;
            $('#username, #email').attr('readonly', false);

            if (type == 1) {
                country.html(`<option value="" disabled selected>Seleccionar País:</option>`);
                timeZone.html(`<option value="" disabled selected>Seleccionar Zona:</option>`);
                userRole.html(`
                    <option value="" disabled selected>Seleccione un rol:</option>
                    <option value="admin">Administradores</option>
                    <option value="commerce">Comercios</option>
                `);
                $('#cont-select-user-to-promote').removeClass('hide-elem');
                $('#cont-select-user-to-promote').addClass('hide-elem');
            } else if (type == 2) {
                $('#cont-select-user-role, #cont-select-user-to-promote').addClass('hide-elem');
            }

            $.each(arrCountries, function(index, item) {
                const isSelected = (type == 2 && item.id == data.treasurer.country) ? 'selected' : '';
                country.append(
                    `<option value="${item.id}" ${isSelected}>${item.name}</option>`);
            });

            $.each(arrTimeZones, function(index, item) {
                const isSelected = (type == 2 && item.id == data.user.time_zone) ? 'selected' : '';
                timeZone.append(
                    `<option value="${item.id}" ${isSelected}>${item.name}</option>`);
            });

            if (type == 2) {
                const treasurer = data.treasurer;
                const otherCurrencies = data.other_currencies;
                const user = data.user;

                $('#name').val(treasurer.name);
                $('#phone').val(treasurer.phone);
                update = true;
                country.trigger('change');
                insertOtherCurrencies(otherCurrencies)

                $("#username").val(user.username);
                $("#email").val(user.email);
                $('#username, #email').attr('readonly', true);
            }

            insertGames();

            $(`#modal-${name}`).modal('show');
        }

        $(document).on("click", "#btn-confirm", function() {
            if (!$('#select-user-to-promote').val()) {
                submitForm(name, ['moduleId', 'country', 'time-zone', 'currency', 'phone', 'username', 'email']);
            } else {
                submitForm(name, ['select-user-role', 'select-user-to-promote', 'other-currencies'])
            }
        });

        function insertUsers(data, label) {
            $('#label-user-promote').text(label);

            $('#select-user-to-promote').html(`<option value="" disabled selected>Seleccionar usuario:</option>`)
                .prop('disabled', false);

            $.each(data, function(index, item) {
                $('#select-user-to-promote').append(`<option value="${item.user}">${item.name}</option>`);
            });
            $('.new-treasurer').addClass('hide-elem');
            $('#label-other-currencies').text('Monedas Permitidas:');
            insertOtherCurrencies();
            $('#cont-select-user-to-promote, #cont-other-currencies').removeClass('hide-elem');
        }

        function getUserInfo(userRole, text) {
            $.getJSON({
                url: route('admin.getUserInfo', {
                    userRole,
                    exclude: 'OtherTreasurers'
                }),
                success: function(data) {
                    if (data.length) {
                        insertUsers(data, text);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Failed to fetch exchange rate:', textStatus, errorThrown);
                }
            });
        }

        function hideSelectUserRole() {
            $('#label-user-promote').text('');
            $('#select-user-to-promote').empty().prop('disabled', true);
            $('#cont-select-user-to-promote, #cont-other-currencies').addClass('hide-elem');
            $('#label-other-currencies').text('Otras Monedas:');
            $('.new-treasurer').removeClass('hide-elem');
        }

        $('#checkbox-promote-user').on('change', function() {
            if ($(this).is(':checked')) {
                userRole.prop('disabled', false);
            } else {
                userRole.prop('disabled', true).val('');
                hideSelectUserRole();
            }
        });

        userRole.on("change", function() {
            const value = $(this).val();
            const text = $(this).find('option:selected').text();

            if (value) {
                getUserInfo(value, `${text}: *`);
            } else {
                hideSelectUserRole();
            }
        });
        const otherCasesMap = {
            4: function(encryptedId) {
                showToastConfirmAction(
                    'Deseas cambiar el status de este tesorero',
                    'question',
                    'Sí, cambiar',
                    route(`${name}.changeStatus`, {
                        id: encryptedId
                    })
                );
            },
            5: function(encryptedId) {
                showToastConfirmAction(
                    'Generar nueva contraseña a este tesorero',
                    'info',
                    'Sí, generar',
                    route(`${name}.changePassw`, {
                        id: encryptedId
                    })
                );
            },
        };
    </script>
@endsection
