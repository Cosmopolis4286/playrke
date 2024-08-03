@extends('layouts.app_rke')
@section('title', $title)
@section('content')
    <div class="col-md-8 m-auto">
        <div class="card">
            @include('components.card-header', ['title' => $title])
            <div class="card-body">
                @component('components.directory-tabs', [
                    'countries' => $countries,
                    'countryCode' => $countryCode,
                ])
                @endcomponent
                <button type="button" class="btn-{{ $name }} btn btn-sm btn-success" data-type="1">
                    <i class="fas fa-plus-square mr-1"></i>{{ $paymentType }}</button>
                <div class="table-responsive mt-3">
                    <table class="table table-striped table-bordered my-2 w-100">
                        <thead>
                            <tr>
                                <th>Banco</th>
                                <th>Tipo</th>
                                <th>Chave</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pixs as $pix)
                                <tr>
                                    <td>{{ $pix->Bank->name }}</td>
                                    <td>{{ $pix->ChavePix->value }}</td>
                                    <td>{{ $pix->pix_key_value }}</td>
                                    <td>
                                        @component('components.btn-group', [
                                            'name' => $name,
                                            'dataId' => encrypt($pix->id),
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
        'dataForm' => view('modules.all.directory.br.form')->render(),
    ])
    @endcomponent
    @include('components.form-other-options')
@endsection
@section('scripts')
    <script src="{{ asset('js/select2.min.js') }}"></script>
    <script>
        const name = '<?php echo $name; ?>';
        const paymentType = '<?php echo $paymentType; ?>';
        const bank = $("#bank");
        const arrPixKeys = JSON.parse('<?php echo json_encode($pixKeys); ?>');
        let banks = [];

        $("#phone").mask("00000000000");

        $(document).on("click", `.btn-${name}`, function() {
            formCRUD($(this), name, 'Pix', '{{ asset('') }}pix')
        });

        $(document).ready(function() {
            $('#bank').select2({
                theme: 'bootstrap4',
                placeholder: "Selecciona una opción",
                allowClear: true
            });
        });

        $(document).on("change", "#bank", function() {
            let bankInfo = {};
            let selectedBank = Number($(this).val());

            $.each(banks, function(index, item) {
                if (selectedBank == item.code) {
                    bankInfo = {
                        ispb: item.ispb,
                        name: item.name,
                        code: item.code,
                        full_name: item.fullName
                    };
                }
            });

            // Asignar la cadena JSON al valor del input
            $("#arr-bank").val(JSON.stringify(bankInfo));
        });

        function insertBanks(type, data = '') {
            if (type == 1) bank.append(`<option value="" selected>Seleccionar:</option>`);

            $.each(banks, function(index, item) {
                const isSelected = (type == 2 && item.code == data.bank) ? 'selected' : '';
                bank.append(
                    `<option value="${item.code}" ${isSelected}>${item.name}</option>`);
            });

            $(`#modal-${name}`).modal('show');
        }

        function getBanks(type, data = '') {
            if (banks.length) {
                insertBanks(type, data);
            } else {
                const url = '{{ asset('') }}pix/get/banks';
                $.getJSON({
                    url: url,
                    data: {
                        '_token': $("#token").val()
                    },
                    success: function(res) {
                        banks = res;
                        insertBanks(type, data);
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        // Manejar errores aquí si es necesario
                    }
                });
            }
        }

        function insertInfo(type, data = '') {
            const pixKey = $("#pix-key");

            $("#pix-key, #bank").empty();

            if (type == 1) pixKey.append(`<option value="" selected>Seleccionar:</option>`);

            /// Tipo Documento
            $.each(arrPixKeys, function(index, item) {
                const isSelected = (type == 2 && item.id == data.pix_key) ? 'selected' : '';
                pixKey.append(`<option value="${item.id}" ${isSelected}>${item.value}</option>`);
            });

            if (type == 2) $("#pix-key-value").val(data.pix_key_value);

            getBanks(type, data);
        }

        submitForm(name, ['pix-key', 'pix-key-value']);

        function deleteInfo() {
            showToastOptionBtns(
                `Este ${paymentType} será eliminado definitivamente`,
                'info',
                'Sí, eliminar',
                '#form-other-options',
                `{{ route('pix.delete', ['id' => ':id']) }}`,
                encryptedId
            );
        }
    </script>
@endsection
