@props(['userRolePrefix' => null, 'userRole' => ''])
<aside class="main-sidebar sidebar-dark-primary elevation-3" role="complementary" aria-label="Sidebar principal">
    <a href="{{ url('/dashboard') }}" class="menu-image-play cold" aria-label="Ir al dashboard">
        <img src="{{ asset('img/stam.png') }}" alt="Logo PlayRKE" class="brand-image">
    </a>

    <div class="container-role-user">
        <span>{{ $userRole }}</span>
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
                {{-- <li class="nav-item has-treeview">
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
                </li> --}}

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
                        @if ($userRolePrefix === 'user')
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

                <li class="nav-item">
                    <button type="button"
                            class="nav-link logout-btn w-100 text-left border-0 bg-transparent"
                            data-logout-url="{{ route('logout') }}"
                            data-login-redirect-url="{{ route('login') }}"
                            data-show-confirmation="false"
                            style="background: none !important;">
                        <i class="nav-icon fas fa-sign-out-alt"></i>
                        <p>Cerrar sesión</p>
                    </button>
                </li>
            </ul>
        </nav>
    </section>
</aside>
