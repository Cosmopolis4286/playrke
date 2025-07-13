@extends('layouts.layout-online')
<title>Crear Trivia - PlayRKE</title>
@section('fnd-content')
    <div class="content-wrapper">
    @endsection
    @section('content')
        <div class="row">
            <div class="col-md-10" style="margin:0 auto;">
                {{-- FormCrearTrivia --}}
                <div class="card bg-rke">
                    <div class="card-header" style="font-size:18px;">
                        <a href="{{ route('trivia.rooms') }}" title="Regresar"
                            style="position:relative;top:-1px;font-size:18px;">
                            <i class="fas fa-chevron-circle-left"></i>
                        </a>
                        questions │ Respuestas
                        <a href="#" class="" style="margin-right:13px;" data-target="#tap" data-toggle="modal">
                            <i class="fas fa-plus-square" style="font-size:24px;"></i>
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12">
                                <div class="table-responsive">
                                    <table id="t_1" class="table table-striped table-bordered  nowrap"
                                        style="width:100%">
                                        <thead>
                                            <tr>
                                                <th>question</th>
                                                <th>Imagen</th>
                                                <th>Correct</th>
                                                <th>OPC</th>
                                                <th>OPC</th>
                                                <th>OPC</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($question as $key => $date)
                                                <tr>
                                                    <td>{{ $date->question }}</td>
                                                    <td>
                                                        <img class="img-table"
                                                            src="{{ asset('imgSystem/imgquestions/' . $date->img) }}">
                                                    </td>
                                                    <td>{{ $date->resp_correct }}</td>
                                                    <td>{{ $date->opc_1 }}</td>
                                                    <td>{{ $date->opc_2 }}</td>
                                                    <td>{{ $date->opc_3 }}</td>
                                                    <td>
                                                        <a href="#" class="btn bg-cyan inf" data-target="#edit_p"
                                                            data-toggle="modal" data-id='{{ $date->id }}'
                                                            data-question="{{ $date->question }}"
                                                            data-imagen="{{ asset('imgSystem/imgquestions/' . $date->img) }}"
                                                            data-file="{{ $date->img }}"
                                                            data-rcorrecta="{{ $date->resp_correct }}"
                                                            data-opcion1="{{ $date->opc_1 }}"
                                                            data-opcion2="{{ $date->opc_2 }}"
                                                            data-opcion3="{{ $date->opc_3 }}">
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
                <form action="{{ route('triv_in.insert_quest') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal fade" id="tap" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
                        aria-hidden="true">
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
                                        <div class="col-12">
                                            <label class="lb_in">Nueva question</label>
                                            <input type="text" name="quest" class="form-control" required>
                                        </div>
                                        <div class="col-12">
                                            <label class="lb_in">Respuesta Correcta</label>
                                            <input type="text" name="correct" class="form-control" required>
                                        </div>
                                        <hr>
                                        <div class="mt-3 col-12 center-img">
                                            <input type="file" name="imagen" id="inpImg"
                                                value="{{ old('imagen_question', $data->imagen ?? '') }}" required>
                                            <div class="image-preview-p" id="imagePreview-p">
                                                <img src="" alt="Image Preview" class="img-preview">
                                                <span class="text-img-t">Img question</span>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <label class="lb_in">Opc. Incorrecta</label>
                                            <input type="text" name="opc_1" class="form-control" required>
                                        </div>
                                        <div class="col-12">
                                            <label class="lb_in">Opc. Incorrecta</label>
                                            <input type="text" name="opc_2" class="form-control" required>
                                        </div>
                                        <div class="col-12">
                                            <label class="lb_in">Opc. Incorrecta</label>
                                            <input type="text" name="opc_3" class="form-control" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal"
                                        id="close">Cerrar</button>
                                    <button type="submit" class="bt_ke btn btn-secondary"
                                        id="envquestions">Guardar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
                {{-- Insert Quest --}}

                {{-- Edit-Quest --}}
                <form action="{{ route('triv_in.edit_quest') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal fade" id="edit_p" tabindex="-1" role="dialog"
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
                                    <div class="">
                                        <input type="hidden" id="id_p" name="id_p" class="form-control"
                                            required>
                                        <label class="lb_in">question</label>
                                        <input type="text" id="quest_e" name="quest_e" class="form-control"
                                            required>
                                    </div>
                                    <div class="">
                                        <label class="lb_in">Respuesta Correcta</label>
                                        <input type="text" id="correct" name="correct" class="form-control"
                                            required>
                                    </div>
                                    <hr>
                                    <div class="center-img">
                                        <input type="file" name="imgQ_e" value="">
                                        <div class="image-preview-p" id="imagePreview-p-e">
                                            <img id="imgQ" class="img-preview-e">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="lb_in">Opc. Incorrecta</label>
                                        <input type="text" name="opc_1e" class="form-control" id="opc_1e"
                                            required>
                                    </div>
                                    <div>
                                        <label class="lb_in">Opc. Incorrecta</label>
                                        <input type="text" name="opc_2e" class="form-control"
                                            id="opc_2e"required>
                                    </div>
                                    <div>
                                        <label class="lb_in">Opc. Incorrecta</label>
                                        <input type="text" name="opc_3e" class="form-control" id="opc_3e"
                                            required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary mr-2" data-dismiss="modal"
                                        id="close">Cerrar</button>
                                    <button type="submit" class="bt_ke btn btn-secondary"
                                        id="envquestions_update">Editar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endsection
    @section('scripts')
        <script>
            /*const inpFile = document.getElementById("inpFile");
                                            const previewContainer = document.getElementById("imagePreview");
                                            const previewImage = previewContainer.querySelector(".img-preview");
                                            const textrivia = previewContainer.querySelector(".text-img-t");*/

            $(".inf").click(function() {
                $("#id_p").val($(this).data('id'));
                $("#quest_e").val($(this).data('question'));
                $("#imgQ").attr("src", $(this).data('imagen'));
                $("#imgQ").val($(this).data('file'));
                $("#correct").val($(this).data('rcorrecta'));
                $("#opc_1e").val($(this).data('opcion1'));
                $("#opc_2e").val($(this).data('opcion2'));
                $("#opc_3e").val($(this).data('opcion3'));
            });
        </script>
    @endsection
