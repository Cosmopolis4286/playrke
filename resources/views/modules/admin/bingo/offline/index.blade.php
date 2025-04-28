@extends('layouts.app_rke')
@section('title', $title)
@section('content')
    <div class="col-12">
        <div class="card">
            @include('includes.card-header')
            <div class="card-body">
                @include('includes.coin-tabs')
                <div class="btn-group" role="group" aria-label="Basic example">
                    <button type="button" class="btnBingo btn btn-sm btn-success" data-type="1">
                        <i class="fas fa-plus-square"></i> Crear
                    </button>
                    <button type="button" class="ml-1 btn btn-sm btn-primary">
                        <i class="fa-solid fa-coins"></i> Profit
                    </button>
                </div>
                <div class="tab-content" id="myTabContent">
                    <div class="tab-pane fade show active">
                        <div class="table-responsive mt-3">
                            <table class="table table-striped table-bordered my-2 w-100">
                                <thead>
                                    <tr>
                                        <th>Sala</th>
                                        <th>Fecha</th>
                                        <th>Hora</th>
                                        <th>CTN</th>
                                        <th>#PJ</th>
                                        <th>Venta</th>
                                        <th>Jackpot</th>
                                        <th>Status</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($rooms as $room)
                                        <tr>
                                            <td class="align-middle">
                                                <a class="badge bg-navy" title="Ver Reporte"
                                                    href="{{ route('seller.reportSalesPlayersCtns', [
                                                        'room' => $room->encrypted,
                                                        'coin' => encrypt(1),
                                                    ]) }}">
                                                    {{ $room->encrypted }}
                                                </a>
                                            </td>
                                            <td class="align-middle">
                                                {{ \Carbon\Carbon::parse($room->date)->format('d/m/y') }}
                                            </td>
                                            <td class="align-middle">{{ $room->time }} H</td>
                                            <td class="align-middle">{{ $coinSym }} {{ $room->price }}</td>
                                            <td class="align-middle">
                                                <i class="fa-solid fa-users mr-1"></i>
                                                {{ $room->players }}<br>
                                                <i class="fa-solid fa-trophy mr-1"></i>
                                            </td>
                                            <td class="align-middle">{{ $coinSym }} {{ $room->all_sale }}</td>
                                            <td>
                                                <small>
                                                    @if ($room->Config->jackpot_type == 1)
                                                        Acumulado
                                                    @elseif($room->Config->jackpot_type == 2)
                                                        Premio
                                                    @endif
                                                </small>
                                                <br>{{ $coinSym }} {{ $room->jackpot }}
                                            </td>
                                            <td class="align-middle">
                                                @if ($room->Status->id == 1)
                                                    <span class="badge bg-gray">{{ $room->Status->info }}</span>
                                                @elseif ($room->Status->id == 2)
                                                    <span class="badge bg-primary">{{ $room->Status->info }}</span>
                                                @elseif ($room->Status->id == 3)
                                                    <span class="badge bg-navy">{{ $room->Status->info }}</span>
                                                @elseif ($room->Status->id == 4)
                                                    <span class="badge bg-success">{{ $room->Status->info }}</span>
                                                @elseif ($room->Status->id == 5)
                                                    <span class="badge bg-black">{{ $room->Status->info }}</span>
                                                @endif
                                            </td>
                                            <td class="align-middle">
                                                <div class="btn-group" role="group" aria-label="Acciones">
                                                    @if ($room->status <= 2)
                                                        <button title="Editar" class="btnBingo btn btn-sm btn-primary"
                                                            data-type="2" data-id="{{ encrypt($room->id) }}">
                                                            <i class="fa-solid fa-pen-to-square"></i> </button>
                                                        @if ($room->status == 1)
                                                            <button class="btnBingo btn btn-sm btn-success" data-type="5"
                                                                data-id="{{ encrypt($room->id) }}" title="Publicar">
                                                                <i class="fa-solid fa-circle-up"></i>
                                                            </button>
                                                            <button class="btnBingo btn btn-sm btn-danger" data-type="3"
                                                                data-id="{{ encrypt($room->id) }}" title="Eliminar">
                                                                <i class="fa-solid fa-trash-can"></i>
                                                            </button>
                                                        @elseif ($room->status == 2)
                                                            <button class="btnBingo btn btn-sm btn-dark" data-type="6"
                                                                data-id="{{ encrypt($room->id) }}" title="Reembolsar">
                                                                <i class="fa-solid fa-rotate-right"></i>
                                                            </button>
                                                        @endif
                                                    @else
                                                        @if ($room->replay == 0)
                                                            <button class="btnBingo btn btn-sm btn-secondary" data-type="4"
                                                                data-id="{{ encrypt($room->id) }}" title="Repetir">
                                                                <i class="fa-solid fa-repeat"></i>
                                                            </button>
                                                        @endif
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4" class="text-right"><b>Total:</b></td>
                                        <td></td>
                                        <td>{{ $coinSym }} {{ $rooms->sum('all_sale') }}</td>
                                        <td>{{ $coinSym }} {{ $rooms->sum('jackpot') }}</td>
                                        <td colspan="3"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="my_profit" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-sm" role="document">
            <form action="{{ route('bingo.myprofit') }}" method="POST">@csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5>Calcular Profit</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body mt-n3">
                        <div class="row">
                            <div class="col-12">
                                <label class="lb_in">Selecionar Moneda</label>
                                <select name="coin" name="coin" class="form-control" required>
                                    <option value="">Todas las monedas:</option>
                                    @foreach ($coins as $coin)
                                        <option value="{{ $coin->id }}">{{ $coin->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label class="lb_in">Inicio</label>
                                <input type="date" name="date_a" class="form-control" required>
                            </div>
                            <div class="col-sm-6">
                                <label class="lb_in">Cierre</label>
                                <input type="date" name="date_b" class="form-control" required>
                            </div>
                        </div>
                        <div class="mt-3 mb-n1">
                            <span>Profit: </span>
                            <span id="profit_dt"></span>
                        </div>
                    </div>
                    <div class="modal-footer d-flex">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                        <button type="submit" class="ml-3 btn btn-primary">Calcular</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="mdlFormBingo" tabindex="-1" role="dialog" aria-labelledby="mdlLabel"
        aria-hidden="true" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="mdlLabel"></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="formBingo" method="POST">@csrf
                    <div id="formBingoPut">@method('PUT')</div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="form-group col-sm-6 col-md-4">
                                <label for="date">Fecha*</label>
                                <input type="date" id="date" name="date" class="form-control"
                                    required min="{{ \Carbon\Carbon::now()->format('Y-m-d') }}">
                            </div>
                            <div class="form-group col-sm-6 col-md-4">
                                <label for="time">Hora*</label>
                                <input type="time" id="time" name="time" class="form-control"
                                    required>
                            </div>
                            {{-- <div class="form-group col-sm-6 col-md-4">
                                <label class="country">Pais*</label>
                                <select id="country" name="country" class="form-control" required></select>
                            </div> --}}
                            {{-- <div class="form-group col-sm-6 col-md-4">
                                <label for="coin">Moneda*</label>
                                <select id="coin" name="coin" class="form-control" required></select>
                            </div> --}}
                            <div class="form-group col-sm-6 col-md-4">
                                <label for="ctn-price">Precio CTN*</label>
                                <input type="number" id="ctn-price" name="ctn_price" class="form-control"
                                    placeholder="Ingresar Precio" min="1" value="1" required>
                            </div>
                            {{-- <div class="form-group col-sm-6 col-md-4">
                                <label for="small-prizes">%Cantos</label>
                                <input type="number" value="0" id="small-prizes" name="small_prizes"
                                    class="form-control" placeholder="Ingrese monto a pagar" required>
                            </div> --}}
                            <div class="form-group col-sm-6 col-md-4">
                                <label for="serials">%Seriales</label>
                                <input type="number" value="5" min="5" max="12" id="serials"
                                    name="serials" class="form-control" placeholder="Ingrese valor" required>
                            </div>
                            <div class="form-group col-sm-6 col-md-4">
                                <label for="max-serial-winners">#Num. Seriales</label>
                                <input type="number" step="1" value="5" min="5" max="15"
                                    id="max-serial-winners" name="max_serial_winners" class="form-control"
                                    placeholder="Ingrese la cantidad" required>
                            </div>
                            <div class="form-group col-sm-6 col-md-4">
                                <label for="commerce-commission">%Comercio*</label>
                                <input type="number" id="commerce-commission" name="room_commission"
                                    class="form-control" placeholder="%Com" required value="20"
                                    step="0.05" min="20" max="45">
                            </div>
                            <div class="form-group col-sm-6 col-md-4">
                                <label for="jackpot-type">Tipo Bote*</label>
                                <select id="jackpot-type" name="jackpot_type" class="form-control"
                                    required></select>
                            </div>
                            <div class="form-group col-sm-6 col-md-4 hide-elem">
                                <label for="fixed-jackpot-value">Ingrese Valor Fijo:</label>
                                <input type="number" id="fixed-jackpot-value" name="fixed_jackpot_value"
                                    class="form-control" placeholder="Ingresar Monto" step="0.05"
                                    min="50" max="5000">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                        <button type="button" id="btnConfirm" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <form id="replay" action="{{ route('bingo_off.replay') }}" method="post">@csrf
        <input type="hidden" name="id" class="id">
    </form>
    <form id="publish" action="{{ route('bingo_off.publish') }}" method="post">@csrf
        <input type="hidden" name="id" class="id">
    </form>
    <form id="delete" action="{{ route('bingo_off.delete') }}" method="post">@csrf
        <input type="hidden" name="id" class="id">
    </form>
    <form id="refund" action="{{ route('bingo_off.refundRoom') }}" method="post">@csrf
        <input type="hidden" name="id" class="id">
    </form>
@endsection
@section('scripts')
    <script>
        const arrCoins = JSON.parse('<?php echo json_encode($coins); ?>');
        const arrCountries = JSON.parse('<?php echo json_encode($countries); ?>');
        const arrJackpotType = JSON.parse('<?php echo json_encode($jackpotType); ?>')
        var encryptedId;

        function insertInfoBingo(type, infoBingo) {
            const country = $("#country");
            const coin = $("#coin");
            const jackpotType = $("#jackpot-type");

            $("#country, #coin, #jackpot-type").empty(); // Limpia las opciones existentes
            // Countries
            if (type == 1) country.append(`<option value="" selected>Seleccionar:</option>`);

            $.each(arrCountries, function(index, item) {
                const isSelected = (type == 2 && item.id == infoBingo.country) ? 'selected' : '';
                country.append(`<option value="${item.id}" ${isSelected}>${item.name}</option>`);
            });
            // Coins
            if (type == 1) coin.append(`<option value="" selected>Seleccionar:</option>`);

            $.each(arrCoins, function(index, item) {
                const isSelected = (type == 2 && item.id == infoBingo.coin) ? 'selected' : '';
                coin.append(`<option value="${item.id}" ${isSelected}>(${item.name}) ${item.long_info}</option>`);
            });
            // jackpotType
            if (type == 1) jackpotType.append(`<option value="" selected>Seleccionar:</option>`);

            $.each(arrJackpotType, function(index, item) {
                const isSelected = (type == 2 && item.id == infoBingo.coin) ? 'selected' : '';
                jackpotType.append(
                    `<option value="${item.id}" ${isSelected}>${item.value}</option>`);
            });
            $('#mdlFormBingo').modal('show');
        }

        $(document).on("click", ".btnBingo", function() {
            const data = $(this).data();
            if (data.type > 1) encryptedId = data.id;
            if (data.type == 3) deleteBingo();

            if (data.type == 1) {
                $("#formBingoPut").remove();
                $("#mdlLabel").text(`Crear Sala Bingo `);
                $("#formBingo").attr('action', `{{ route('bingo_off.create') }}`);
                insertInfoBingo(1);
            } else if (data.type == 2) {
                $("#mdlLabel").text(`Editar Sala Bingo `);
                $("#formBingo").attr('action', `{{ route('bingo_off.update', ['id' => ':id']) }}`
                    .replace(':id', encryptedId));
                $('#formBingo').append(`<div id="formBingoPut">@method('PUT')</div>`);
                getInfoBingo(encryptedId);
            }
        });

        $(document).on("click", "#btnConfirm", function() {
            if ($("#date").val() && $("#time").val() && $("#country").val() && ("#coin") && $("#ctn-price") &&
                $("#serials") && $("#commerce-commission") && $("#jackpot-type")) {
                $("#formBingo").submit();
            } else {
                showToast('warning', `Toda la informacion es requerida`, '');
            }
        });

        $(".replay").click(function(e) {
            $(".id").val($(this).data('id'));
            Swal.fire({
                title: '¿Confirmas está acción?',
                text: "Repetir esta sala cada 24h",
                type: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                cancelButtonText: 'Cancelar',
                confirmButtonText: 'Sí, repetir',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#replay').submit();
                } else if (result.isDenied) {
                    Swal.fire('Los cambios no se guardaron', '', 'info')
                }
            })
        });
        $(".publish").click(function(e) {
            $(".id").val($(this).data('id'));
            Swal.fire({
                title: '¿Confirmas está acción?',
                text: "Se publicará esta sala al público",
                type: 'info',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, publicarla',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#publish').submit();
                } else if (result.isDenied) {
                    Swal.fire('Los cambios no se guardaron', '', 'info')
                }
            })
        });

        $(".eliminar").click(function(e) {
            $(".id").val($(this).data('id'));
            Swal.fire({
                title: '¿Confirmas está acción?',
                text: "Esta sala sera eliminada definitivamente",
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#delete').submit();
                } else if (result.isDenied) {
                    Swal.fire('Los cambios no se guardaron', '', 'info')
                }
            })
        });
        $(".refund").click(function(e) {
            $(".id").val($(this).data('id'));
            Swal.fire({
                title: '¿Confirmas está acción?',
                text: "El jackpot de esta sala sera reembolsado",
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, reembolsar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#refundRoom').submit();
                } else if (result.isDenied) {
                    Swal.fire('Los cambios no se guardaron', '', 'info')
                }
            })
        });
        $(".type_boat").change(function() {
            var boat = $(this).val();
            if (boat == 2) {
                $(".mont_valf").show();
            } else {
                $(".mont_valf").hide();
            }
        });
        $(".edit_rm").click(function() {
            var data = $(this).data();

            $(".id").val(data.id);
            $("#date_edit").val(data.date);
            $("#time_edit").val(data.time);
            $("#my_coin").val(data.coin).html(data.coin_name);

            arrCoins.forEach(function(coin) {
                if (data.id == coin.id) {
                    $("#opc_coin_" + coin.id).hide();
                }
            });

            $("#val_ctn_edit").val(data.price);
            $("#val_automc_edit").val(data.val_automc);
            $("#val_songs_edit").val(data.val_songs);

            const typeBoat = (data.type_boat == 1) ? 'Aumet' : 'ValFJ';
            $("#my_type").val(data.type_boat).html(typeBoat);

            if (data.type_boat == $('#bot_1').val()) {
                $("#bot_1").hide();
            } else {
                $(".mont_valf").show();
                $("#val_fij_edit").val(data.boat).show();
                $("#bot_2").hide();
            }
            $("#val_com_edit").val(data.com);
        });
    </script>
@endsection
