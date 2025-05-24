@extends('layouts.app-home-page')
@section('title', 'Reestablecer Contraseña')
@section('content')
    <form id="pass_chg" method="POST" action="{{ route('password.update') }}">@csrf
        <div class="content_form_items">
            <div class="content_form_info">
                <div class="login_form_item">
                    <i class="e_mc fa-solid fa-envelope"></i>
                    <input id="email" type="email" class="@error('email') is-invalid @enderror" name="email"
                        value="{{ $email ?? old('email') }}">
                </div>
                @error('email')
                    <strong class="er_nw">{{ $message }}</strong>
                @enderror
                <div class="login_form_item">
                    <i class="e_mc fa-solid fa-lock"></i>
                    <input type="hidden" id="van" value="0">
                    <input id="password" type="password" class="pass @error('password') is-invalid @enderror"
                        name="password">
                </div>
                @error('password')
                    <strong class="er_nw">{{ $message }}</strong>
                @enderror
                <div class="login_form_item">
                    <i class="e_mc fa-solid fa-unlock"></i><i class="eye_mc fa-solid fa-eye"></i>
                    <input id="password-confirm" type="password" class="pass" name="password_confirmation">
                </div>
                <div class="login_form_item">
                    <button type="button" id="change_pass">Modificar Contraseña</button>
                </div>
            </div>
        </div>
    </form>
@endsection
@section('pre-footer')
    <div class="login_footer_ck">
        <a href="{{ route('login') }}">
            <span class="pre_tx_link">¿Quieres volver a intentar?</span>
            <span class="sep_r"></span>
            <span class="user_tx_link">Inicia Sesion</span>
        </a>
    </div>
@endsection
@section('script')
    <script>
        $("#change_pass").click(function(e) {
            if ($("#email").val() == '' || $("#password").val() == '' || $("#password-confirm").val() == '') {
                if ($(".notyf__toast").html() == null || $(".notyf__toast").html() == '') {
                    var notyf = new Notyf();
                    notyf.error({
                        message: '<span style="font-size: 0.91em;">' +
                            'Toda la información es requerida</span>',
                        duration: 3450,
                        ripple: true,
                        position: {
                            x: 'center',
                            y: 'bottom',
                        }
                    })
                }
            } else {
                $('#pass_chg').submit();
            }
        })
    </script>
@endsection
