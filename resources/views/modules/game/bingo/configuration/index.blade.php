@extends($layout)
@section('title', $title)
@section('content')
    <div class="col-12 @if (isset($userToken)) p-2 @endif">
        <div class="card">
            @include('includes.card-header')
            <div class="card-body">
                <form id="form-bingo-rules" action="{{ route('bingo.handleBingoRules', $rulesID) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="form-group">
                        <label for="bingo-rules">Reglamento:</label>
                        <button type="button" class="ml-1 btn btn-sm btn-secondary" data-toggle="modal"
                            data-target="#modal-bingo-rules">
                            <i class="fas fa-eye"></i>
                        </button>
                        <textarea class="form-control" id="bingo-rules" name="rules" rows="14">{{ $gameRules }}</textarea>
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
        @include('includes.game.bingo.layout.modal-bingo-rules')
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
