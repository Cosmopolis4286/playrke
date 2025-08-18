@extends('layouts.layout-online')
@section('title', $title)
@section('content')
    <div class="col-12">
        <div class="card">
            @include('includes.card-header')
            <div class="card-body">
                <button type="button" class="btn-{{ $viewId }} btn btn-sm btn-success" data-action="create">
                    <i class="fas fa-plus-square mr-1"></i>Añadir</button>
                <div class="table-responsive mt-3">
                    <table class="table table-striped table-bordered my-2 w-100">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Pais</th>
                                <th>Contacto</th>
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
                                    <td>
                                        {{ $item->status == 1 ? 'Activo' : ($item->status == 2 ? 'Inactivo' : 'Eliminado') }}
                                    </td>
                                    <td>
                                        @include('includes.btn-group', [
                                            'viewId' => $viewId,
                                            'dataId' => encrypt($item->id),
                                            'buttons' => array_filter([
                                                $item->status != 3
                                                    ? (object) [
                                                        'action' => 'edit',
                                                        'color' => 'btn-primary',
                                                        'icon' => 'fas fa-edit',
                                                    ]
                                                    : null,
                                                $item->status != 3
                                                    ? (object) [
                                                        'action' => 'delete',
                                                        'color' => 'btn-danger',
                                                        'icon' => 'fa-solid fa-trash-can',
                                                    ]
                                                    : null,
                                                (object) [
                                                    'action' => '4',
                                                    'color' => 'btn-secondary',
                                                    'icon' => 'fa-solid fa-repeat',
                                                ],
                                                $item->status != 3
                                                    ? (object) [
                                                        'action' => '5',
                                                        'color' => 'bg-navy',
                                                        'icon' => 'fa-solid fa-lock',
                                                    ]
                                                    : null,
                                            ]),
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
    @include('includes.modal-form', [
        'viewId' => $viewId,
        'size' => 'md',
        'dataForm' => view('modules.admin.customer.form')->render(),
    ])
    @include('includes.modal-copy-info', [
        'viewId' => 'copy-passw',
        'title' => 'Contraseña Cliente',
    ])
@endsection
@section('scripts')
    @vite('resources/js/helpers/get-uuid.js')
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

        $(document).on("click", "#btn-change-customer-key", function() {
            $('#customer-key').val(generateUUID());
        })

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

        const name = '<?php echo $viewId; ?>';
        const arrCountries = JSON.parse('<?php echo $countries; ?>');
        const arrTimeZones = JSON.parse('<?php echo $timeZones; ?>');
        const country = $("#country");
        const timeZone = $("#time-zone");
        let update = false;

        if (@json(Session::has('msg-error'))) {
            const error = {!! json_encode(Session::get('msg-error')) !!};
            // Asignar elem basado en el valor de error
            let elem = error == 1 ? $(`#btn-${name}-insert`) :
                (error == 2 ? [2, {!! json_encode(Session::get('encryptedId')) !!}] : null);

            formCRUD(elem, name, 'Cliente');
        }

        $(document).on("click", `.btn-${name}`, function() {
            formCRUD($(this), name, 'Cliente', otherCasesMap)
        });

        function insertInfo(type, data = '') {
            update = false;
            $('#username, #email').attr('readonly', false);

            if (type == 1) {
                $('#customer-key').val(generateUUID());
                country.html(`<option value="" disabled selected>Seleccionar País:</option>`);
                timeZone.html(`<option value="" disabled selected>Seleccionar Zona:</option>`);
            }

            $.each(arrCountries, function(index, item) {
                const isSelected = (type == 2 && item.id == data.customer.country) ? 'selected' : '';
                country.append(
                    `<option value="${item.id}" ${isSelected}>${item.name}</option>`);
            });

            $.each(arrTimeZones, function(index, item) {
                const isSelected = (type == 2 && item.id == data.user.time_zone) ? 'selected' : '';
                timeZone.append(
                    `<option value="${item.id}" ${isSelected}>${item.name}</option>`);
            });

            if (type == 2) {
                const customer = data.customer;
                const user = data.user;

                $('#name').val(customer.name);
                $("#email").val(user.email);
                $("#username").val(user.username);
                $("#customer-id").val(customer.customer_id);
                $('#phone').val(customer.phone);

                $('#api-route').val(customer.link_route);
                $('#customer-key').val(customer.customer_key);

                update = true;
                $('#username, #email').attr('readonly', true);
            }

            $(`#modal-${name}`).modal('show');
        }

        $(document).on("click", "#btn-confirm", function() {
            submitForm(name, ['viewId', 'email', 'username', 'customer-id',
                'country', 'time-zone', 'api-route', 'customer-key'
            ]);
        });

        const otherCasesMap = {
            4: function(encryptedId) {
                showToastConfirmAction(
                    'Deseas cambiar el status de este cliente',
                    'question',
                    'Sí, cambiar',
                    route(`${name}.changeStatus`, {
                        id: encryptedId
                    })
                );
            },
            5: function(encryptedId) {
                showToastConfirmAction(
                    'Generar nueva contraseña a este cliente',
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
