@extends($layout)
@section('title', $title)
@section('content')
    <div class="col-12 @if (isset($userToken)) p-2 @endif">
        <div class="card">
            @include('includes.card-header')
            <div class="card-body">
                <button type="button" class="mr-2 btn btn-sm btn-success" data-toggle="modal" data-target="#modal-polla-rules">
                    <i class="fas fa-eye"></i> Ver Reglamento
                </button>
                <a href="#" class="mr-2 btn btn-sm btn-primary">Tipo de Juego</a>
                <a href="#" class="btn btn-sm btn-primary">Loterias</a>
                <hr class="mt-2">
                <form id="form-polla-rules" action="{{ route('polla.handlePollaRules', $rulesID) }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf @method('PUT')
                    <div class="d-flex justify-content-between">
                        <div class="form-group">
                            <label for="image">Imagen</label>
                            <input type="file" id="image" class="form-control-file" name="image"
                                accept="image/png, image/jpeg">
                        </div>
                        <div class="mr-2">
                            <img style="max-height: 68px" src="{{ asset("storage/game/polla/$imagePollaRules") }}"
                                alt="Game Image">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="polla-rules">Reglamento:</label>
                        <textarea class="form-control" id="polla-rules" name="rules" rows="10">{{ $gameRules }}</textarea>
                    </div>
                    <div class="d-flex justify-content-center">
                        <button type="button" id="btn-form-polla-rules" class="btn btn-primary">
                            <i class="fa-solid fa-floppy-disk"></i> Guardar
                            <span id="loader-btn-ctn" class="spinner-border spinner-border-sm" role="status"
                                aria-hidden="true" style="display: none"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @include('includes.game.polla.layout.modal-polla-rules')
    </div>
@endsection
@section('scripts')
    <script>
        $(document).on("click", "#btn-form-polla-rules", function() {
            if ($('#polla-rules').val()) {
                $('#loader-btn-ctn').show();
                $('#form-polla-rules').submit();
            } else {
                showToast('La información es obligatoria', 'info');
            }
        });
    </script>
@endsection
