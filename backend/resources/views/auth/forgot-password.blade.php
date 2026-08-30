@extends('layouts.layout-external')
@section('title', 'Reestablecer Contraseña')
@section('content')
    <form id="form-forgot-passw" action="{{ route('password.email') }}" method="post">@csrf
        @if (session('status'))
            <div class="auth-form__field">
                <div class="alert alert-success mb-0" role="alert">Hemos enviado el link de reseteo</div>
            </div>
        @endif
        <div class="auth-form__field">
            <i class="e_mc fa-solid fa-envelope"></i>
            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email"
                placeholder="Ingresa tu correo asociado">
        </div>
        @error('email')
            <strong class="er_nw">{{ $message }}</strong>
        @enderror
    </form>
    <div class="auth-form__field">
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
    <script nonce="{{ csp_nonce() }}">
        $(document).on("click", "#btn-forgot-passw", function() {
            if (!$("#email").val()) {
                alert('Ingresa tu correo asociado');
            } else {
                $("#loader-btn").show();
                $('#form-forgot-passw').submit();
            }
        })
    </script>
@endsection
