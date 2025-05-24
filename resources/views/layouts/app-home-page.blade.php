<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <meta property="og:type" content="website" />
    <meta property="og:url" content="{{ url()->current() }}" />
    <meta property="og:site_name" content="PlayRKE" />
    <meta property="og:title" content="PlayRKE" />
    <meta property="og:description"
        content="Únete ahora. Transforma tu conocimiento, habilidad, tiempo y esfuerzo en dinero mientras juegas online. Gana rápido y retira seguro con PlayRKE." />
    <meta name="description"
        content="Juega online y gana dinero. Regístrate en PlayRKE, juega y retira tus ganancias de forma segura y rápida." />

    <link rel="icon" href="{{ asset('img/fav.ico') }}" sizes="192x192" />

    {{-- CSS --}}
    <link rel="stylesheet" href="{{ asset('css/notyf.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('template/plugins/fontawesome/css/all.min.css') }}" />
    @vite(['resources/css/rke/styles.css', 'resources/css/rke/home-page.css'])

    @stack('styles')

    <title>PlayRKE - @yield('title', 'Inicia sesión')</title>
</head>

<body class="home-page">
    <img class="home-page__background" src="{{ asset('img/body/wall9.jpg') }}" alt="Fondo de PlayRKE" loading="lazy" />

    <main class="home-page__content">
        <header class="home-page__header">
            <img class="home-page__logo" src="{{ asset('img/log.png') }}" alt="Logo PlayRKE" loading="lazy" />
            <p class="home-page__subtitle"></p>
        </header>

        <section class="home-page__form bg-danger" aria-labelledby="login-form-title">
            <form action="{{ route('login') }}" method="POST" class="login-form" novalidate>
                @csrf

                {{-- Usuario o Email --}}
                <div class="login-form__group">
                    <i class="login-form__icon--left fa-solid fa-user" aria-hidden="true"></i>
                    <input type="text" name="login" id="login" placeholder="Email o Nombre de Usuario"
                        aria-label="Email o Nombre de Usuario" value="{{ old('login') }}"
                        class="login-form__input @error('login') is-invalid @enderror" required autofocus />
                    @error('login')
                        <span class="login-form__error" role="alert">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Contraseña --}}
                <div class="login-form__group">
                    <i class="login-form__icon--left fa-solid fa-lock" aria-hidden="true"></i>
                    <input type="password" name="password" id="password-input" placeholder="Contraseña"
                        aria-label="Contraseña" class="login-form__input @error('password') is-invalid @enderror"
                        required />
                    <i class="login-form__icon--toggle fa-solid fa-eye" aria-label="Mostrar contraseña" role="button"
                        tabindex="0" aria-pressed="false"></i>
                    @error('password')
                        <span class="login-form__error" role="alert">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Botón --}}
                <div class="login-form__group">
                    <button type="submit" class="login-form__submit" aria-live="polite" aria-busy="false">
                        Iniciar Sesión
                        <span class="spinner-border spinner-border-sm visually-hidden" role="status"
                            aria-hidden="true"></span>
                    </button>
                </div>

                {{-- Olvidé mi contraseña --}}
                <div class="login-form__group">
                    <a href="{{ route('password.request') }}" class="login-form__forgot-password">
                        ¿Olvidaste tu contraseña?
                    </a>
                </div>
            </form>
        </section>

        {{-- Footer --}}
        <footer class="home-page__footer">
            <a href="{{ route('register') }}">
                <p>¿No tienes una cuenta? Regístrate</p>
            </a>

            <nav class="home-page__nav">
                <a class="home-page__link" href="#" data-val="terms">Términos y Condiciones</a>
                <a class="home-page__link" href="#" data-val="about">Quiénes Somos</a>
                <a class="home-page__link" href="#" data-val="privacy">Privacidad</a>
            </nav>

            <div class="home-page__bottom">
                <div class="home-page__copyright">
                    PlayRKE © {{ date('Y') }}
                </div>
                <div class="home-page__social">
                    <a class="home-page__social-icon fab fa-facebook" href="https://www.facebook.com/my.play.rke"
                        target="_blank" rel="noopener" title="Facebook"></a>
                    <a class="home-page__social-icon fab fa-instagram" href="https://www.instagram.com/my.play.rke"
                        target="_blank" rel="noopener" title="Instagram"></a>
                    <a class="home-page__social-icon fab fa-telegram" href="https://t.me/+rV3KWBuuKOtjYzkx"
                        target="_blank" rel="noopener" title="Telegram"></a>
                </div>
            </div>
        </footer>
    </main>

    {{-- Modal --}}
    @include('layouts.modals.tap')

    {{-- Scripts --}}
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <script src="{{ asset('js/jquery-3.6.4.min.js') }}"></script>
    <script src="{{ asset('js/Bootstrap/popper.min.js') }}"></script>
    <script src="{{ asset('js/Bootstrap/bootstrap.min.js') }}"></script>
    <script src="{{ asset('js/notyf.min.js') }}"></script>
    <script src="{{ asset('js/rke/home-page.js') }}"></script>

    @stack('scripts')
    @yield('scripts')

    {{-- Scripts internos --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toggleIcon = document.querySelector('.login-form__icon--toggle');
            const passwordInput = document.getElementById('password-input');
            const loginForm = document.querySelector('.login-form');
            const spinner = loginForm.querySelector('.spinner-border');
            const submitButton = loginForm.querySelector('button[type="submit"]');

            // Mostrar/Ocultar contraseña
            function togglePassword() {
                const isPassword = passwordInput.type === 'password';
                passwordInput.type = isPassword ? 'text' : 'password';
                toggleIcon.classList.toggle('fa-eye');
                toggleIcon.classList.toggle('fa-eye-slash');
                toggleIcon.setAttribute('aria-label', isPassword ? 'Ocultar contraseña' : 'Mostrar contraseña');
                toggleIcon.setAttribute('aria-pressed', String(isPassword));
            }

            toggleIcon.addEventListener('click', togglePassword);
            toggleIcon.addEventListener('keydown', (e) => {
                if (['Enter', ' '].includes(e.key)) {
                    e.preventDefault();
                    togglePassword();
                }
            });

            // Spinner al enviar formulario
            loginForm.addEventListener('submit', () => {
                spinner.classList.remove('visually-hidden');
                submitButton.setAttribute('aria-busy', 'true');
                submitButton.disabled = true;
            });

            // Mostrar errores con Notyf si existen
            @if ($errors->any())
                const notyf = new Notyf({
                    duration: 4000,
                    position: {
                        x: 'right',
                        y: 'top'
                    }
                });
                @foreach ($errors->all() as $error)
                    notyf.error(@json($error));
                @endforeach
            @endif
        });
    </script>
</body>

</html>
