@extends('layouts.app_rke')
<title>Mi Perfil - PlayRKE</title>
@section('content')
    <div class="row">
        <div class="col-sm-9 col-lg-6" style="max-width:510px; margin:0 auto;">
            <div class="card bg-rke">
                <div class="card-header mt-n2" id="div-active">
                    <div class="user_pf mb-n2">
                        <h4 class=" mb-n1"><b>{{ $user->username }}</b></h4>
                        <span style="font-size:11.6px;">MI PERFIL</span>
                    </div>
                </div>
                <form id="store_perfil" action="{{ route('perfil.update', $user->id) }}" method="post"
                    enctype="multipart/form-data">
                    {{ method_field('PUT') }}
                    {{ csrf_field() }}
                    <div class="sp_body card-body mt-n3">
                        @if ($user->country == 234)
                            @if ($user->cedula == null)
                                <div class="col-12 mb-1 alert alert-success text-center">
                                    <strong>CARGUE TODA LA INFORMACION</strong>
                                </div>
                            @endif
                        @endif
                        <div class="row">
                            <div class="col-6">
                                <label class="lb_in">Nombre</label>
                                @if ($user->name == null)
                                    <input id="name" type="text" name="name" class="form-control"
                                        placeholder="Nombre">
                                @else
                                    <div>{{ $user->name }}</div>
                                @endif
                            </div>
                            <div class="col-6">
                                <label class="lb_in">Apellido</label>
                                @if ($user->apellido == null)
                                    <input id="last_name" type="text" name="last_name" class="form-control"
                                        placeholder="Apellido">
                                @else
                                    <div>{{ $user->apellido }}</div>
                                @endif
                            </div>
                            <div class="col-6">
                                <label class="lb_in">País</label>
                                <div>{{ $wallet->name }}</div>
                            </div>
                            @if ($user->country == 234)
                                <div class="col-6">
                                    <label class="lb_in">Cédula</label>
                                    <div style="display: flex">
                                        @if ($user->cedula == null || $user->cedula == '')
                                            <select class="sl_in form-control" id="type_id" name="type_id">
                                                <option value="">-</option>
                                                <option value="V">V</option>
                                                <option value="E">E</option>
                                            </select>
                                            <div style="display: grid; width:100%;">
                                                <input id="cedula" type="text" name=""
                                                    class="form-control @error('cedula') is-invalid @enderror"
                                                    value="">
                                                @error('cedula')
                                                    <span class="invalid-feedback">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        @else
                                            <div class="form-control_d">{{ $user->cedula }}</div>
                                        @endif
                                    </div>
                                </div>
                            @elseif ($user->country == 43)
                                <div class="col-6">
                                    <label class="lb_in">RUT</label>
                                    <div style="display: flex">
                                        @if ($user->cedula == null || $user->cedula == '')
                                            <div style="display: grid; width:100%;">
                                                <input id="cedula" type="text" name=""
                                                    class="form-control @error('cedula') is-invalid @enderror"
                                                    value="">
                                                @error('cedula')
                                                    <span class="invalid-feedback">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        @else
                                            <div class="form-control_d">{{ $user->cedula }}</div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                            <div class="col-6">
                                <label class="lb_in">E-mail</label>
                                <div>{{ $user->email }}</div>
                            </div>
                            <div class="col-6">
                                <label class="lb_in">Imagen Perfil</label>
                                <div style="display:flex;">
                                    <div class="cont_img_pf">
                                        <img class="img_pf"
                                            @if ($user->foto == '') src="{{ asset('img/fav.ico') }}" 
                                    @else src="imgSystem/imgUsers/{{ $user->foto }}" @endif>
                                    </div>
                                    <input id="foto" type="file" name="foto" class="ml-1 form-control"
                                        value="{{ $user->foto }}">
                                </div>
                            </div>
                            <div class="save_pf col-6" style="margin:0 auto;">
                                <label class="lb_in">&nbsp</label>
                                <button id="save_inf" style="width:100%; font-size:15px;"
                                    class="form-control btn btn-primary profile-button" type="button">
                                    <i class="fa-solid fa-floppy-disk"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endsection
    @section('scripts')
        <script>
            $(document).ready(function() {
                $("#name").on('input', function(evt) {
                    $(this).val($(this).val().replace(/[^a-zA-Z\s]+/g, ""));
                    if (this.value.length > 14)
                        this.value = this.value.slice(0, 14);
                });
                $("#apellido").on('input', function(evt) {
                    $(this).val($(this).val().replace(/[^a-zA-Z\s]+/g, ""));
                    if (this.value.length > 20)
                        this.value = this.value.slice(0, 20);
                });

                $("#save_inf").click(function(e) {
                    if (("#name").val() == '' || $("#last_name").val() == '' ||
                        $("#type_id").val() == '' || $("#cedula").val() == '') {
                        alert('Campos Requeridos');
                    } else {
                        $('#store_perfil').submit();
                    }
                })
            });
        </script>
    @endsection
