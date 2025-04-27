@extends($layout)
@section('title', $title)
@section('content')
    <div class="col-12 @if (isset($userToken)) p-2 @endif">
        <div class="card">
            @include('includes.card-header')
            <div class="card-body">
                <button type="button" class="btn-{{ $name }} btn btn-sm btn-success" data-type="1"><i
                        class="fas fa-plus-square"></i> Añadir</button>
                <div class="table-responsive mt-2">
                    <table class="table table-striped table-bordered my-2 w-100">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Horas</th>
                                <th>Status</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lotteries as $item)
                                <tr>
                                    <td>{{ $item->name }} ({{ $item->hour_a }}-{{ $item->hour_b }})</td>
                                    <td>{{ $item->name }}</td>
                                    <td>{{ $item->status == 0 ? 'Inactivo' : 'Activo' }}</td>
                                    <td class="align-middle">
                                        <div class="btn-group" role="group">
                                            <button title="Editar" class="btn-{{ $name }} btn btn-sm btn-primary"
                                                data-type="2" data-id="{{ encrypt($item->id) }}">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button class="btn-{{ $name }} btn btn-sm btn-dark" data-type="8"
                                                data-id="{{ encrypt($item->id) }}" title="Cambiar Status">
                                                <i class="fa-solid fa-rotate"></i>
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
        @include('includes.modal-form', [
            'name' => $name,
            'size' => 'md',
            'dataForm' => view('modules.game.lottery.form')->render(),
        ])
        @include('includes.form-other-options')
    </div>
@endsection
@section('scripts')
    <script src="{{ asset('js/select2.min.js') }}"></script>
    <script>
        const name = @json($name);
        const arrNumbers = @json($numbers);
        const arrSchedules = @json($schedules);
        const schedules = $('#schedules');
        const numbers = $('#numbers');
        let selectedSchedules = [];

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

        $(document).on("click", `.btn-${name}`, function() {
            formCRUD($(this), name, 'Loteria', `{{ asset('') }}${name}`, otherCasesMap)
        });


        function insertSchedules(selectedSchedules) {
            schedules.empty();
            $.each(arrSchedules, function(index, item) {
                const isSelected = selectedSchedules.includes(item) ? 'selected' : '';
                schedules.append(
                    `<option value="${index}" ${isSelected}>${item}</option>`
                );

            });
        }

        function insertInfo(type, data = '') {

            $.each(arrNumbers, function(index, item) {
                const isSelected = (type == 2 && index == data.numbers) ? 'selected' : '';
                numbers.append(
                    `<option value="${index}" ${isSelected}>${item}</option>`
                );

            });

            if (type == 2) {
                $('#name').val(data.name);
                selectedSchedules = data.schedules;
            }

            insertSchedules(selectedSchedules);

            $(`#modal-${name}`).modal('show');
        }

        $(document).on("click", "#btn-confirm", function() {
            submitForm(name, ['name', 'numbers', 'schedules']);
        });

        const otherCasesMap = {
            4: function(encryptedId) {
                showToastOptionBtns(
                    'Se actualizará el estatus de este registro',
                    'info',
                    'Sí, actualizar',
                    '#form-other-options',
                    route(`${name}.change-status`, {
                        id: encryptedId
                    })
                );
            }
        };
    </script>
@endsection
