@extends('layouts.app-home-page')
@section('title', 'Tu Sitio Para Ganar')

@section('scripts')
    <script type="text/javascript">
        $(document).ready(function() {
            // Validación del formulario
            $('#Ingresar').click(function() {
                validate();
            });

            $('#form-login').keypress(function(e) {
                if (e.which === 13) {
                    validate();
                }
            });

            function validate() {
                let emailOrUsername = $('#user-email').val().trim();
                let password = $('#user-psw').val().trim();

                if (!emailOrUsername || !password) {
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
                    $('#form-login').submit();
                }
            }

            $(document).on('click', '.eye_mc', function(e) {
                var passwordInput = $(".pass");
                var isPasswordVisible = passwordInput.attr('type') === 'text';

                passwordInput.attr('type', isPasswordVisible ? 'password' : 'text');
                $("#van").val(isPasswordVisible ? '0' : '1');

                $("#eyeOpen, #eyeClose").toggle();
            });

            $("#user_email, #username, #email, #login").on('input', function(evt) {
                if (this.value.length > 40) {
                    this.value = this.value.slice(0, 40);
                }
            });
        });
    </script>
@endsection
