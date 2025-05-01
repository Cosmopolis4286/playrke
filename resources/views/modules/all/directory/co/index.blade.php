@extends('layouts.app_rke')
@section('title', $title)
@section('content')
    <div class="col-md-9 m-auto">
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
                                <th>Método</th>
                                <th>#Número</th>
                                <th>Usuario</th>
                                <th>Núm.Doc</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($paymentMethods as $item)
                                <tr>
                                    <td>{{ $item->gateway }}</td>
                                    <td>{{ $item->acc_number }}</td>
                                    <td>{{ $item->user_name }}</td>
                                    <td>{{ $item->document_number }}</td>
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
        'dataForm' => view('modules.all.directory.co.form')->render(),
    ])
    @include('includes.form-other-options')
@endsection
@section('scripts')
    <script src="{{ asset('js/select2.min.js') }}"></script>
    <script>
        const name = '{{ $name }}';
        const arrPaymentMethod = @json($arrPaymentMethod);

        $(document).on("click", `.btn-${name}`, function() {
            formCRUD($(this), name, 'Datos Bancarios')
        });

        $(document).ready(function() {
            $("#acc-number").mask("000000000000");
            $("#document-number").mask("000000000000");
        });

        function insertInfo(type, data = '') {
            $("#acc-number, #user-name, #document-number").empty();

            if (type == 1) $('#gateway').append(`<option value=""  disabled selected>Seleccionar:</option>`);

            // Pasarela de Pago
            $.each(arrPaymentMethod, function(index, item) {
                const isSelected = (type == 2 && index == data.gateway) ? 'selected' : '';
                $('#gateway').append(`<option value="${index}" ${isSelected}>${item}</option>`);
            });

            if (type == 2) {
                $('#acc-number').val(data.acc_number);
                $('#user-name').val(data.user_name);
                $('#document-number').val(data.document_number);
            };

            $(`#modal-${name}`).modal('show');
        }

        $(document).on("click", "#btn-confirm", function() {
            submitForm(name, ['gateway', 'acc-number', 'user-name', 'document-number']);
        });
    </script>
@endsection
