@extends($layout)
@section('title', $title)
@section('content')
    <div class="col-12 @if (isset($customer)) p-2 @endif">
        <div class="card">
            @include('includes.card-header', ['subtitle' => $subtitle])
            <div class="card-body">
                @include('includes.coin-tabs', ['userToken' => $userToken ?? false])
                @include('includes.games.bingo.report.btn-group', ['view' => 'OTHER-PUBLIC'])
                <div class="tab-content" id="myTabContent">
                    <div class="tab-pane fade show active">
                        @include('includes.games.bingo.report.table-info-other-public-bingo-rooms') </div>
                </div>
            </div>
        </div>
    </div>
    @include('includes.modal-form', [
        'name' => $name,
        'size' => 'md',
        'dataForm' => view('modules.game.bingo.form.body-other-public-rooms', ['creator' => $creator])->render(),
    ])
    @include('includes.form-other-options')
@endsection
@section('scripts')
    <script>
        const name = '{{ $name }}';
        const creation = 'M';
        const routeGetCtnPriceForCoin = @json($routeGetCtnPriceForCoin);
        const creatorType = @json($creator['type'] ?? '');
        const creatorID = @json($creator['id'] ?? '');
        let coinsCtnPrice = false;

        $(document).on("click", `.btn-${name}`, function() {
            if ($(this).data('type') == 1) {
                extraInfo = `{"creatorType": ${creatorType}}`;
            }
            formCRUD($(this), name, 'Nueva Sala', `{{ asset('') }}${name}`);
        });

        function insertInfo(type, data = '') {
            if (type == 1) {
                coinsCtnPrice = false;
                $("#container-ctn-price-config").addClass("hide-elem").data("active", 0);
                $('#container-price-config').html('');
            } else {
                getCtnPriceCoin(data.room.encrypted, true);
            }

            if (type == 2) {
                $('#ctn-price').val(data.config.ctn_price);
            }

            $(`#modal-${name}`).modal('show');
        }

        $(document).on("click", "#btn-confirm", function() {
            submitForm(name, ['ctn-price']);
        });
    </script>
    @vite(['resources/js/games/bingo/form.js', 'resources/js/games/bingo/ctn-price-coins.js'])
@endsection
