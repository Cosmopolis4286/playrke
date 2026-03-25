@php
    $menuItems = get_user_menu();
    $userRoleDisplay = get_role_display_name();
@endphp

<aside class="main-sidebar sidebar-dark-primary elevation-3" role="complementary" aria-label="Sidebar principal">
    <a href="{{ url('/dashboard') }}" class="menu-image-play cold" aria-label="Ir al dashboard">
        <img src="{{ asset('img/stam.png') }}" alt="Logo PlayRKE" class="brand-image">
    </a>

    <div class="container-role-user">
        <span>{{ $userRoleDisplay }}</span>
    </div>

    <section class="sidebar mt-2" role="navigation" aria-label="Menú lateral">
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                data-accordion="false">
                
                {{-- Menú dinámico basado en permisos --}}
                @foreach($menuItems as $key => $item)
                    @if(isset($item['submenu']))
                        {{-- Menú con submenús --}}
                        <li class="nav-item has-treeview {{ is_menu_active($item['route'] ?? $key) ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ is_menu_active($item['route'] ?? $key) ? 'active' : '' }}">
                                <i class="nav-icon {{ $item['icon'] }}"></i>
                                <p>
                                    {{ $item['title'] }}
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                @foreach($item['submenu'] as $subkey => $subitem)
                                    @if(can_access_menu($subitem['permission'] ?? null))
                                        <li class="nav-item">
                                            <a href="{{ route($subitem['route']) }}" 
                                               title="{{ $subitem['title'] }}"
                                               class="{{ get_menu_classes($subitem['route']) }}">
                                                <i class="fa-solid fa-caret-right nav-icon"></i>
                                                <p>{{ $subitem['title'] }}</p>
                                            </a>
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        </li>
                    @else
                        {{-- Menú simple --}}
                        @if(can_access_menu($item['permission'] ?? null))
                            <li class="nav-item">
                                <a href="{{ route($item['route']) }}" 
                                   class="{{ get_menu_classes($item['route']) }}"
                                   aria-current="{{ is_menu_active($item['route']) ? 'page' : false }}">
                                    <i class="nav-icon {{ $item['icon'] }}"></i>
                                    <p>{{ $item['title'] }}</p>
                                </a>
                            </li>
                        @endif
                    @endif
                @endforeach

                {{-- Sección de información (siempre visible) --}}
                @php
                    $infoRoutes = ['info.terms', 'info.about', 'info.privacy'];
                    $isInfoActive = request()->route()->is($infoRoutes);
                @endphp

                <li class="nav-item has-treeview {{ $isInfoActive ? 'menu-open' : '' }}">
                    <a href="#" class="{{ $isInfoActive ? 'nav-link active' : 'nav-link' }}">
                        <i class="nav-icon fas fa-book"></i>
                        <p>Información <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('info.about') }}"
                                class="{{ request()->route()->is('info.about') ? 'nav-link active' : 'nav-link' }}">
                                <i class="fa-solid fa-caret-right nav-icon"></i>
                                <p>Acerca de PlayRKE</p>
                            </a>
                        </li>
                        @if(auth()->user()?->hasRole('player'))
                            <li class="nav-item">
                                <a href="{{ route('info.power-of-knowledge') }}"
                                    class="{{ request()->route()->is('info.pds') ? 'nav-link active' : 'nav-link' }}">
                                    <i class="fa-solid fa-caret-right nav-icon"></i>
                                    <p>El Poder del Saber</p>
                                </a>
                            </li>
                        @endif
                        <li class="nav-item">
                            <a href="{{ route('info.terms') }}"
                                class="{{ request()->route()->is('info.terms') ? 'nav-link active' : 'nav-link' }}">
                                <i class="fa-solid fa-caret-right nav-icon"></i>
                                <p>Términos y Condiciones</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('info.privacy') }}"
                                class="{{ request()->route()->is('info.privacy') ? 'nav-link active' : 'nav-link' }}">
                                <i class="fa-solid fa-caret-right nav-icon"></i>
                                <p>Política de Privacidad</p>
                            </a>
                        </li>
                    </ul>
                </li>

                {{-- Cerrar sesión --}}
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
