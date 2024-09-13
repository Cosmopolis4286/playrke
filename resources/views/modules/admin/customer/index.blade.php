@extends('layouts.app_rke')
@section('title', $title)
@section('content')
    <div class="col-12">
        <div class="card">
            @include('components.card-header', ['title' => $title])
            <div class="card-body">
                <button type="button" class="btn-{{ $name }} btn btn-sm btn-success" data-type="1">
                    <i class="fas fa-plus-square mr-1"></i>Añadir</button>
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
                            @foreach ($customers as $item)
                                <tr>
                                    <td>{{ $item->name }}</td>
                                    <td>{{ $item->Country->name }}</td>
                                    <td>{{ $item->phone }}</td>
                                    <td>
                                        {{ $item->User?->email ?? 'Sin Email para Login' }}
                                    </td>
                                    <td>{{ $item->status == 1 ? 'Activo' : 'Inactivo' }}</td>
                                    <td>
                                        @component('components.btn-group', [
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
                                                (object) [
                                                    'dataType' => '5',
                                                    'color' => 'bg-navy',
                                                    'icon' => 'fa-solid fa-lock',
                                                ],
                                            ],
                                        ])
                                        @endcomponent
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @component('components.modal-form', [
        'name' => $name,
        'size' => 'md',
        'dataForm' => view('modules.admin.customer.form')->render(),
    ])
    @endcomponent
    @component('components.modal-copy-info', [
        'name' => 'copy-passw',
        'title' => 'Contraseña Cliente',
    ])
    @endcomponent
    @include('components.form-other-options')
@endsection
@section('scripts')
    <script src="{{ asset('js/select2.min.js') }}"></script>
    <script>
        if (@json(Session::has('message-passw'))) {
            const newPassw = {!! json_encode(Session::get('message-passw')) !!};
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

        const name = '<?php echo $name; ?>';
        const arrCountries = JSON.parse('<?php echo $countries; ?>');
        const coins = JSON.parse('<?php echo $coins; ?>');
        const country = $("#country");
        const coin = $("#coin");
        const coinText = $("#coin-text");
        const otherCoins = $("#other-coins");
        let update = false;

        if (@json(Session::has('msg-error'))) {
            const error = {!! json_encode(Session::get('msg-error')) !!};

            // Asignar elem basado en el valor de error
            let elem = error == 1 ? $(`#btn-${name}-insert`) :
                (error == 2 ? [2, {!! json_encode(Session::get('encryptedId')) !!}] : null);

            formCRUD(elem, name, 'Cliente', '{{ asset('') }}customer');
        }

        $(document).on("click", `.btn-${name}`, function() {
            formCRUD($(this), name, 'Cliente', '{{ asset('') }}customer', otherCasesMap)
        });

        country.on("change", function() {
            const selectedCountry = country.val(); // Obtener el valor seleccionado del país

            if (selectedCountry) {
                $.each(coins, function(index, item) {
                    if (item.country == selectedCountry) {
                        coinText.val(`(${item.symbol}) ${item.short_name}`);
                        coin.val(item.id);
                    }
                });
                if (coins.length > 1 && !update) insertOtherCoins();
            } else {
                coinText.val('');
                coin.val('');
                $("#cont-other-coins").addClass('hide-elem');
            }
        });

        function insertOtherCoins(selectedCoins = '') {
            otherCoins.empty();
            $.each(coins, function(index, item) {
                if (item.id != coin.val()) {
                    const isSelected = selectedCoins.includes(item.id) ? 'selected' : '';
                    otherCoins.append(
                        `<option value="${item.id}" ${isSelected}>(${item.symbol}) ${item.long_name}</option>`
                    );
                }
            });
            $("#cont-other-coins").removeClass('hide-elem');
        }

        function insertInfo(type, data = '') {
            update = false;
            $('#username, #email').attr('readonly', false);

            if (type == 1) country.append(`<option value="" selected>Seleccionar:</option>`);

            $.each(arrCountries, function(index, item) {
                const isSelected = (type == 2 && item.id == data.customer.country) ? 'selected' : '';
                country.append(
                    `<option value="${item.id}" ${isSelected}>${item.name}</option>`);
            });

            if (type == 2) {
                let customer = data.customer;
                let otherCoins = data.other_coins;
                let user = data.user;

                $('#name').val(customer.name);
                $('#phone').val(customer.phone);
                update = true;
                country.trigger('change');
                insertOtherCoins(otherCoins)

                $("#username").val(user.username);
                $("#email").val(user.email);
                $('#username, #email').attr('readonly', true);
            }

            $(`#modal-${name}`).modal('show');
        }

        $(document).on("click", "#btn-confirm", function() {
            submitForm(name, ['name', 'country', 'coin', 'phone', 'username', 'email']);
        });

        function deleteInfo(encryptedId) {
            showToastOptionBtns(
                'Este Cliente será eliminado definitivamente',
                'info',
                'Sí, eliminar',
                '#form-other-options',
                `{{ route('customer.delete', ['id' => ':id']) }}`,
                encryptedId
            );
        }

        const otherCasesMap = {
            4: function() {
                showToastOptionBtns(
                    'Deseas cambiar el status de este Cliente',
                    'question',
                    'Sí, cambiar',
                    '#form-other-options',
                    `{{ route('customer.change-status', ['id' => ':id']) }}`,
                    encryptedId
                );
            },
            5: function() {
                showToastOptionBtns(
                    'Generar Nueva Contraseña a este Cliente',
                    'info',
                    'Sí, generar',
                    '#form-other-options',
                    `{{ route('customer.change-pasww', ['id' => ':id']) }}`,
                    encryptedId
                );
            },
        };
    </script>
@endsection
