@extends('layouts.app_login')

@section('title', 'Tu Sitio Para Ganar')

@section('content')
    <form action="{{ route('login') }}" method="post" id="form_lg">
        @csrf
        <div class="content_form_items">
            <div class="content_form_info">
                <!-- Input para email o nombre de usuario -->
                <div class="login_form_item">
                    <i class="e_mc fa-solid fa-user" aria-hidden="true"></i>
                    <input id="user_email" type="text"
                        class="@error('username') is-invalid @enderror @error('email') is-invalid @enderror" name="login"
                        value="{{ old('username') ?: old('email') }}" placeholder="Email/Username"
                        aria-label="Email o Nombre de Usuario">
                </div>
                <!-- Mostrar error de validación -->
                @error('username')
                    <span class="er_nw">{{ $message }}</span>
                @enderror
                @error('email')
                    <span class="er_nw">{{ $message }}</span>
                @enderror
                <!-- Input para contraseña -->
                <div class="login_form_item">
                    <i class="e_mc fa-solid fa-lock" aria-hidden="true"></i>
                    <i id="eyeClose" class="eye_mc fa-solid fa-eye-slash" style="display: none"
                        aria-label="Ocultar contraseña"></i>
                    <input type="password" class="pass" id="pass" name="password" placeholder="Contraseña"
                        aria-label="Contraseña">
                    <i id="eyeOpen" class="eye_mc fa-solid fa-eye" aria-label="Mostrar contraseña"></i>
                </div>
                <!-- Botón para iniciar sesión -->
                <div class="login_form_item">
                    <button type="submit" id="Ingresar">Iniciar Sesión
                        <span id="loader-btn" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"
                            style="display: none"></span>
                    </button>
                </div>
                <!-- Enlace para recuperar contraseña -->
                <div class="login_form_item">
                    <a href="{{ route('password.request') }}" class="forget_password">¿Olvidaste tu contraseña?</a>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('preFooter')
    @if ($register)
        <div class="login_footer_ck">
            <a href="{{ route('register') }}">
                <span class="pre_tx_link">¿No tienes una cuenta?</span>
                <span class="sep_r"></span>
                <span class="user_tx_link">Regístrate</span>
            </a>
        </div>
    @endif
@endsection

@section('scripts')
    <script type="text/javascript">
        $(document).ready(function() {
            // Validación del formulario
            $('#Ingresar').click(function() {
                validate();
            });

            $('#form_lg').keypress(function(e) {
                if (e.which === 13) {
                    validate();
                }
            });

            function validate() {
                let emailOrUsername = $('#user_email').val().trim();
                let password = $('#pass').val().trim();

                if (emailOrUsername === "" || password === "") {
                    if ($(".notyf__toast").length === 0) {
                        new Notyf().error({
                            message: '<span style="font-size: 0.91em;">Toda la información es requerida</span>',
                            duration: 3450,
                            ripple: true,
                            position: {
                                x: 'center',
                                y: 'bottom',
                            }
                        });
                    }
                } else {
                    $("#loader-btn").show();
                    $('#form_lg').submit();
                }
            }
        });
    </script>
@endsection
