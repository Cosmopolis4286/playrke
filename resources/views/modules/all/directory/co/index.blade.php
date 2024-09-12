@extends('layouts.app_rke')
@section('title', $title)
@section('content')
    <div class="col-md-8 m-auto">
        <div class="card">
            @include('components.card-header', ['title' => $title])
            <div class="card-body">
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
                                    <td>{{ $pix->bank }}</td>
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
    <script>
        const name = '<?php echo $name; ?>';
        const paymentType = '<?php echo $paymentType; ?>';
        const arrPixKeys = JSON.parse('<?php echo json_encode($pixKeys); ?>');

        $(document).on("click", `.btn-${name}`, function() {
            formCRUD($(this), name, 'Pix', '{{ asset('') }}pix')
        });

        function insertInfo(type, data = '') {
            const pixKey = $("#pix-key");
            const listBanks = $("#bank");

            $("#pix-key").empty();

            if (type == 1) pixKey.append(`<option value="" selected>Seleccionar:</option>`);

            /// Tipo Documento
            $.each(arrPixKeys, function(index, item) {
                const isSelected = (type == 2 && item.id == data.pix_key) ? 'selected' : '';
                pixKey.append(`<option value="${item.id}" ${isSelected}>${item.value}</option>`);
            });


            if (type == 2) $("#pix-key-value").val(data.pix_key_value) && $("#bank").val(data.bank);
            $(`#modal-${name}`).modal('show');
        }

        $(document).on("click", "#btn-confirm", function() {
            submitForm(name, ['pix-key', 'pix-key-value']);
        });

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
