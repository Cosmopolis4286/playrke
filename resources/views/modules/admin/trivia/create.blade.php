@extends('layouts.layout-online')
<title>Crear Trivia - PlayRKE</title>
@section('fnd-content')
    <div class="content-wrapper">
    @endsection
    @section('content')
        <div class="row">
            <div class="col-md-8" style="margin:0 auto;">
                <div class="card bg-rke">
                    <div class="card-header">
                        <div class="sub_ti-tle">
                            <a href="{{ route('trivia.rooms') }}" title="Regresar"
                                style="position:relative;top:-1px;font-size:18px;">
                                <i class="fas fa-chevron-circle-left"></i>
                            </a>
                            <span style="margin-left:7px; font-size:20px;">Añadir Info. Trivia</span>
                        </div>
                    </div>
                    {{-- FormCrearTrivia --}}
                    <form action="{{ route('trivia.store') }}" method="POST" enctype="multipart/form-data">@csrf
                        <div class="card-body mb-n4">
                            <div class="row">
                                <div class="mb-4 col-sm-6 col-md-5"style="margin-bottom:16px;">
                                    <input type="file" name="imagen" id="inpFile"
                                        value="{{ old('imagen', $data->imagen ?? '') }}">
                                    <div class="image-preview" id="imagePreview">
                                        <img src="" alt="Image Preview" class="img-preview">
                                        <span class="text-img-t">Imagen Trivia (Opcional)</span>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-md-7">
                                    <div class="row">
                                        <div class="col-sm-12 col-md-8">
                                            <label class="lb_in">Nombre</label>
                                            <input type="text" name="nombre"
                                                value="{{ old('nombre', $data->nombre ?? '') }}" class="form-control"
                                                required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="lb_in">%Com. Entrada</label>
                                            <input type="number" name="comision" class="form-control"
                                                value="{{ old('comision', $data->com ?? '') }}" step="0.05"
                                                min="1.5" max="10"
                                                onKeyUp="if(this.value>10){this.value='10';}else if(this.value<1.5){this.value='1.5';}"
                                                required>
                                        </div>
                                        <div class="col-6 col-md-5 col-lg-3">
                                            <label class="lb_in">Moneda</label>
                                            <select class="form-control" name="moneda">
                                                <option value="">-</option required>
                                                @foreach ($coins as $coin)
                                                    <option value="{{ $coin->id }}">{{ $coin->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-6 col-sm-6 col-md-5 col-lg-3">
                                            <label class="lb_in">Precio</label>
                                            <input type="number" id="in" name="precio" class="form-control"
                                                value="{{ old('precio', $data->precio ?? '') }}" step="0.05"
                                                min="0.65" max="20"
                                                onKeyUp="if(this.value>20){this.value='20';}else if(this.value<0.65){this.value='0.65';}"
                                                required>
                                        </div>
                                        <div class="col-6 col-sm-6 col-md-4">
                                            <label class="lb_in">Mín. PJ</label>
                                            <input type="number" class="form-control" placeholder="Mín. Players"
                                                step="5" value="{{ old('min_pj', $data->min_pj ?? '') }}"
                                                min="10" value="10" max="100" name="min_pj"
                                                onKeyUp="if(this.value>200){this.value='200';}else if(this.value<10){this.value='10';}">
                                        </div>

                                        <div class="save_pf col-6 col-md-3 col-lg-2">
                                            <label class="lb_in">&nbsp</label>
                                            <div>
                                                <button id="enviardatos" class="mt-1 btn btn-primary profile-button">
                                                    <i class="fa-solid fa-floppy-disk"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <hr class="mb-n1">
                                    <div class="row">
                                        <div class="col-12 mt-1"
                                            style="display:flex; justify-content:center; align-items:center;">
                                            <span class="mr-2 mt-2">
                                                <input type="checkbox" name="check" id="check" value="1"
                                                    onchange="javascript:showContent()">
                                            </span>
                                            <label class="lb_in">¿Generar códigos-venta?</label>
                                            <span class="ml-3 mt-2">
                                                <input type="number" class="form-control" id="cods_tri"
                                                    style="max-width:100px;" min="60" max="200"
                                                    onKeyUp="if(this.value>200){this.value='200';}else if(this.value<60){this.value='60';}"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @section('validate')
    @endsection
    <script>
        const inpFile = document.getElementById("inpFile");
        const previewContainer = document.getElementById("imagePreview");
        const previewImage = previewContainer.querySelector(".img-preview");
        const textrivia = previewContainer.querySelector(".text-img-t");

        inpFile.addEventListener("change", function() {
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
        var el = document.getElementById("in")
        el.value = el.valueAsNumber.toFixed(2)
        el.addEventListener('input', function(event) {
            event.target.value = event.target.valueAsNumber.toFixed(2)
        });

        function showContent() {
            element = document.getElementById("cods_tri");
            check = document.getElementById("check");
            if (check.checked) {
                element.style.display = 'block';
            } else {
                element.style.display = 'none';
            }
        }
    </script>
@endsection
