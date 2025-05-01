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
        const name = @json($name);
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
            if (type == 1) {
                coinsCtnPrice = false;
                $("#container-ctn-price-config").addClass("hide-elem").data("active", 0);
                $('#container-price-config').html('');
            } else {
                getCtnPriceCoin(data.room.encrypted, true);
            }
            $('#ctn-price').val(data.config.ctn_price);

            $(`#modal-${name}`).modal('show');
        }

        $(document).on("click", "#btn-confirm", function() {
            submitForm(name, ['ctn-price']);
        });

        $(document).on("click", ".btn-participate-bingo", function() {

        });

        function insertInfoPublicRooms(data) {
            const $modal = $('#modal-other-bingo-rooms');
            const $modalBodyContainer = $modal.find('.modal-body .container');

            $modalBodyContainer.empty();

            if (!data.length) {
                $modalBodyContainer.html(`
            <div class="alert alert-info mb-n1 text-center" role="alert">
                <strong>¡No hay salas disponibles!</strong>
            </div>`);
                $modal.modal('show');
                return;
            }

            let html = '<div class="row">';

            data.forEach(elem => {

                const creator = elem.creator;
                let isUnited = `disabled`;
                let isUnitedIcon = `<i class="fa-solid fa-gamepad"></i>`;

                if (!creator || creator[0].status == 0) {
                    isUnited = `data-type="5" title="Participar"`;
                    isUnitedIcon = `<i class="fa-solid fa-right-to-bracket"></i>`;
                }

                const originalDate = elem.config.date;
                const formattedDate = new Date(originalDate)
                    .toLocaleDateString('es-ES', {
                        day: '2-digit',
                        month: '2-digit',
                        year: '2-digit'
                    });

                html += `
            <div class="input-group">
                <div class="input-group-prepend">
                    <label class="input-group-text">
                        <small><i class="fa-solid fa-calendar-days mr-1 mb-1"></i></small>${formattedDate}
                        <small><i class="fa-solid fa-clock ml-1 mr-1 mb-1"></i></small>${elem.config.time}
                    </label>
                </div>
                <div class="form-control">Bote Actual: <b>${elem.jackpot} <small>USD</small></b></div>
                <div class="input-group-append">
                    <button class="btn btn-outline-success btn-${name}"
                     type="button" data-id="${elem.encrypted}" ${isUnited}>
                        ${isUnitedIcon}
                    </button>
                </div>
            </div>`;
            });

            html += '</div>';

            $modalBodyContainer.html(html);
            $modal.modal('show');
        }

        function getInfoPublicRoom() {
            $.getJSON({
                url: route('bingo.getInfoPublicRoom'),
                headers: {
                    'X-CSRF-TOKEN': $("#5K3_70K").val()
                },
                success: function(data) {
                    insertInfoPublicRooms(data);
                }
            });
        }

        const otherCasesMap = {
            4: getInfoPublicRoom,
            5: (encryptedId) => {
                showToastOptionBtns(
                    'Quieres participar en esta sala',
                    'info',
                    'Sí, participar',
                    '#form-other-options',
                    route(`${name}.participateInPublicRoom`, {
                        id: encryptedId
                    })
                );
            },
            6: (encryptedId) => {
                showToastOptionBtns(
                    'Quieres salir de esta sala',
                    'info',
                    'Sí, salir',
                    '#form-other-options',
                    route(`${name}.participateInPublicRoom`, {
                        id: encryptedId,
                        getOut: true
                    })
                );
            },
        };
    </script>
    @vite(['resources/js/games/bingo/form.js', 'resources/js/games/bingo/ctn-price-coins.js'])
@endsection
