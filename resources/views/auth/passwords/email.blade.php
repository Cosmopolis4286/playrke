@extends('layouts.app_login')
@section('title', 'Reestablecer Contraseña')
@section('content')
    <form id="pass_rest" action=" {{ route('password.email') }}" method="post">@csrf
        <div class="content_form_items">
            <div class="content_form_info">
                @if (session('status'))
                    <div class="login_form_item">
                        <div class="alert alert-success mb-0" role="alert">Hemos enviado el link de reseteo</div>
                    </div>
                @endif
                <div class="login_form_item">
                    <i class="e_mc fa-solid fa-envelope"></i>
                    <input id="email_rest" type="email" class="form-control @error('email') is-invalid @enderror"
                        name="email" placeholder="Ingresa tu correo asociado">
                </div>
                @error('email')
                    <strong class="er_nw">{{ $message }}</strong>
                @enderror
                <div class="login_form_item">
                    <button type="button" id="rest">Enviar link de reseteo</button>
                </div>
            </div>
        </div>
    </form>
@endsection
@section('preFooter')
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
        $("#rest").click(function(e) {
            if ($("#email_rest").val() == '') {
                if ($(".notyf__toast").html() == null || $(".notyf__toast").html() == '') {
                    var notyf = new Notyf();
                    notyf.error({
                        message: '<span style="font-size: 0.91em;">' +
                            'Ingresa tu correo asociado</span>',
                        duration: 3450,
                        ripple: true,
                        position: {
                            x: 'center',
                            y: 'bottom',
                        }
                    })
                }
            } else {
                $('#pass_rest').submit();
                setTimeout(function() {
                    window.location.href = "{{ route('login') }}";
                }, 4000);
            }
        })
    </script>
@endsection
