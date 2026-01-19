@extends('layouts.auth-layout')
@section('title', $title)
@section('content')
    <div class="col-12">
        <div class="card">
            @include('includes.card-header')
            <div class="card-body">
                <button type="button" class="btn-{{ $moduleId }} btn btn-sm btn-success" data-action="create"><i
                        class="fas fa-plus-square"></i> Añadir</button>
                <div class="table-responsive mt-2">
                    <table class="table table-striped table-bordered my-2 w-100">
                        <thead>
                            <tr>
                                <th>Codigo</th>
                                <th>Tipo</th>
                                <th>Nombre</th>
                                <th>#Itens</th>
                                <th>Precio</th>
                                <th>Status</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rewards as $item)
                                <tr>
                                    <td>{{ $item->code }}</td>
                                    <td>{{ $item->reward_type_description }}</td>
                                    <td>{{ $item->name }}</td>
                                    <td>{{ $item->num_items }}</td>
                                    <td>{{ $item->price }}</td>
                                    <td>
                                        {{ $item->status == 1 ? 'Activo' : 'Inactivo' }}
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-toggle">
                                            @if ($item->status == 1)
                                                <button class="btn btn-sm btn-primary btn-{{ $moduleId }}"
                                                    data-action="edit" data-id="{{ encrypt($item->id) }}"
                                                    title="Editar Tasa de Cambio">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            @endif
                                            <button class="btn btn-sm btn-secondary btn-{{ $moduleId }}" data-type="4"
                                                data-id="{{ encrypt($item->id) }}" title="Cambiar Status">
                                                <i class="fa-solid fa-repeat"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger btn-{{ $moduleId }}" data-action="delete"
                                                data-id="{{ encrypt($item->id) }}" title="Eliminar">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </div>
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
        'moduleId' => $moduleId,
        'size' => 'md',
        'dataForm' => view('modules.all.rewards.form', ['creator' => $creator])->render(),
    ])
@endsection
@section('scripts')
    <script>
        const name = '{{ $moduleId }}';
        const rewardTypes = @json($rewardTypes);

        $(document).on("click", `.btn-${name}`, function() {
            formCRUD($(this), name, 'Premio', otherCasesMap)
        });

        function insertInfo(type, data = {}) {
            if (type == 1) {
                $('#container-code').addClass('d-none');
                $('#code, #name, #price, #description').val('');
                $('#num-items').val(1);
            } else {
                $('#code').val(data.code || '');
                $('#container-code').removeClass('d-none');
                $('#name').val(data.name);
                $('#num-items').val(data.num_items);
                $('#price').val(data.price);
                $('#description').val(data.description);
            }

            Object.entries(rewardTypes).forEach(([value, text]) => {
                const isSelected = (type == 2 && value === data.product_service) ? 'selected' : '';
                $('#reward-type').append(`<option value="${value}" ${isSelected}>${text}</option>`);
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
            submitForm(name, ['reward-type', 'moduleId', 'num-items', 'price', 'description']);
        });

        const otherCasesMap = {
            4: function(encryptedId) {
                showToastConfirmAction(
                    'Cambiar el status del item',
                    'question',
                    'Sí, cambiar',
                    route(`${name}.changeStatus`, {
                        id: encryptedId
                    })
                );
            }
        };
    </script>
@endsection
