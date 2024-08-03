@extends('layouts.app_rke')
<title>Bingo - PlayRKE</title>
    @section('content')
        <div class="row">
            <div class="col-md-12">
                <div class="bg-rke">
                    <div class="mt-n2 card-header" id="headingOne" style="color:#DADADA;">
                        <h1 class="text-center mb-n3">SALAS DE JUEGO</h1>
                    </div>
                    <div class="card-body-tl">
                        <button class="buy_cartn">Comprar carton</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <style>
        body {
            background: url(../img-noise-480x400.png);
        }
    </style>
@endsection
@section('scripts')
    <script>
        $(".buy_cartn").click(function(e) {
            e.stopImmediatePropagation();
            $.ajax({
                url: "{{ route('bingo.store') }}",
                headers: {
                    'X-CSRF-TOKEN': $("#token").val()
                },
                type: 'post',
                dataType: 'json',
                data: {
                    id: 1,
                    n_carton: 3
                },
                beforeSend: function() {},
                success: function(data) {
                    
                },
                error: function() {}
            });
        });
    </script>
@endsection