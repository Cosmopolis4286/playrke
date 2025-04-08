@extends('layouts.app_rke')
<title>Bingo - Ganadores</title>
@section('fnd-content')
    <div class="content-wrapper">
    @endsection
    @section('content')
        <div class="row">
            <div class="col-md-12">
                <div class="card bg-rke">
                    <div class="card-header">
                        <a href="{{ url('/home') }}" title="Regresar" style="position:relative;top:-1px;font-size:18px;">
                            <i class="fas fa-chevron-circle-left"></i>
                        </a>
                        <span style="margin-left:7px; font-size:20px;">Bingo - Salas</span>
                        @if (Session::has('mensaje'))
                            <div class="col-12 col-lg-5">
                                <caption>
                                    <div class="alert alert-info alert-dismissible" role="alert">
                                        <strong></strong> {{ Session::get('mensaje') }}
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                </caption>
                            </div>
                        @endif
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12">
                                <div style="font-size:18.5px;">
                                    <a href="#" data-toggle="modal" data-target="#create_bg">
                                        <i class="fas fa-plus-square"></i> Crear Sala
                                    </a>
                                </div>
                                <div class="table-responsive">
                                    <table id="t_1" class="table table-striped table-bordered  nowrap"
                                        style="width:100%">
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Hora</th>
                                                <th>REP</th>
                                                <th>CTN</th>
                                                <th>AutoM</th>
                                                <th>PJ</th>
                                                <th>jackpot</th>
                                                <th>Tipo</th>
                                                <th>Com</th>
                                                <th>Profit</th>
                                                <th>Estatus</th>
                                                <th>OPC's</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($rooms as $rm)
                                                <tr>
                                                    <td>{{ $rm->date }}</td>
                                                    <td>{{ $rm->time }}</td>
                                                    <td></td>
                                                    <td>{{ $rm->price }} {{ $rm->name }}</td>
                                                    <td>{{ $rm->automarker }} {{ $rm->name }}</td>
                                                    <td>{{ $rm->players }}</td>
                                                    <td>{{ $rm->jackpot }} {{ $rm->name }}</td>
                                                    <td>
                                                        @if ($rm->type_boat == 0)
                                                            Aumento
                                                        @elseif($rm->type_boat == 1)
                                                            Valor Fijo
                                                        @endif
                                                    </td>
                                                    <td>{{ $rm->com }}%</td>
                                                    <td>
                                                        @if ($rm->profit > 0)
                                                            <span style="color:rgb(0, 128, 28)">{{ $rm->profit }}</span>
                                                        @else
                                                            <span style="color: rgb(151, 0, 0)">{{ $rm->profit }}</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $rm->info }}</td>
                                                    <td align="center">
                                                        <a href="#" title="Editar" style="color:rgb(0, 209, 94)"
                                                            class="edit_rm mr-1" data-target="#edit_inf" data-toggle="modal"
                                                            data-id='{{ $rm->id }}' data-date="{{ $rm->date }}"
                                                            data-time="{{ $rm->time_adm }}"
                                                            data-coin="{{ $rm->name }}"
                                                            data-price="{{ $rm->price }}"
                                                            data-automarker="{{ $rm->automarker }}"
                                                            data-type_boat="{{ $rm->type_boat }}"
                                                            data-com="{{ $rm->com }}">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="#" class="replay mr-1" data-id="{{ $rm->id }}"
                                                            title="Repetir Automatico" style="color:rgb(0, 125, 209)">
                                                            <i class="fa-solid fa-repeat"></i>
                                                        </a>
                                                        @if ($rm->status == 0)
                                                            <a href="#" class="publicar mr-1"
                                                                data-id="{{ $rm->id }}" title="Publicar"
                                                                style="color: rgb(218, 54, 240)">
                                                                <i class="fa-solid fa-comment-dots"></i>
                                                            </a>
                                                        @endif
                                                        <a href="#" class="eliminar" data-id="{{ $rm->id }}"
                                                            title="Eliminar" style="color: rgb(238, 61, 61)">
                                                            <i class="fa-solid fa-trash-can"></i>
                                                        </a>
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
        <div class="modal fade" id="create_bg" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:400px" role="document">
                <form action="{{ route('bingo.store') }}" method="POST">@csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5>Crear Sala Bingo</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="col-10" style="margin: 0 auto">
                                <input type="file" name="imagen" id="inpFile"
                                    value="{{ old('imagen', $data->imagen ?? '') }}">
                                <div class="image-preview" id="imagePreview" style="max-height: 132px">
                                    <img src="" alt="Image Preview" class="img-preview">
                                    <span class="text-img-t">Imagen</span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6">
                                    <label class="lb_in">Fecha</label>
                                    <input type="date" name="date" class="form-control" required>
                                </div>
                                <div class="col-sm-6">
                                    <label class="lb_in">Hora</label>
                                    <input type="time" name="time" class="form-control" required>
                                </div>
                                <div class="col-sm-6">
                                    <label class="lb_in">Moneda</label>
                                    <select name="coin" name="coin" class="form-control" required>
                                        <option value="" disabled selected>Seleccionar</option>
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
                                    <label class="lb_in">Automarcar</label>
                                    <input type="number" name="val_autom" class="form-control"
                                        placeholder="Precio Automarcar" required>
                                </div>
                                <div class="col-sm-6">
                                    <label class="lb_in">Comision</label>
                                    <input type="number"name="val_com" class="form-control" placeholder="%Com" required>
                                </div>
                                <div class="col-sm-6">
                                    <label class="lb_in">Tipo jackpot</label>
                                    <select name="type_boat" class="type_boat form-control" required>
                                        <option value="" disabled selected>Seleccionar</option>
                                        <option value="0">Acumulativo</option>
                                        <option value="1">Valor Fijo</option>
                                    </select>
                                </div>
                                <div class="col-sm-6 mont_valf" style="display: none">
                                    <label class="lb_in">Ingrese Valor Fijo:</label>
                                    <input type="number" name="val_fij" class="form-control"
                                        placeholder="Ingresar Monto">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer d-flex">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                            <button type="submit" id="sub_info" class="ml-3 btn btn-primary">Crear Sala</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        {{-- Editar --}}
        <div class="modal fade" id="edit_inf" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:400px"
                role="document">
                <form action="{{ route('bingo.update') }}" method="POST">@csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5>Editar Sala Bingo</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="id" class="id">
                            <div class="col-10" style="margin: 0 auto">
                                <input type="file" name="imagen" id="inpFile"
                                    value="{{ old('imagen', $data->imagen ?? '') }}">
                                <div class="image-preview" id="imagePreview" style="max-height: 132px">
                                    <img src="" alt="Image Preview" class="img-preview">
                                    <span class="text-img-t">Imagen</span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6 date_div">
                                    <label class="lb_in">Fecha</label>
                                    <input type="date" name="date" id="date" class="form-control">
                                </div>
                                <div class="col-sm-6 time_div">
                                    <label class="lb_in">Hora</label>
                                    <input type="time" name="time" id="time" class="form-control">
                                </div>
                                <div class="col-sm-6 coin_div">
                                    <label class="lb_in">Moneda</label>
                                    <select name="coin" id="coin" class="form-control">
                                        <option value="" disabled selected>Seleccionar</option>
                                        @foreach ($coins as $coin)
                                            <option value="{{ $coin->id }}">{{ $coin->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-sm-6 price_div">
                                    <label class="lb_in">Precio Carton</label>
                                    <input type="number" name="val_ctn" id="val_ctn" class="form-control"
                                        placeholder="Ingresar Precio" required>
                                </div>
                                <div class="col-sm-6 autom_div">
                                    <label class="lb_in">Automarcar</label>
                                    <input type="number" name="val_autom" id="val_autom" class="form-control"
                                        placeholder="Precio Automarcar">
                                </div>
                                <div class="col-sm-6 com_div">
                                    <label class="lb_in">Comision</label>
                                    <input type="number"name="val_com" id="val_com" class="form-control"
                                        placeholder="Ingrese el valor">
                                </div>
                                <div class="col-sm-6 type_boat_div">
                                    <label class="lb_in">Tipo jackpot</label>
                                    <select name="type_boat" class="type_boat form-control">
                                        <option value="" disabled selected>Seleccionar</option>
                                        <option value="0">Acumulativo</option>
                                        <option value="1">Valor Fijo</option>
                                    </select>
                                </div>
                                <div class="col-sm-6 mont_valf" style="display: none">
                                    <label class="lb_in">Ingrese Valor Fijo:</label>
                                    <input type="number" name="val_fij" id="val_fij" class="form-control"
                                        placeholder="Ingresar Monto">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer d-flex">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                            <button type="submit" id="sub_info" class="ml-3 btn btn-primary">Editar Sala</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <form id="replay" action="{{ route('bingo.update') }}" method="post">@csrf
            <input type="hidden" name="id" class="id">
        </form>
        <form id="publicar" action="{{ route('bingo.publish') }}" method="post">@csrf
            <input type="hidden" name="id" class="id">
        </form>
        <form id="delete" action="{{ route('bingo.delete') }}" method="post">@csrf
            <input type="hidden" name="id" class="id">
        </form>
    @endsection
    @section('scripts')
        <script>
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
                    confirmButtonText: 'Sí, publicarla',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#replay').submit();
                    } else if (result.isDenied) {
                        Swal.fire('Changes are not saved', '', 'info')
                    }
                })
            });
            $(".publicar").click(function(e) {
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
                        $('#publicar').submit();
                    } else if (result.isDenied) {
                        Swal.fire('Changes are not saved', '', 'info')
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
                        Swal.fire('Changes are not saved', '', 'info')
                    }
                })
            });
            $(".type_boat").change(function() {
                var boat = $(".type_boat").val();
                if (boat == 1) {
                    $(".mont_valf").show();
                } else {
                    $(".mont_valf").hide();
                }
            });
            $(".edit_rm").click(function() {
                $("#id").val($(this).data('id'));
                $("#date").val($(this).data('date'));
                $("#time").val($(this).data('time'));
                $("#coin").val($(this).data('coin'));
                $("#val_ctn").val($(this).data('price'));
                $("#val_autom").val($(this).data('automarker'));
                $(".type_boat").val($(this).data('type_boat'));
                $("#val_com").html($(this).data('com'));
            });
            /*$("#sub_info").click(function() {
                $("#sub_info").submit();
            });*/
        </script>
    @endsection
