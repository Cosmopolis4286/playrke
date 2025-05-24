@extends('layouts.app-auth-pages')
@section('title', 'Reestablecer Contraseña')
@section('content')
    <form id="form-forgot-passw" action="{{ route('password.email') }}" method="post">@csrf
        @if (session('status'))
            <div class="login_form_item">
                <div class="alert alert-success mb-0" role="alert">Hemos enviado el link de reseteo</div>
            </div>
        @endif
        <div class="login_form_item">
            <i class="e_mc fa-solid fa-envelope"></i>
            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email"
                placeholder="Ingresa tu correo asociado">
        </div>
        @error('email')
            <strong class="er_nw">{{ $message }}</strong>
        @enderror
    </form>
    <div class="login_form_item">
        <button type="button" id="btn-forgot-passw">Enviar link de reseteo
            <span id="loader-btn" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"
                style="display: none"></span>
        </button>
    </div>
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
@section('scripts')
    <script>
        $(document).on("click", "#btn-forgot-passw", function() {
            if (!$("#email").val()) {
                if (!$(".notyf__toast").html() || !$(".notyf__toast").html()) {
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
                $("#loader-btn").show();
                $('#form-forgot-passw').submit();
            }
        })
    </script>
@endsection
