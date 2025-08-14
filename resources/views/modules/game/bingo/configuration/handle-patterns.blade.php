@extends($layout)
@section('styles')
    @vite('resources/css/games/bingo/bingo-handle-patterns.css')
@endsection
@section('title', $title)
@section('content')
    <div class="col-12 @if (isset($userToken)) p-2 @endif">
        <div class="card">
            @include('includes.card-header', ['subtitle' => 'Patrones/Cantos'])
            <div class="card-body">
                <button type="button" class="btn btn-sm btn-success btn-{{ $viewId }}" data-type="4">
                    <i class="fas fa-plus-square"></i> Crear
                </button>
                <div class="table-responsive mt-2">
                    <table class="table table-striped table-bordered my-2 w-100">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Casillas</th>
                                <th>Status</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($bingoPatterns as $item)
                                <tr>
                                    <td>{{ $item->name }}</td>
                                    <td>{{ $item->boxes }}</td>
                                    <td>{{ $item->status == 0 ? 'Inactivo' : 'Activo' }}</td>
                                    <td class="align-middle">
                                        <div class="btn-group" role="group">
                                            <button title="Editar" class="btn-{{ $viewId }} btn btn-sm btn-primary"
                                                data-action="edit" data-id="{{ encrypt($item->id) }}">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button class="btn-{{ $viewId }} btn btn-sm btn-dark" data-type="4"
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
        @include('includes.games.bingo.layout.modal-bingo-patterns')
    </div>
@endsection
@section('scripts')
    @vite('resources/js/games/bingo/includes/handle-bingo-patterns.js')
    <script>
        const name = @json($viewId);
        const form = $(`#form-${name}`);
        let namePattern = $('#name-pattern');
        let bingoBoxes = [];
        let playId = false;

        function insertInfo(type, data = '') {
            if (type != 2) return;
            cleanBoxes();
            namePattern.val(data.name);
            checkBoxes(data.boxes);
            $(`#modal-${name}`).modal('show');
        }

        $(document).on("click", `.btn-${name}`, function() {
            formCRUD($(this), name, 'Patron de Bingo', otherCasesMap)
        });

        const otherCasesMap = {
            4: function() {
                cleanBoxes();
                $(`#modal-${name}`).modal('show');
            },
            5: function(encryptedId) {
                showToastConfirmAction(
                    'Deseas cambiar el status de este registro',
                    'question',
                    'Sí, cambiar',
                    route(`${name}.handler`, {
                        id: encryptedId,
                        action: 'status'
                    })
                );
            }
        };

        const bingoOrder = ['B', 'I', 'N', 'G', 'O'];

        $(document).on("click", ".ctn-patterns", function() {
            const box = $(this);
            const value = box.text().trim();

            box.toggleClass("number-box-circle");

            if (box.hasClass("number-box-circle")) {
                bingoBoxes.push(value);
            } else {
                bingoBoxes = bingoBoxes.filter(v => v !== value);
            }

            // Ordenar bingoBoxes según letra (BINGO) y número
            bingoBoxes.sort((a, b) => {
                const [letterA, numberA] = [a.charAt(0), parseInt(a.slice(1))];
                const [letterB, numberB] = [b.charAt(0), parseInt(b.slice(1))];

                const indexA = bingoOrder.indexOf(letterA);
                const indexB = bingoOrder.indexOf(letterB);

                if (indexA === indexB) {
                    return numberA - numberB;
                }

                return indexA - indexB;
            });
        });

        $(document).on("click", "#btn-clean", function() {
            cleanBoxes();
        });

        $(document).on("click", "#btn-confirm", function() {

            if (!namePattern.val()) {
                showToast('Es obligatorio un nombre de jugada', 'info');
                return;
            }

            if (bingoBoxes.length < 3) {
                showToast('Es necesario un minimo de 4 casillas', 'info');
                return;
            }

            $('#bingo-boxes').val(bingoBoxes.join(';'));

            form.submit();
        });
    </script>
@endsection
