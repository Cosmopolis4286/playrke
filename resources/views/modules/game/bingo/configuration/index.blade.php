@extends($layout)
@section('title', $title)
@section('content')
    <div class="col-12 @if (isset($userToken)) p-2 @endif">
        <div class="card">
            @include('includes.card-header')
            <div class="card-body">
                <form action="{{ route('bingo.handleBingoRules', $IDRules) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="form-group">
                        <label for="bingo-rules">Reglamento:</label>
                        <textarea class="form-control" id="bingo-rules" name="rules" rows="15"></textarea>
                    </div>
                    <div class="d-flex justify-content-center">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-floppy-disk"></i> Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
@endsection
