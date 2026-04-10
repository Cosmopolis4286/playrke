@extends('layouts.app_rke')
@section('title', $title)
@section('content')
    <div class="col-12 col-md-10 col-lg-9 m-auto">
        <div class="card">
            @include('includes.card-header')
            <div class="card-body">
                <form id="form-trivia" action="{{ route('trivia.create') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <!-- Sección de Imagen -->
                        <div class="col-sm-6 col-lg-5">
                            <input type="file" name="imagen" id="inpFile"
                                value="{{ old('imagen', $data->imagen ?? '') }}">
                            <div class="image-preview" id="imagePreview">
                                <img alt="Vista previa de la imagen" class="img-preview">
                                <span class="text-img-t">Imagen Trivia (Opcional)</span>
                            </div>
                        </div>

                        <!-- Sección de Detalles -->
                        <div class="col-sm-6 col-lg-7">
                            <div class="row">
                                <!-- Descripción -->
                                <div class="form-group col-md-8">
                                    <label for="title">Título</label>
                                    <input type="text" name="title" class="form-control"
                                        value="{{ old('title', $data->description ?? '') }}">
                                </div>

                                <!-- Comisión de Entrada -->
                                <div class="form-group col-md-4">
                                    <label for="commn_room">%Com. Entrada*</label>
                                    <div class="input-group mb-3">
                                        <input type="number" id="commn-room" name="commn_room" class="form-control"
                                            value="{{ old('commn_room', $data->com ?? '') }}" step="0.05" min="1.5"
                                            max="10" oninput="this.value = Math.max(1.5, Math.min(10, this.value))"
                                            required>
                                        <div class="input-group-append">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Precio -->
                                <div class="form-group col-md-6">
                                    <label for="price">Precio*</label>
                                    <div class="input-group">
                                        <select class="custom-select" id="price-type" name="price_type" required>
                                            <option selected value="USDT">USDT</option>
                                            <option value="LIVES">VIDAS</option>
                                        </select>
                                        <div class="input-group-append">
                                            <input type="number" id="price" name="price" class="form-control"
                                                value="{{ old('price', $data->price ?? '') }}" step="0.05" min="0.65"
                                                max="20"
                                                oninput="this.value = Math.max(0.65, Math.min(20, this.value))" required>
                                        </div>
                                    </div>
                                </div>

                                <!-- Mínimo de Jugadores -->
                                <div class="form-group col-md-6">
                                    <label for="min_players">Mín. PJ*</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="fa-solid fa-users"></i>
                                            </span>
                                        </div>
                                        <input type="number" id="min-players" name="min_players" class="form-control"
                                            value="{{ old('min_players', $data->min_pj ?? '') }}" step="5"
                                            min="10" max="200"
                                            oninput="this.value = Math.max(10, Math.min(200, this.value))">
                                    </div>
                                </div>
                            </div>

                            <hr class="mt-n1">

                            <!-- Generar Códigos-Venta -->
                            <div class="row mt-1">
                                <div class="col-12 d-flex justify-content-center align-items-center">
                                    <input type="checkbox" name="check" id="check" value="1"
                                        onchange="showContent()" class="mr-2">
                                    <label for="check" class="mb-0">¿Generar códigos-venta?</label>
                                    <input type="number" id="sale-codes" class="form-control ml-3"
                                        style="max-width: 100px;" min="50" max="250" step="10"
                                        oninput="this.value = Math.max(60, Math.min(200, this.value))">
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="card-footer text-muted">
                <!-- Botón Guardar -->
                <div class="col-auto text-center">
                    <button type="button" id="btn-create-trivia" class="btn btn-primary col-auto">
                        <i class="fa-solid fa-floppy-disk"></i> Crear Trivia
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const inpFile = document.getElementById("inpFile");
            const previewContainer = document.getElementById("imagePreview");
            const previewImage = previewContainer.querySelector(".img-preview");
            const textTrivia = previewContainer.querySelector(".text-img-t");
            const salesCodes = document.getElementById("sale-codes");
            const check = document.getElementById("check");

            // Mostrar vista previa de imagen
            inpFile.addEventListener("change", () => {
                const file = inpFile.files[0];

                if (file) {
                    const reader = new FileReader();
                    textTrivia.style.display = "none";
                    previewImage.style.display = "block";

                    reader.onload = (e) => {
                        previewImage.src = e.target.result;
                    };

                    reader.readAsDataURL(file);
                } else {
                    textTrivia.style.display = "block";
                    previewImage.style.display = "none";
                    previewImage.src = "";
                }
            });

            // Mostrar u ocultar el campo de códigos
            check.addEventListener("change", () => {
                salesCodes.style.display = check.checked ? "block" : "none";
            });
        });

        $(document).on('click', '#btn-create-trivia', function() {
            submitForm('trivia', ['commn-room', 'price-type', 'price', 'min-players']);
        });
    </script>
@endsection
