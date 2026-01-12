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
    <link rel="stylesheet" href="{{ asset('css/notyf.min.css') }}">
    <link rel="stylesheet" href="{{ asset('template/plugins/fontawesome/css/all.min.css') }}">
    @vite(['resources/css/global/styles.css', 'resources/css/global/auth-pages.css'])
    <title>PlayRKE - @yield('title')</title>
</head>

<body>
    <main id="containerFirst">
        <img id="bgImg" src="{{ asset('img/body/bg_6.jpg') }}">
        <div id="guest-layout__card">
            <div class="guest-layout__logo">
                <img src="{{ asset('img/log.png') }}" alt="PlayRKE">
                <p class="guest-layout__logo-text"></p>
            </div>
            <div class="container-form">@yield('content')</div>
            <div class="login_footer">
                @yield('pre-footer')
                <div class="guest-layout__legal">
                    <a class="guest-layout__legal-link" href="#" data-val="terms">Términos y Condiciones</a>
                    <a class="guest-layout__legal-link" href="#" data-val="about">Quienes Somos</a>
                    <a class="guest-layout__legal-link" href="#" data-val="privacy">Privacidad</a>
                </div>
                <div class="guest-layout__footer-bottom">
                    <div class="mr-3 guest-layout__copyright">PlayRKE © {{ date('Y') }}</div>
                    <div class="guest-layout__socials">
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
        {{-- <x-modal id="app-info" title="PlayRKE © {{ date('Y') }}" /> --}}
    </main>
    <script src="https://www.google.com/recaptcha/api.js"></script>
    <script src="{{ asset('js/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('js/Bootstrap/popper.min.js') }}"></script>
    <script src="{{ asset('js/Bootstrap/bootstrap.min.js') }}"></script>
    <script src="{{ asset('js/notyf.min.js') }}"></script>
    @push('stack-scripts')
        @vite('resources/js/helpers/userInfoModule.ts')
    @endpush
</body>

</html>
