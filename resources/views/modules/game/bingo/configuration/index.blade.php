@extends($layout)
@section('title', $title)
@section('content')
    <div class="col-12 @if (isset($userToken)) p-2 @endif">
        <div class="card">
            @include('includes.card-header')
            <div class="card-body">
                <button type="button" class="btn btn-sm btn-success mr-2" data-toggle="modal" data-target="#modal-bingo-rules">
                    <i class="fas fa-eye"></i> Ver Reglamento
                </button>
                <a href="{{ route('bingo.bingoPlays', $userToken) }}" class="btn btn-sm btn-primary">
                    <i class="fa-solid fa-gamepad"></i> Ver Jugadas
                </a>
                <hr class="mt-2">
                <form id="form-bingo-rules" action="{{ route('bingo.handleBingoRules', $rulesID) }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf @method('PUT')
                    <div class="d-flex justify-content-between">
                        <div class="form-group">
                            <label for="image">Imagen</label>
                            <input type="file" id="image" class="form-control-file" name="image"
                                accept="image/png, image/jpeg">
                        </div>
                        <div class="mr-2">
                            <img style="max-height: 68px" src="{{ asset("storage/game/bingo/$imageBingoRules") }}"
                                alt="Game Image">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="bingo-rules">Reglamento:</label>
                        <textarea class="form-control" id="bingo-rules" name="rules" rows="10">
                            {{ $gameRules ?? view('includes.info.game.bingo')->render() }}
                        </textarea>
                    </div>
                    <div class="d-flex justify-content-center">
                        <button type="button" id="btn-form-bingo-rules" class="btn btn-primary">
                            <i class="fa-solid fa-floppy-disk"></i> Guardar
                            <span id="loader-btn-ctn" class="spinner-border spinner-border-sm" role="status"
                                aria-hidden="true" style="display: none"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @include('includes.games.bingo.layout.modal-bingo-rules')
    </div>
@endsection
@section('scripts')
    <script>
        $(document).on("click", "#btn-form-bingo-rules", function() {
            if ($('#bingo-rules').val()) {
                $('#loader-btn-ctn').show();
                $('#form-bingo-rules').submit();
            } else {
                showToast('La información es obligatoria', 'info');
            }
        });
    </script>
@endsection
