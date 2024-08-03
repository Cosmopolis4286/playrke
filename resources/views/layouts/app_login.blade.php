<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://playrke.com/">
    <meta property="og:site_name" content="PlayRKE">
    <meta name="description"
        content="Únete ahora. Transforma tu conocimiento, habilidad, tiempo y esfuerzo en dinero mientras juegas online. Gana rápido y retira seguro con PlayRKE.">
    <link rel="icon" href="{{ asset('img/fav.ico') }}" sizes="192x192">
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
    <link rel="stylesheet" href="{{ asset('css/rlg_r.css') }}">
    <link rel="stylesheet" href="{{ asset('css/notyf.min.css') }}">
    <link rel="stylesheet" href="{{ asset('template/plugins/fontawesome/css/all.min.css') }}">
    <title>PlayRKE - @yield('title')</title>
</head>

<body>
    <main id="containerFirst">
        <img id="bgImg" src="{{ asset('img/body/wall9.jpg') }}">
        <div id="containerRKE">
            <div class="login_logo">
                <img src="{{ asset('img/log.png') }}" alt="PlayRKE">
                <p class="login_content_text"></p>
            </div>
            <div class="content_forms">
                @yield('content')
            </div>
            <div class="login_footer">
                @yield('preFooter')
                <div class="info_rke">
                    <a href="#" class="terms">Términos y Condiciones</a>
                    <a href="#" class="about">Quienes Somos</a>
                    <a href="#" class="priv">Privacidad</a>
                </div>
                <input type="hidden" id="van_lgn" value="0">
                <div class="login_footer_link">
                    <div class="mr-3 name_pg">PlayRKE © {{ date('Y') }}</div>
                    <div class="content_rs">
                        <a class="fab fa-facebook" title="Facebook" href="https://www.facebook.com/my.play.rke"
                            target="_blank"></a>
                        <a class="fab fa-instagram" title="Instagram" href="https://www.instagram.com/my.play.rke"
                            target="_blank"></a>
                        <a class="fab fa-telegram" title="Telegram" href="https://t.me/+rV3KWBuuKOtjYzkx"
                            target="_blank"></a>
                    </div>
                </div>
            </div>
        </div>
        @include('layouts/modals/tap')
    </main>

    <script src="https://www.google.com/recaptcha/api.js"></script>
    <script src="{{ asset('js/jquery-3.6.4.min.js') }}"></script>
    <script src="{{ asset('js/rke-functions.js') }}" defer></script>
    <script src="{{ asset('js/Bootstrap/popper.min.js') }}"></script>
    <script src="{{ asset('js/Bootstrap/bootstrap.min.js') }}"></script>
    <script src="{{ asset('js/notyf.min.js') }}"></script>

    <script>
        $(document).on('dragstart', 'img', function(evt) {
            evt.preventDefault();
        });
        
        $(document).ready(function() {
            $(".terms, .about, .priv").click(function() {
                var page = $(this).attr("class");
                var pageUrl = "{{ asset('info/') }}" + page + ".blade.php";

                $.post(pageUrl, function(ext) {
                    $(".modal-body").html(ext);
                    $("#tap").modal("show");
                });
            });

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
    @yield ('scripts')
</body>

</html>
