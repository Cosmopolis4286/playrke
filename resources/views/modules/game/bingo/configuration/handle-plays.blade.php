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
                                            <button class="btn-{{ $name }} btn btn-sm btn-secondary" data-type="8"
                                                data-id="{{ encrypt($item->id) }}" title="Ver Jugada">
                                                <i class="fas fa-eye"></i> Ver
                                            </button>
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
        @include('includes.game.bingo.layout.modal-bingo-plays')
    </div>
@endsection
@section('scripts')
    <script>
        let bingoBoxes = [];

        $(document).on("click", ".ctn-plays", function() {
            const box = $(this);
            const value = box.text().trim();

            box.toggleClass("number-box-circle");

            bingoBoxes = box.hasClass("number-box-circle") ? [...bingoBoxes, value] :
                bingoBoxes.filter(v => v !== value);
        });

        function cleanBoxes() {
            $('.ctn-plays').removeClass('number-box-circle');
            bingoBoxes = [];
            $('#name-play').val('');
        }

        $(document).on("click", "#btn-clean", function() {
            cleanBoxes();
        });

        $(document).on("click", "#btn-confirm", function() {
            if (bingoBoxes.length > 3) {
                $.ajax({
                    url: `{{ route('bingo.handleBingoPlays') }}`,
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('#5K3_70K').val()
                    },
                    data: {
                        bingo_boxes: bingoBoxes,
                        name_play: $('#name-play').val()
                    },
                    dataType: 'json',
                    beforeSend: function() {
                        $('#loader-btn-purchase-ctns').show();
                    },
                    success: function(res) {
                        cleanBoxes();
                        showToast(res.message, res.type);
                    },
                    error: function(error) {
                        console.error(error);
                    },
                    complete: function() {
                        $('#loader-btn-purchase-ctns').hide();
                    }
                });
            } else {
                showToast('Es necesario un minimo de 4 casillas', 'info');
            }
        });
    </script>
@endsection
