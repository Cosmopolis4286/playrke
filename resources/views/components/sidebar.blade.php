@props(['creatorTypePrefix' => null, 'userType' => ''])

@once
    <style>
        aside.main-sidebar {
            background-image: radial-gradient(circle at 50% -20.71%, #352e72 0, #181b50 25%, #02002f 50%, #000011 75%, #000000 100%);
        }

        .menu-image-play.cold .brand-image {
            max-height: 50.7px;
        }

        .cont-user-type {
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #020025;
            letter-spacing: 0.8px;
            padding-right: 5.5px;
        }

        .tx-user-type {
            text-transform: uppercase;
            font-size: 100%;
            color: #e6e3e3;
        }
    </style>
@endonce

<aside class="main-sidebar sidebar-dark-primary elevation-3" role="complementary" aria-label="Sidebar principal">
    <a href="{{ url('/dashboard') }}" class="menu-image-play cold" aria-label="Ir al dashboard">
        <img src="{{ asset('img/stam.png') }}" alt="Logo PlayRKE" class="brand-image">
    </a>

    <div class="cont-user-type">
        <span class="tx-user-type">{{ $userType }}</span>
    </div>

    <section class="sidebar mt-2" role="navigation" aria-label="Menú lateral">
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                data-accordion="false">
                <li class="nav-item has-treeview">
                    <a href="{{ route('dashboard') }}"
                        class="{{ Route::is('dashboard') ? 'nav-link active' : 'nav-link' }}"
                        aria-current="{{ Route::is('dashboard') ? 'page' : false }}">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Tablero</p>
                    </a>
                </li>

                {{-- Slot: contenido personalizado según el usuario --}}
                {{ $slot }}

                {{-- Extras comunes --}}
                <li class="nav-item has-treeview">
                    <a href="#" class="{{ Route::is('info.help-center') ? 'nav-link active' : 'nav-link' }}">
                        <i class="nav-icon fas fa-question-circle"></i>
                        <p>Ayuda <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('info.help-center') }}"
                                class="{{ Route::is('info.help-center') ? 'nav-link active' : 'nav-link' }}">
                                <i class="fa-solid fa-caret-right nav-icon"></i>
                                <p>FAQ</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link" data-toggle="modal" data-target="#report">
                                <i class="fa-solid fa-caret-right nav-icon"></i>
                                <p>Soporte</p>
                            </a>
                        </li>
                    </ul>
                </li>

                @php
                    $infoRoutes = ['info.terms', 'info.about', 'info.privacy'];
                    $isInfoActive = Route::is($infoRoutes);
                @endphp
                <li class="nav-item has-treeview">
                    <a href="#" class="{{ $isInfoActive ? 'nav-link active' : 'nav-link' }}">
                        <i class="nav-icon fas fa-book"></i>
                        <p>Información <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('info.about') }}"
                                class="{{ Route::is('info.about') ? 'nav-link active' : 'nav-link' }}">
                                <i class="fa-solid fa-caret-right nav-icon"></i>
                                <p>Acerca de PlayRKE</p>
                            </a>
                        </li>
                        @if ($creatorTypePrefix === 'user')
                            <li class="nav-item">
                                <a href="{{ route('info.power-of-knowledge') }}"
                                    class="{{ Route::is('info.pds') ? 'nav-link active' : 'nav-link' }}">
                                    <i class="fa-solid fa-caret-right nav-icon"></i>
                                    <p>El Poder del Saber</p>
                                </a>
                            </li>
                        @endif
                        <li class="nav-item">
                            <a href="{{ route('info.terms') }}"
                                class="{{ Route::is('info.terms') ? 'nav-link active' : 'nav-link' }}">
                                <i class="fa-solid fa-caret-right nav-icon"></i>
                                <p>Términos y Condiciones</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('info.privacy') }}"
                                class="{{ Route::is('info.privacy') ? 'nav-link active' : 'nav-link' }}">
                                <i class="fa-solid fa-caret-right nav-icon"></i>
                                <p>Política de Privacidad</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nav-item has-treeview">
                    <a href="#" class="nav-link"
                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="nav-icon fas fa-sign-out-alt"></i>
                        <p>Cerrar sesión</p>
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                </li>

                <li class="mt-2 nav-item text-center">
                    <div title="Versión 1.3.7" style="font-size:12.3px;"><b>V 1.3.7</b></div>
                </li>
            </ul>
        </nav>
    </section>
</aside>
