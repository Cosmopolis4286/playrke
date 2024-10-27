@extends('layouts.app_rke')
@section('title', $title)
@section('content')
    <div class="col-md-8 m-auto">
        <div class="card">
            @include('includes.card-header')
            <div class="card-body">
                @include('includes.directory-tabs', [
                    'countries' => $countries,
                    'countryCode' => $countryCode,
                ])
                <button type="button" class="btn-{{ $name }} btn btn-sm btn-success" data-type="1">
                    <i class="fas fa-plus-square mr-1"></i>{{ $paymentType }}</button>
                <div class="table-responsive mt-3">
                    <table class="table table-striped table-bordered my-2 w-100">
                        <thead>
                            <tr>
                                <th>Banco</th>
                                <th>CI/RIF</th>
                                <th>Telefono</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pagomovils as $pagoM)
                                <tr>
                                    <td>{{ $pagoM->Bank->name }}</td>
                                    <td>{{ $pagoM->DocumentType->value }}{{ $pagoM->document }}</td>
                                    <td>{{ $pagoM->phone }}</td>
                                    <td>
                                        @include('includes.btn-group', [
                                            'name' => $name,
                                            'dataId' => encrypt($pagoM->id),
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
        'name' => $name,
        'dataForm' => view('modules.all.directory.ve.form')->render(),
    ])
    @include('includes.form-other-options')
@endsection
@section('scripts')
    <script src="{{ asset('js/select2.min.js') }}"></script>
    <script>
        const name = '<?php echo $name; ?>';
        const paymentType = '<?php echo $paymentType; ?>';
        const arrDocumentType = JSON.parse('<?php echo json_encode($documentType); ?>');
        const arrBanks = JSON.parse('<?php echo json_encode($banks); ?>');

        $("#phone").mask("00000000000");

        $(document).on("click", `.btn-${name}`, function() {
            formCRUD($(this), name, 'Pago Movil', '{{ asset('') }}pagomovil')
        });

        $(document).ready(function() {
            $('#bank').select2({
                theme: 'bootstrap4',
                placeholder: "Selecciona una opción",
                allowClear: true
            });
        });

        function insertInfo(type, data = '') {
            const documentType = $("#document-type");
            const listBanks = $("#bank");
            $("#document-type, #bank").empty(); // Limpia las opciones existentes

            if (type == 1) documentType.append(`<option value="" selected>-</option>`);

            /// Tipo Documento
            $.each(arrDocumentType, function(index, IDType) {
                const isSelected = (type == 2 && IDType.id == data.document_type) ? 'selected' : '';
                documentType.append(`<option value="${IDType.id}" ${isSelected}>${IDType.value}</option>`);
            });

            if (type == 1) listBanks.append(`<option value="" selected>Seleccionar:</option>`);
            /// Obtener Bancos
            $.each(arrBanks, function(index, bank) {
                const isSelected = (type == 2 && bank.id == data.bank) ? 'selected' : '';
                listBanks.append(`<option value="${bank.id}" ${isSelected}>${bank.name}</option>`);
            });

            if (type == 2) $("#document-val").val(data.document) && $("#phone").val(data.phone);

            $(`#modal-${name}`).modal('show');
        }

        $(document).on("click", "#btn-confirm", function() {
            submitForm(name, ['document-type', 'document-val', 'bank', 'phone']);
        });

        function deleteInfo() {
            showToastOptionBtns(
                `Este ${paymentType} será eliminado definitivamente`,
                'info',
                'Sí, eliminar',
                '#form-other-options',
                `{{ route('pagomovil.delete', ['id' => ':id']) }}`,
                encryptedId
            );
        }
    </script>
@endsection
