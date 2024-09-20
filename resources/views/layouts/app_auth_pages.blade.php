<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://www.playrke.com/">
    <meta property="og:site_name" content="PlayRKE">
    <meta name="description"
        content="Únete ahora. Transforma tu conocimiento, habilidad, tiempo y esfuerzo en dinero mientras juegas online. Gana rápido y retira seguro con PlayRKE.">
    <link rel="icon" href="{{ asset('img/fav.ico') }}" sizes="192x192">
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
    <link rel="stylesheet" href="{{ asset('css/auth-pages.css') }}">
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
            <div class="container-form">@yield('content')</div>
            <div class="login_footer">
                @yield('pre-footer')
                <div class="info_rke">
                    <a class="info-user-rke" href="#" data-val="terms">Términos y Condiciones</a>
                    <a class="info-user-rke" href="#" data-val="about">Quienes Somos</a>
                    <a class="info-user-rke" href="#" data-val="priv">Privacidad</a>
                </div>
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
            $(".info-user-rke").click(function() {
                const info = $(this).data('val');
                const pageUrl = `{{ route('info-rke', ['page' => ':id']) }}`.replace(':id', info);

                $.ajax({
                    url: pageUrl,
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': $("#token").val()
                    },
                    success: function(response) {
                        $(".modal-body").html(response);
                        $("#tap").modal("show");
                    },
                    error: function() {
                        console.log("No se pudo cargar el contenido.");
                    }
                });
            });
        });
    </script>
    @yield ('scripts')
</body>

</html>
