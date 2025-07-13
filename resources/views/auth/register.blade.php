@extends('layouts.layout-external')
@section('title', 'Registro')
@section('content')
    <form action="{{ route('register') }}" method="post" id="form-register">@csrf
        <div id="grupo__email">
            <div class="login_form_item">
                <label for="email" class="sr-only">E-mail</label>
                <i class="e_mc fa-solid fa-envelope"></i>
                <input type="email" class="@error('email') is-invalid @enderror" id="email" placeholder="E-mail"
                    name="email" value="{{ old('email') }}" required>
                @error('email')
                    <span class="invalid-feedback er_nw">{{ $message }}</span>
                @enderror
            </div>
            <p class="formulario__input-error">Ingrese un E-mail válido.</p>
        </div>
        <div id="grupo__username">
            <div class="login_form_item">
                <label for="username" class="sr-only">Nombre de Usuario</label>
                <i class="e_mc fa-solid fa-user"></i>
                <input type="text" class="@error('username') is-invalid @enderror" id="username"
                    placeholder="Nombre de Usuario" name="username" value="{{ old('username') }}" required>
                @error('username')
                    <span class="invalid-feedback er_nw">{{ $message }}</span>
                @enderror
            </div>
            <p class="formulario__input-error">Su nombre de usuario debe tener de 4-16 caracteres</p>
        </div>
        <div class="login_form_item">
            <label for="country" class="sr-only">País</label>
            <i class="e_mc fa-solid fa-earth-americas"></i>
            <i class="eye_down fa-solid fa-chevron-down"></i>
            <div id="dropdown_select">Seleccione su país</div>
            <input type="hidden" name="country" id="in_country" required>
            <div id="dropdown_list">
                @foreach ($countries as $country)
                    <div id="item_{{ $country->id }}" class="dropdown_list_item"
                        onmousedown="country({{ $country->id }})">{{ $country->name }}</div>
                @endforeach
            </div>
        </div>
        <div id="grupo__password">
            <div class="login_form_item">
                <label for="password" class="sr-only">Contraseña</label>
                <i class="e_mc fa-solid fa-lock"></i>
                <input type="hidden" id="van" value="0">
                <input type="password" class="pass @error('password') is-invalid @enderror" placeholder="Contraseña"
                    name="password" id="password" required>
                @error('password')
                    <span class="invalid-feedback er_nw">{{ $message }}</span>
                @enderror
            </div>
            <p class="formulario__input-error">Su contraseña tiene que ser mínimo de 8 caracteres.</p>
        </div>
        <div class="login_form_item">
            <label for="password2" class="sr-only">Repetir Contraseña</label>
            <i class="e_mc fa-solid fa-unlock"></i>
            <i class="eye_mc fa-solid fa-eye"></i>
            <input id="password2" type="password" class="pass" name="password_confirmation"
                placeholder="Repetir Contraseña" required>
        </div>
        <p class="er_nw" id="no_pass" style="display: none">Las contraseñas no son iguales.</p>
        <div class="login_form_item_check" id="grupo__terminos">
            <input type="checkbox" name="politicas" id="politicas" required>
            <label for="politicas">
                <div id="acep_term">Acepto los <a href="#" class="terms">Términos y Condiciones </a> y
                    autorizo mis datos e información de acuerdo a la <a href="#" class="priv">Política
                        de
                        Privacidad </a> de PlayRKE
                </div>
            </label>
        </div>
        <div class="login_form_item">
            <button id="btn-register" class="btn btn-primary" type="submit">Registrarse
                <span id="loader-btn-register" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"
                    style="display: none"></span>
            </button>
        </div>
    </form>
@endsection
@section('pre-footer')
    <div class="login_footer_ck">
        <a href="{{ route('login') }}">
            <span class="pre_tx_link">¿Ya tienes una cuenta?</span>
            <span class="sep_r"></span>
            <span class="user_tx_link">Inicia Sesion</span>
        </a>
    </div>
@endsection
@section('scripts')
    @vite('resources/js/rke/register.js')
    <script>
        const list = $('#dropdown_list');
        const dropdownSelect = $('#dropdown_select');

        dropdownSelect.click(function() {
            list.toggle();
        });

        $(document).click(function(event) {
            if (!$(event.target).closest('#dropdown_list, #dropdown_select').length) {
                list.hide();
            }
        });

        function country(c) {
            const item = $('#item_' + c).html();
            dropdownSelect.html(item);
            $('#in_country').val(c);
            list.hide();
        }
    </script>
@endsection
