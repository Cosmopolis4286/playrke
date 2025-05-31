@extends('layouts.app_rke')
@section('title', $title)
@section('content')
    <div class="col-12">
        <div class="card">
            @include('includes.card-header')
            <div class="card-body">
                <button type="button" class="btn-{{ $name }} btn btn-sm btn-success" data-type="1"><i
                        class="fas fa-plus-square"></i> Añadir</button>
                <div class="table-responsive mt-2">
                    <table class="table table-striped table-bordered my-2 w-100">
                        <thead>
                            <tr>
                                <th>Codigo</th>
                                <th>#Itens</th>
                                <th>Precio</th>
                                <th>Status</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rewards as $item)
                                <tr></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @include('includes.modal-form', [
        'name' => $name,
        'size' => 'md',
        'dataForm' => view('modules.all.rewards.form', ['creator' => $creator])->render(),
    ])
    @include('includes.form-other-options')
@endsection
@section('scripts')
    <script>
        const name = '{{ $name }}';
        const optionsKit = @json($optionsKit);

        $(document).on("click", `.btn-${name}`, function() {
            formCRUD($(this), name, 'Premio', otherCasesMap)
        });

        function insertInfo(type, data = {}) {
            if (type == 1) {
                $('#container-code').addClass('d-none');
                $('#code').val('');
                $('#num-items').val(1);
                $('#price').val('');
                $('#description').val('');
            } else {
                $('#code').val(data.code || '');
                $('#container-code').removeClass('d-none');
                $('#num-items').val(data.num_items);
                $('#price').val(data.price);
                $('#description').val(data.description);
            }

            Object.entries(optionsKit).forEach(([value, text]) => {
                const isSelected = (type == 2 && value === data.kit) ? 'selected' : '';
                $('#kit').append(`<option value="${value}" ${isSelected}>${text}</option>`);
            });

            // Mostrar modal
            $(`#modal-${name}`).modal('show');
        }

        $('#num-items').on('input change', function() {
            validateInput(this, false);
        });

        $('#price').on('input change', function() {
            validateInput(this, true);
        });

        $(document).on("click", "#btn-confirm", function() {
            submitForm(name, ['kit', 'num-items', 'price']);
        });

        const otherCasesMap = {
            4: function(encryptedId) {
                showToastOptionBtns(
                    'Cambiar el status del item',
                    'question',
                    'Sí, cambiar',
                    '#form-other-options',
                    route(`${name}.change-status`, {
                        id: encryptedId
                    })
                );
            }
        };
    </script>
@endsection
