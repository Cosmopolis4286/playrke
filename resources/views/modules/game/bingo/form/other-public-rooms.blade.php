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
    @include('includes.games.bingo.layout.modal-other-bingo-rooms')
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
            formCRUD($(this), name, 'Nueva Sala', `{{ asset('') }}${name}`, otherCasesMap);
        });

        function insertInfo(type, data = '') {
            if (type == 2) return;

            $('#ctn-price').val(data.config.ctn_price);

            $(`#modal-${name}`).modal('show');
        }

        $(document).on("click", "#btn-confirm", function() {
            submitForm(name, ['ctn-price']);
        });

        function insertInfoPublicRooms(data) {
            const $modal = $('#modal-other-bingo-rooms');
            const $modalBody = $modal.find('.modal-body');

            $modalBody.empty();

            if (!data.length) {
                $modalBody.html(`
            <div class="alert alert-info alert-dismissible text-center mb-n1" role="alert">
                ¡No hay salas disponibles!
            </div>`);
                $modal.modal('show');
                return;
            }

            let html = '<div class="row">';

            data.forEach(elem => {
                html += `
            <div class="input-group mb-3">
                <div class="form-control">${elem.encrypted}</div>
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary btn-participate-bingo" type="button" data-id="${elem.encrypted}">
                        Participar
                    </button>
                </div>
            </div>`;
            });

            html += '</div>';

            $modalBody.html(html);
            $modal.modal('show');
        }

        function getInfoPublicRoom() {
            $.getJSON({
                url: `{{ route('bingo.getInfoPublicRoom') }}`,
                headers: {
                    'X-CSRF-TOKEN': $("#5K3_70K").val()
                },
                success: function(data) {
                    insertInfoPublicRooms(data);
                }
            });
        }

        const otherCasesMap = {
            4: getInfoPublicRoom
        };
    </script>
    @vite(['resources/js/games/bingo/form.js', 'resources/js/games/bingo/ctn-price-coins.js'])
@endsection
