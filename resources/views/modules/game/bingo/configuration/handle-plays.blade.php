@extends($layout)
@section('styles')
    @vite('resources/css/games/bingo/bingo-handle-plays.css')
@endsection
@section('title', $title)
@section('content')
    <div class="col-12 @if (isset($userToken)) p-2 @endif">
        <div class="card">
            @include('includes.card-header', ['subtitle' => 'Jugadas'])
            <div class="card-body">
                <button type="button" class="btn btn-sm btn-success" data-toggle="modal" data-target="#modal-bingo-plays">
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
                            @foreach ($bingoPlays as $item)
                                <tr>
                                    <td>{{ $item->name }}</td>
                                    <td>{{ $item->boxes }}</td>
                                    <td>{{ $item->status == 0 ? 'Inactivo' : 'Activo' }}</td>
                                    <td class="align-middle">
                                        <div class="btn-group" role="group">
                                            <button title="Editar" class="btn-{{ $name }} btn btn-sm btn-primary"
                                                data-type="2" data-id="{{ encrypt($item->id) }}">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button class="btn-{{ $name }} btn btn-sm btn-dark" data-type="4"
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
        @include('includes.games.bingo.layout.modal-bingo-plays')
        @include('includes.form-other-options')
    </div>
@endsection
@section('scripts')
    <script>
        const name = @json($name);
        const form = $(`#form-${name}`);
        let namePlay = $('#name-play');
        let bingoBoxes = [];
        let playId = false;

        function checkBoxes(boxes) {
            $('.ctn-plays').removeClass("number-box-circle");
            $('.ctn-plays').each(function() {
                if (boxes.includes($(this).text().trim())) {
                    $(this).addClass("number-box-circle");
                }
            });
        }

        function insertInfo(type, data = '') {

            if (type == 2) {
                namePlay.val(data.name);
                checkBoxes(data.boxes);
            }

            $(`#modal-${name}`).modal('show');
        }

        $(document).on("click", `.btn-${name}`, function() {
            formCRUD($(this), name, 'Jugada de Bingo', `{{ asset('') }}${name}`, otherCasesMap)
        });

        const otherCasesMap = {
            4: function(encryptedId) {
                showToastOptionBtns(
                    'Deseas cambiar el status de este registro',
                    'question',
                    'Sí, cambiar',
                    '#form-other-options',
                    route(`${name}.handler`, {
                        id: encryptedId,
                        action: 'status'
                    })
                );
            }
        };

        const bingoOrder = ['B', 'I', 'N', 'G', 'O'];

        $(document).on("click", ".ctn-plays", function() {
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

        function cleanBoxes() {
            $('.ctn-plays').removeClass('number-box-circle');
            bingoBoxes = [];
            namePlay.val('');
        }

        $(document).on("click", "#btn-clean", function() {
            cleanBoxes();
        });

        $(document).on("click", "#btn-confirm", function() {

            if (!namePlay.val()) {
                showToast('Es obligatorio un nombre de jugada', 'info');
                return;
            }

            if (bingoBoxes.length < 3) {
                showToast('Es necesario un minimo de 4 casillas', 'info');
                return;
            }

            $('#bingo-boxes').val(bingoBoxes.join(','));

            form.submit();
        });
    </script>
@endsection
