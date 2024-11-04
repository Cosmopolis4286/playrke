<aside class="main-sidebar sidebar-dark-primary elevation-3"
    style="background-image: radial-gradient(circle at 50% -20.71%, #352e72 0, #181b50 25%, #02002f 50%, #000011 75%, #000000 100%);">
    <a href="{{ url('/dashboard') }}" class="menu-image-play cold">
        <img src="{{ asset('img/stam.png') }}" alt="Logo PlayRKE" class="brand-image" style="max-height: 50.7px;">
    </a>
    <style>
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
    <div class="cont-user-type">
        <span class="tx-user-type">@yield('user-type')</span>
    </div>
    <section class="sidebar" style="margin-top:2px;">
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                data-accordion="false">
                <li class="nav-item has-treeview ">
                    <a href="{{ route('dashboard') }}" class="nav-link  {{ !Route::is('dashboard') ?: 'active' }}">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Tablero</p>
                    </a>
                </li>
                @yield('body')
                {{-- Extras --}}
                <li class="nav-item">
                    <a href="#" class="nav-link {{ !Route::is('info.help-center') ?: 'active' }}">
                        <i class="nav-icon fas fa-question-circle"></i>
                        <p>Ayuda <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('info.help-center') }}"
                                class="nav-link {{ Route::is('info.help-center') ? 'active' : '' }}">
                                <i class="fa-solid fa-caret-right nav-icon"></i>
                                <p>FAQ</p>
                            </a>
                        </li>
                        <li class="nav-item ">
                            <a href="#" class="nav-link" data-toggle="modal" data-target="#report">
                                <i class="fa-solid fa-caret-right nav-icon"></i>
                                <p>Soporte</p>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item has-treeview">
                    <a href="#"
                        class="nav-link {{ !Route::is('info.terms', 'info.about', 'info.privacy') ?: 'active' }}">
                        <i class="nav-icon fas fa-book"></i>
                        <p>Información
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('info.about') }}" title="Acerca de PlayRKE"
                                class="nav-link {{ !Route::is('info.about') ?: 'active' }}">
                                <i class="fa-solid fa-caret-right nav-icon"></i>
                                <p>Acerca de PlayRKE</p>
                            </a>
                        </li>
                        @if ($creatorTypePrefix === 'user')
                            <li class="nav-item">
                                <a href="{{ route('info.power-of-knowledge') }}" title="El Poder del Saber"
                                    class="nav-link {{ !Route::is('info.pds') ?: 'active' }}">
                                    <i class="fa-solid fa-caret-right nav-icon"></i>
                                    <p>El Poder del Saber</p>
                                </a>
                            </li>
                        @endif
                        <li class="nav-item">
                            <a href="{{ route('info.terms') }}" title="Términos y Condiciones"
                                class="nav-link {{ !Route::is('info.terms') ?: 'active' }}">
                                <i class="fa-solid fa-caret-right nav-icon"></i>
                                <p>Términos y Condiciones</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('info.privacy') }}" title="Política de Privacidad"
                                class="nav-link {{ !Route::is('info.privacy') ?: 'active' }}">
                                <i class="fa-solid fa-caret-right nav-icon"></i>
                                <p>Política de Privacidad</p>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item has-treeview">
                    <a class="nav-link" href="#"
                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="nav-icon fas fa-sign-out-alt"></i>
                        <p>Cerrar sesión</p>
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                </li>

                <li class="mt-2 nav-item has-treeview" align="center">
                    <div title="Versión 1.3.5" style="font-size:12.3px;"><b>V 1.3.5</b></div>
                </li>
            </ul>
        </nav>
    </section>
</aside>
