@extends('layouts.layout-external')
@section('title', 'Confirmar Email')
@section('content')
    <div class="auth-form__field text-center">
        <span class="verify_email_tx">
            ¡Gracias por registrarte! Antes de comenzar, debes verificar tu dirección
            de correo electrónico haciendo clic en el enlance recibido. Revisa la bandeja
            de entrada y la carpeta de spam.Si no recibiste el correo electrónico, con gusto te enviaremos otro.
        </span>
    </div>
    @if (session('status') == 'verification-link-sent')
        <div class="alert alert-succes alert-dismissible fade show" role="alert">
            Se ha enviado un nuevo enlace de verificación al E-mail que proporcionó durante el registro
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif
    <div class="auth-form__field">
        <form id="form-verification" method="POST" action="{{ route('verification.send') }}">@csrf</form>
        <button id="btn-verification" class="tx_btn_sendv" type="button">Reenviar correo de verificación</button>
    </div>
@endsection
@section('scripts')
    <script>
        $(document).on("click", "#btn-verification", function() {
            $("#form-verification").submit();
        });
    </script>
@endsection
