@extends('layouts.app-home-page')
@section('title', 'Registro')

@section('styles')
    @vite('resources/css/rke/register.css')
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card bg-transparent border-0 shadow-sm">
                <div class="card-body">
                    <form id="form-register" action="{{ route('register') }}" method="POST">@csrf
                        {{-- Email --}}
                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fa-solid fa-envelope"></i>
                                </span>
                                <input type="email" name="email" id="email"
                                    class="form-control @error('email') is-invalid @enderror"
                                    placeholder="ejemplo@correo.com" value="{{ old('email') }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Username --}}
                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fa-solid fa-user"></i>
                                </span>
                                <input type="text" name="username" id="username"
                                    class="form-control @error('username') is-invalid @enderror"
                                    placeholder="Tu nombre de usuario" value="{{ old('username') }}" required>
                                @error('username')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- País --}}
                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fa-solid fa-earth-americas"></i>
                                </span>
                                <select name="country" id="country"
                                    class="form-control @error('country') is-invalid @enderror" required>
                                    <option value="">Seleccione su país</option>
                                    @foreach ($countries as $country)
                                        <option value="{{ $country->id }}"
                                            {{ old('country') == $country->id ? 'selected' : '' }}>
                                            {{ $country->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('country')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Contraseña --}}
                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fa-solid fa-lock"></i>
                                </span>
                                <input type="password" name="password" id="password"
                                    class="form-control @error('password') is-invalid @enderror" placeholder="Contraseña"
                                    required>
                                <span class="input-group-text">
                                    <i class="fa-solid fa-eye toggle-password" data-target="password"></i>
                                </span>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Confirmar Contraseña --}}
                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fa-solid fa-unlock"></i>
                                </span>
                                <input type="password" name="password_confirmation" id="password_confirmation"
                                    class="form-control" placeholder="Repetir Contraseña" required>
                                <span class="input-group-text">
                                    <i class="fa-solid fa-eye toggle-password" data-target="password_confirmation"></i>
                                </span>
                            </div>
                        </div>

                        {{-- Aceptar términos --}}
                        <div class="form-group d-flex align-items-center">
                            <div class="mr-2"><input type="checkbox" name="terms" id="terms"></div>
                            <small>
                                Acepto los <a href="#">Términos y Condiciones</a> y la <a href="#">Política
                                    de Privacidad</a> de PlayRKE.
                            </small>
                        </div>

                        {{-- Botón --}}
                        <div class="form-group d-flex justify-content-center">
                            <button type="submit" class="btn btn-primary w-100">
                                Registrarse
                                <span class="spinner-border spinner-border-sm ms-2 d-none" role="status"></span>
                            </button>
                        </div>
                    </form>

                    {{-- Enlace a login --}}
                    <div class="text-center mt-n2 mb-n2">
                        <p class="mb-0">
                            ¿Ya tienes una cuenta?
                            <a href="{{ route('login') }}">Inicia Sesión</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @vite('resources/js/rke/register.js')
@endsection
