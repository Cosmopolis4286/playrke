@extends('layouts.app_rke')
<title>PlayRKE - Salas de Bingo</title>
@section('fnd-content')
    <div class="content-wrapper">
    @endsection
    @section('content')
        <div class="row">
            <div class="col-md-12">
                <div class="card bg-rke">
                    <div class="card-header">
                        <a href="{{ route('dashboard') }}" title="Regresar" style="position:relative;top:-1px;font-size:18px;">
                            <i class="fas fa-chevron-circle-left"></i>
                        </a>
                        <span style="margin-left:7px; font-size:20px;">Bingo - Salas</span>
                        @if (Session::has('message'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {!! html_entity_decode(Session::get('message')) !!}
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @endif
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12">
                                <style>
                                    .opc_bing {
                                        margin-top: -1%;
                                        display: flex;
                                        justify-content: space-between;
                                        font-size: 18.5px;
                                    }
                                </style>
                                <div class="opc_bing">
                                    <a href="#" data-toggle="modal" data-target="#create_bg">
                                        <i class="fas fa-plus-square"></i> Crear Sala
                                    </a>
                                    <a href="#" data-toggle="modal" data-target="#my_profit">
                                        <i class="fa-solid fa-coins"></i>
                                    </a>
                                </div>
                                <div class="table-responsive">
                                    <table id="t_1" class="table table-striped table-bordered" style="width:100%">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Fecha</th>
                                                <th>Hora</th>
                                                <th>Status</th>
                                                <th>Coin</th>
                                                <th>CTN</th>
                                                <th>Prize</th>
                                                <th>PJ</th>
                                                <th>Bote</th>
                                                <th>Tipo</th>
                                                <th>Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($rooms as $rm)
                                                <tr>
                                                    <td>{{ $rm->encrypted }}</td>
                                                    <td>{{ \Carbon\Carbon::parse($rm->date)->format('d/m/y') }}</td>
                                                    <td>{{ $rm->time }}</td>
                                                    <td>{{ $rm->Status->info }}</td>
                                                    <td>{{ $rm->name }}</td>
                                                    <td>{{ $rm->ctns }}</td>
                                                    <td>{{ $rm->price }}</td>
                                                    {{-- <td>{{ $rm->val_songs }}</td> --}}
                                                    <td>{{ $rm->players }}</td>
                                                    <td>{{ $rm->jackpot }} [{{ $rm->com }}%]</td>
                                                    <td>
                                                        @if ($rm->type_boat == 1)
                                                            Aumet
                                                        @elseif($rm->type_boat == 2)
                                                            ValFJ
                                                        @endif
                                                    </td>
                                                    {{-- <td>
                                                        @if ($rm->profit > 0)
                                                            <span style="color:rgb(0, 128, 28)">{{ $rm->profit }}</span>
                                                        @else
                                                            <span style="color: rgb(151, 0, 0)">{{ $rm->profit }}</span>
                                                        @endif
                                                    </td> --}}
                                                    <td align="center">
                                                        @if ($rm->status != 3)
                                                            @if ($rm->status != 4)
                                                                <a href="#" title="Editar"
                                                                    style="color:rgb(0, 209, 94)" class="edit_rm mr-1"
                                                                    data-target="#edit_inf" data-toggle="modal"
                                                                    data-id='{{ $rm->id }}'
                                                                    data-date="{{ $rm->date }}"
                                                                    data-time="{{ date('H:i', strtotime($rm->time)) }}"
                                                                    data-coin="{{ $rm->coin }}"
                                                                    data-coin_name="{{ $rm->name }}"
                                                                    data-price="{{ $rm->price }}"
                                                                    data-val_songs="{{ $rm->val_songs }}"
                                                                    data-type_boat="{{ $rm->type_boat }}"
                                                                    data-boat="{{ $rm->jackpot }}"
                                                                    data-com="{{ $rm->com }}">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                            @endif
                                                            @if ($rm->replay == 0)
                                                                <a href="#" class="replay mr-1"
                                                                    data-id="{{ $rm->id }}"
                                                                    title="Repetir Automatico"
                                                                    style="color:rgb(0, 125, 209)">
                                                                    <i class="fa-solid fa-repeat"></i>
                                                                </a>
                                                            @endif
                                                            @if ($rm->status == 1)
                                                                <a href="#" class="publish mr-1"
                                                                    data-id="{{ $rm->id }}" title="Publicar"
                                                                    style="color: rgb(218, 54, 240)">
                                                                    <i class="fa-solid fa-comment-dots"></i>
                                                                </a>
                                                                <a href="#" class="eliminar"
                                                                    data-id="{{ $rm->id }}" title="Eliminar"
                                                                    style="color: rgb(238, 61, 61)">
                                                                    <i class="fa-solid fa-trash-can"></i>
                                                                </a>
                                                            @elseif ($rm->status == 2)
                                                                <a href="#" class="refund"
                                                                    data-id="{{ $rm->id }}" title="Reembolsar"
                                                                    style="color: rgb(135, 0, 153)">
                                                                    <i class="fa-solid fa-rotate-right"></i>
                                                                </a>
                                                            @endif
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="my_profit" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
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
        <div class="modal fade" id="create_bg" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-scrollable" style="max-width:400px" role="document">
                <form action="{{ route('bingo_off.create') }}" method="POST">@csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5>Crear Sala Bingo</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-sm-6">
                                    <label class="lb_in">Fecha</label>
                                    <input type="date" name="date" class="form-control" required
                                        min="{{ \Carbon\Carbon::now()->format('Y-m-d') }}">
                                </div>
                                <div class="col-sm-6">
                                    <label class="lb_in">Hora</label>
                                    <input type="time" name="time" class="form-control" required>
                                </div>
                                <div class="col-sm-6">
                                    <label class="lb_in">Pais</label>
                                    <select name="coin" name="coin" class="form-control" required>
                                        <option value=""disabled selected>Seleccionar:</option>
                                            <option value="29">Brasil</option>
                                            <option value="234">Venezuela</option>
                                    </select>
                                </div>
                                <div class="col-sm-6">
                                    <label class="lb_in">Moneda</label>
                                    <select name="coin" name="coin" class="form-control" required>
                                        <option value="" disabled selected>Seleccionar:</option>
                                        @foreach ($coins as $coin)
                                            <option value="{{ $coin->id }}">{{ $coin->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-sm-6">
                                    <label class="lb_in">Precio Carton</label>
                                    <input type="number" name="val_ctn" class="form-control"
                                        placeholder="Ingresar Precio" required>
                                </div>
                                <div class="col-sm-6">
                                    <label class="lb_in">Cantos</label>
                                    <input type="number" value="0" name="val_songs" class="form-control"
                                        placeholder="Ingrese monto a pagar" required>
                                </div>
                                <div class="col-sm-6">
                                    <label class="lb_in">Comision</label>
                                    <input type="number" name="val_com" class="form-control" placeholder="%Com"
                                        required value="5">
                                </div>
                                <div class="col-sm-6">
                                    <label class="lb_in">Tipo Bote</label>
                                    <select name="type_boat" class="type_boat form-control" required>
                                        <option value="">Seleccionar:</option>
                                        <option value="1">Acumulativo</option>
                                        <option value="2">Valor Fijo</option>
                                    </select>
                                </div>
                                <div class="col-sm-6 mont_valf" style="display: none">
                                    <label class="lb_in">Ingrese Valor Fijo:</label>
                                    <input type="number" id="val_fij" name="val_fij" class="form-control"
                                        placeholder="Ingresar Monto" step="0.01" min="150">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer d-flex">
                            <button type="submit" class="btn_bing mr-3 btn btn-primary">Crear Sala</button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        {{-- Editar --}}
        <div class="modal fade" id="edit_inf" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-scrollable" style="max-width:400px;" role="document">
                <form action="{{ route('bingo_off.update') }}" method="POST">@csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5>Editar Sala Bingo</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="id" class="id">
                            <div class="row">
                                <div class="col-sm-6 date_div">
                                    <label class="lb_in">Fecha</label>
                                    <input type="date" name="date" id="date_edit" class="form-control">
                                </div>
                                <div class="col-sm-6 time_div">
                                    <label class="lb_in">Hora</label>
                                    <input type="time" name="time" id="time_edit" class="form-control">
                                </div>
                                <div class="col-sm-6 coin_div">
                                    <label class="lb_in">Moneda</label>
                                    <select name="coin" class="form-control">
                                        <option id="my_coin" value=""></option>
                                        @foreach ($coins as $coin)
                                            <option id="opc_coin_{{ $coin->id }}" value="{{ $coin->id }}">
                                                {{ $coin->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-sm-6 price_div">
                                    <label class="lb_in">Precio Carton</label>
                                    <input type="number" name="val_ctn" id="val_ctn_edit" class="form-control"
                                        placeholder="Ingresar Precio" required>
                                </div>
                                <div class="col-sm-6 autom_div">
                                    <label class="lb_in">Cantos</label>
                                    <input type="number" name="val_songs" id="val_songs_edit" class="form-control"
                                        placeholder="Ingrese monto a pagar">
                                </div>
                                <div class="col-sm-6 com_div">
                                    <label class="lb_in">Comision</label>
                                    <input type="number" name="val_com" id="val_com_edit" class="form-control"
                                        placeholder="Ingrese el valor">
                                </div>
                                <div class="col-sm-6 type_boat_div">
                                    <label class="lb_in">Tipo Bote</label>
                                    <select name="type_boat" class="type_boat form-control">
                                        <option id="my_type" value=""></option>
                                        <option id="bot_1" value="1">Acumulativo</option>
                                        <option id="bot_2" value="2">Valor Fijo</option>
                                    </select>
                                </div>
                                <div class="col-sm-6 mont_valf" style="display: none">
                                    <label class="lb_in">Ingrese Valor Fijo:</label>
                                    <input type="number" name="val_fij_edit" id="val_fij_edit" class="form-control"
                                        placeholder="Ingresar Monto">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer d-flex">
                            <button type="submit" class="mr-3 btn btn-primary">Editar Sala</button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </form>
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
            var arrCoins = @json($coins);
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
                    text: "El Bote de esta sala sera reembolsado",
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
