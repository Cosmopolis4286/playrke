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
                                <th>Tipo</th>
                                <th>Data</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($accounts as $item)
                                <tr>
                                    <td>{{ $item->account_type_description }}</td>
                                    <td>{{ $item->data_value }}</td>
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
        'dataForm' => view('modules.all.directory.usdt.form')->render(),
    ])
    @include('includes.form-other-options')
@endsection
@section('scripts')
    <script src="{{ asset('js/select2.min.js') }}"></script>
    <script>
        const name = '{{ $name }}';
        const accountTypes = @json($accountTypes);

        $(document).on("click", `.btn-${name}`, function() {
            formCRUD($(this), name, 'Datos Binance', `{{ asset('') }}${name}`)
        });

        function insertInfo(type, data = '') {
            $("#data-value").empty();

            if (type == 1) $('#account-type').append(`<option value="" disabled selected>Seleccionar:</option>`);

            accountTypes.forEach(function(item) {
                const isSelected = (type == 2 && item.id == data.account_type) ? 'selected' : '';
                $('#account-type').append(`<option value="${item.id}" ${isSelected}>${item.description}</option>`);
            });

            if (type == 2) {
                $('#data-value').val(data.data_value);
            };

            $(`#modal-${name}`).modal('show');
        }

        $(document).on("click", "#btn-confirm", function() {
            submitForm(name, ['account-type', 'data-value']);
        });

        function deleteInfo(encryptedId) {
            showToastOptionBtns(
                `Este registro será eliminado definitivamente`,
                'info',
                'Sí, eliminar',
                '#form-other-options',
                `{{ route('binance.delete', ['id' => ':id']) }}`,
                encryptedId
            );
        }
    </script>
@endsection
