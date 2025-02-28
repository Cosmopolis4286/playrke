@extends($layout)
@section('title', $title)
@section('content')
    <div class="col-12 @if (isset($userToken)) p-2 @endif">
        <div class="card">
            @include('includes.card-header')
            <div class="card-body">
                <form id="form-bingo-rules" action="{{ route('bingo.handleBingoRules', $IDRules) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="form-group">
                        <label for="bingo-rules">Reglamento:</label>
                        <textarea class="form-control" id="bingo-rules" name="rules" rows="15"></textarea>
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
