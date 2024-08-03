@extends('layouts.app_rke')
<title>PlayRKE - Editar Trivia</title>
@section('fnd-content')
    <div class="content-wrapper">
    @endsection
    @section('content')
        <div class="row">
            <div class="col-md-12">
                <div class="card bg-rke">
                    <div class="card-header" id="headingOne" style="">
                        <div class="sub_ti-tle">
                            <a href="{{ route('trivia.rooms') }}" title="Regresar"
                                style="position:relative;top:-1px;font-size:18px;">
                                <i class="fas fa-chevron-circle-left"></i>
                            </a>
                            <span style="margin-left:7px; font-size:20px;">Editar Info. Trivia</span>
                            @if (Session::has('mensaje'))
                                <div class="col-12 col-lg-6">
                                    <caption>
                                        <div class="alert alert-success alert-dismissible" role="alert">
                                            <strong> {{ Session::get('mensaje') }}</strong>
                                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                    </caption>
                                </div>
                            @endif
                        </div>
                    </div>
                    <form action="{{ route('trivia.updateTriviaMulti') }}" id="enviar" method="POST"
                        enctype="multipart/form-data">@csrf
                        <input type="hidden" name="id" value="{{ $data->id }}">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-5 col-lg-3"style="margin-bottom:16px;">
                                    <input type="file" name="inpFile" style="max-width:100%;">
                                    <div class="image-preview" id="imagePreview">
                                        <img src="{{ asset('imgSystem/imgTrivia/' . $data->imagen) }}" alt="Image Preview"
                                            class="img-preview-e" name='imagen_tr'>
                                    </div>
                                </div>
                                <div class="col-md-7 col-lg-9">
                                    <div class="row">
                                        <div class="col-sm-6 col-md-4">
                                            <label class="lb_in">Nombre</label>
                                            <input type="text" id="name_pds" name="nombre" value="{{ $data->nombre }}"
                                                class="form-control" required>
                                        </div>
                                        <div class="col-6 col-sm-6 col-md-4 col-lg-4">
                                            <label class="lb_in">Fecha</label>
                                            <input type="date" name="fecha" id="fecha" class="form-control"
                                                value="{{ $data->fecha }}"
                                                min="{{ \Carbon\Carbon::now()->format('Y-m-d') }}">
                                        </div>
                                        <div class="col-6 col-sm-6 col-md-4 col-lg-4">
                                            <label class="lb_in">Hora</label>
                                            <input type="time" id="h_pds" name="hora" class="form-control"
                                                value="{{ $data->hora }}">
                                        </div>
                                        <div class="col-6 col-sm-6 col-md-4 col-lg-3">
                                            <label class="lb_in">Precio</label>
                                            <div style="display: flex">
                                                <div style="max-width:71px;">
                                                    <span class="form-control_d">{{ $data->Coin->name }}</span>
                                                </div>
                                                <input type="number" id="in" name="precio" class="form-control"
                                                    step="0.05" min="0.65" max="15"
                                                    value="{{ $data->precio }}"
                                                    onKeyUp="if(this.value>15){this.value='15';}else if(this.value<0.65){this.value='0.65';}"
                                                    required>
                                            </div>
                                        </div>
                                        <div class="col-6 col-sm-4 col-md-4 col-lg-3">
                                            <label class="lb_in">Mínimo&nbspPJ</label>
                                            <input type="number" id="min_pj" class="form-control"
                                                placeholder="Mínimo de Jugadores" step="5" min="15"
                                                max="200" name="min_pj" value="{{ $data->min_pj }}"
                                                onKeyUp="if(this.value>200){this.value='200';}else if(this.value<15){this.value='15';}"required>
                                        </div>
                                        <div class="col-6 col-sm-4 col-md-4 col-lg-3">
                                            <label class="lb_in">Inscritos</label>
                                            <input type="text" class="form-control_d"
                                                value="{{ $data->players }} / {{ $data->min_pj }} PJ" disabled>
                                        </div>
                                        <div class="col-6 col-sm-4 col-md-4 col-lg-3">
                                            <label class="lb_in">jackpot</label>
                                            <div>
                                                <span class="form-control_d">{{ $data->jackpot }}
                                                    {{ $data->name }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row mt-n4">
                                        {{-- Tipo Winners NUM --}}
                                        <div class="col-6 col-sm-4 col-lg-3">
                                            <label class="lb_in">&nbsp</label>
                                            <div style="display: flex">
                                                <input class="mr-2" value="" type="radio" name="np"
                                                    id="num_wn">
                                                <label for="num_wn"></label>
                                                <input id="" type="text" name=""
                                                    class="in_numw form-control" value=""
                                                    placeholder="Núm. Winners">
                                            </div>
                                        </div>
                                        {{-- Tipo Winners % --}}
                                        <div class="col-6 col-sm-4 col-lg-3">
                                            <label class="lb_in">&nbsp</label>
                                            <div style="display: flex">
                                                <input class="mr-2" value="" type="radio" name="np"
                                                    id="por_wn">
                                                <label for="por_wn"></label>
                                                <input id="" type="text" name=""
                                                    class="in_pormw form-control" value="" placeholder="% Winners">
                                            </div>
                                        </div>
                                        <div class="col-4 col-sm-2 col-lg-2">
                                            <label class="lb_in mb-2">&nbsp</label>
                                            <div>
                                                <input class="mr-1" type="radio" name="status"
                                                    @if ($data->status == 2) checked value="1" @else value="2" @endif>Disp.
                                            </div>
                                        </div>
                                        <div class="col-4 col-sm-2 col-lg-2">
                                            <label class="lb_in mb-2">&nbsp</label>
                                            <div>
                                                <input class="mr-1" type="radio" name="status"
                                                    @if ($data->status == 1) checked value="1" @else value="1" @endif>N/D
                                            </div>
                                        </div>
                                        <div class="save_pf col-4 col-sm-12 col-md-1 col-lg-1">
                                            <label class="lb_in mb-2">&nbsp</label>
                                            <div>
                                                <button id="enviardatos" class="btn btn-primary profile-button">
                                                    <i class="fa-solid fa-floppy-disk"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="card bg-rke">
                    <div class="card-header" style="font-size:18px;">
                        <a href="#" class="" style="float:left; margin-right:13px;" data-target="#tap"
                            data-toggle="modal">
                            <i class="fas fa-plus-square" style="font-size:24px;"></i>
                            <a> questions │ Respuestas
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12">
                                <div class="table-responsive">
                                    <table id="t_1" class="table table-striped table-bordered  nowrap"
                                        style="width:100%">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>question</th>
                                                <th>Imagen</th>
                                                <th>R Correcta</th>
                                                <th>Incorrecta</th>
                                                <th>Incorrecta</th>
                                                <th>Incorrecta</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($question as $key => $date)
                                                <tr>
                                                    <td>{{ $date->num_quest }}</td>
                                                    <td>{{ $date->question }}</td>
                                                    <td>
                                                        <img class="img-table"
                                                            src="{{ asset('imgSystem/imgquestions/' . $date->imagen) }}">
                                                    </td>
                                                    <td>{{ $date->correct_answer }}</td>
                                                    <td>{{ $date->option_1 }}</td>
                                                    <td>{{ $date->option_2 }}</td>
                                                    <td>{{ $date->option_3 }}</td>
                                                    <td>
                                                        <a href="#" class="btn bg-cyan informacion"
                                                            data-target="#edit_p" data-toggle="modal"
                                                            data-id='{{ $date->id }}'
                                                            data-question="{{ $date->question }}"
                                                            data-imagen="{{ asset('imgSystem/imgquestions/' . $date->imagen) }}"
                                                            data-rcorrecta="{{ $date->correct_answer }}"
                                                            data-opcion1="{{ $date->option_1 }}"
                                                            data-opcion2="{{ $date->option_2 }}"
                                                            data-opcion3="{{ $date->option_3 }}"
                                                            data-nump="{{ $date->num_quest }}">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div><br>
                    </div>
                </div>
                {{-- Insert Quest --}}
                <form action="{{ route('trivia.insertp') }}" id="questionsenvio" method="POST"
                    enctype="multipart/form-data">@csrf
                    <div class="modal fade" id="tap" tabindex="-1" role="dialog"
                        aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
                            <div class="modal-content">
                                <div class="modal-header">PlayRKE © 2022
                                    <span class="sub"></span>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <input type="hidden" name="id_trivia" value="{{ $data->id }}">
                                        <div class="col-12">
                                            <label class="lb_in">Nueva question</label>
                                            <input type="text" id="question" name="question" class="form-control"
                                                required>
                                        </div>
                                        <div class="col-12">
                                            <label class="lb_in">Respuesta Correcta</label>
                                            <input type="text" id="correcta" name="correcta" class="form-control"
                                                required>
                                        </div>
                                        <div class="mt-3 col-12 center-img">
                                            <input type="file" name="imagen_question" id="inpImg"
                                                value="{{ old('imagen_question', $data->imagen ?? '') }}" required>
                                            <div class="image-preview-p" id="imagePreview-p">
                                                <img src="" alt="Image Preview" class="img-preview">
                                                <span class="text-img-t">Img question</span>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <label class="lb_in">Opc. Incorrecta</label>
                                            <input type="text" name="option_1" class="form-control" id="option_1"
                                                required>
                                        </div>
                                        <div class="col-12">
                                            <label class="lb_in">Opc. Incorrecta</label>
                                            <input type="text" name="option_2" class="form-control"
                                                id="option_2"required>
                                        </div>
                                        <div class="col-12">
                                            <label class="lb_in">Opc. Incorrecta</label>
                                            <input type="text" name="option_3" class="form-control" id="option_3"
                                                required>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal"
                                        id="close">Cerrar</button>
                                    <button type="button" class="bt_ke btn btn-secondary"
                                        id="envquestions">Guardar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
                {{-- Insert Quest --}}

                {{-- Edit-Quest --}}
                <form action="{{ route('trivia.update_p') }}" method="POST" enctype="multipart/form-data"
                    id="questionsenvio_update">@csrf
                    <div class="modal fade" id="edit_p" tabindex="-1" role="dialog"
                        aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
                            <div class="modal-content">
                                <div class="modal-header">PlayRKE © 2023
                                    <span class="sub"></span>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <input type="hidden" name="id_trivia" value="{{ $data->id }}">
                                        <div class="col-12">
                                            <input type="hidden" id="id_p" name="id_p" class="form-control"
                                                required>
                                            <label class="lb_in">question # <label id="np"></label></label>
                                            <input type="text" id="question_e" name="question_e" class="form-control"
                                                required>
                                        </div>
                                        <div class="col-12">
                                            <label class="lb_in">Respuesta Correcta</label>
                                            <input type="text" id="correcta_e" name="correcta" class="form-control"
                                                required>
                                        </div>
                                        <hr>
                                        <div class="mt-3 col-12 center-img">
                                            <input type="file" name="imgQ_e" value="{{ $data->imagen }}">
                                            <div class="image-preview-p" id="imagePreview-p-e">
                                                <img id="imgQ" class="img-preview-e">
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <label class="lb_in">Opc. Incorrecta</label>
                                            <input type="text" name="option_1_e" class="form-control" id="option_1_e"
                                                required>
                                        </div>
                                        <div class="col-12">
                                            <label class="lb_in">Opc. Incorrecta</label>
                                            <input type="text" name="option_2_e" class="form-control"
                                                id="option_2_e"required>
                                        </div>
                                        <div class="col-12">
                                            <label class="lb_in">Opc. Incorrecta</label>
                                            <input type="text" name="option_3_e" class="form-control" id="option_3_e"
                                                required>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary mr-2" data-dismiss="modal"
                                        id="close">Cerrar</button>
                                    <button type="button" class="bt_ke btn btn-secondary"
                                        id="envquestions_update">Editar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
                {{-- Edit-Quest --}}
            </div>
        @endsection
        @section('validate')
            <script>
                const ImgP = document.getElementById('inpImg');
                const previewContainer = document.getElementById('imagePreview-p');
                const previewImage = previewContainer.querySelector(".img-preview");
                const textrivia = previewContainer.querySelector(".text-img-t");

                ImgP.addEventListener("change", function() {
                    const file = this.files[0];

                    if (file) {
                        const reader = new FileReader();

                        textrivia.style.display = "none";
                        previewImage.style.display = "block";
                        reader.addEventListener("load", function() {
                            console.log(this);
                            previewImage.setAttribute("src", this.result)
                        });
                        reader.readAsDataURL(file);
                    } else {
                        textrivia.style.display = "null";
                        previewImage.style.display = "null";
                        previewImage.setAttribute("src", "");
                    }
                });
            </script>
        @endsection
        @section('scripts')
            <script>
                var el = document.getElementById("in")
                el.value = el.valueAsNumber.toFixed(2)
                el.addEventListener('input', function(event) {
                    event.target.value = event.target.valueAsNumber.toFixed(2)
                });

                $("#enviardatos").click(function() {
                    if ($("#name_pds").val() == "" || $("#in").val() == "" || $("#min_pj").val() == "") {
                        alert('Todos los campos son Obligatorios');
                    } else {
                        $("#enviar").submit();
                    }
                });

                $("#envquestions").click(function() {
                    if ($("#question").val() == "" || $("#correcta").val() == "" || $("#option_1").val() == "" ||
                        $("#option_2").val() == "" || $("#option_3").val() == "") {
                        alert('Todos los campos son obligatorios');
                    } else {
                        $("#questionsenvio").submit();
                    }
                });

                $(".informacion").click(function() {
                    $("#question_e").val($(this).data('question'));
                    $("#imgQ").attr("src", $(this).data('imagen'));
                    $("#correcta_e").val($(this).data('rcorrecta'));
                    $("#option_1_e").val($(this).data('opcion1'));
                    $("#option_2_e").val($(this).data('opcion2'));
                    $("#option_3_e").val($(this).data('opcion3'));
                    $("#id_p").val($(this).data('id'));
                    $("#np").html($(this).data('nump'));

                });

                $("#envquestions_update").click(function() {
                    if ($("#question_e").val() == "" || $("#correcta_e").val() == "" || $("#option_1_e").val() == "" ||
                        $("#option_2_e").val() == "" || $("#option_3_e").val() == "") {
                        alert('Todos los campos son Obligatorios');
                    } else {
                        $("#questionsenvio_update").submit();
                    }
                });
            </script>
        @endsection
