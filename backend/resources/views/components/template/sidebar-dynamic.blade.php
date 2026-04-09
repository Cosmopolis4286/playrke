@php
    use App\Services\Menu\SidebarMenuService;

    $menuService = app(SidebarMenuService::class);
    $menuItems = $menuService->generateMenu();
    $userRoleDisplay = $menuService->getUserRoleDisplay();
    $authUser     = auth()->user();
    $sidebarPhoto = $authUser?->profile_photo_url
        ?? ($authUser?->foto ? asset('img/users/' . $authUser->foto) : null);
    $sidebarName  = $authUser?->username ?? $authUser?->name ?? 'Usuario';
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
                        @php
                            $isSubmenuActive = $menuService->hasActiveSubmenu($item['submenu']);
                        @endphp
                        <li class="nav-item has-treeview {{ $isSubmenuActive ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ $isSubmenuActive ? 'active' : '' }}">
                                <i class="nav-icon {{ $item['icon'] }}"></i>
                                <p>
                                    {{ $item['title'] }}
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                @foreach($item['submenu'] as $subkey => $subitem)
                                    <li class="nav-item">
                                        <a href="{{ route($subitem['route']) }}" 
                                           title="{{ $subitem['title'] }}"
                                           class="{{ $menuService->getMenuItemClasses($subitem['route']) }}">
                                            <i class="fa-solid fa-caret-right nav-icon"></i>
                                            <p>{{ $subitem['title'] }}</p>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @else
                        {{-- Menú simple --}}
                        <li class="nav-item">
                            <a href="{{ route($item['route']) }}" 
                               class="{{ $menuService->getMenuItemClasses($item['route']) }}"
                               aria-current="{{ $menuService->isActive($item['route']) ? 'page' : false }}">
                                <i class="nav-icon {{ $item['icon'] }}"></i>
                                <p>{{ $item['title'] }}</p>
                            </a>
                        </li>
                    @endif
                @endforeach

                {{-- Centro de Ayuda --}}
                <li class="nav-item">
                    <a href="{{ route('info.helpCenter') }}"
                       class="{{ request()->routeIs('info.helpCenter') ? 'nav-link active' : 'nav-link' }}">
                        <i class="nav-icon fas fa-circle-question"></i>
                        <p>Centro de Ayuda</p>
                    </a>
                </li>

            </ul>
        </nav>
    </section>

    {{-- Pie del sidebar: info del usuario + cerrar sesión --}}
    <div class="sidebar-user-footer">
        <div class="sidebar-user-footer__info">
            <div class="sidebar-user-footer__avatar">
                @if($sidebarPhoto)
                    <img src="{{ $sidebarPhoto }}" alt="{{ $sidebarName }}" class="sidebar-user-footer__photo">
                @else
                    <i class="fas fa-user-circle"></i>
                @endif
            </div>
            <div class="sidebar-user-footer__details">
                <span class="sidebar-user-footer__name">{{ $sidebarName }}</span>
                <span class="sidebar-user-footer__role">{{ $userRoleDisplay }}</span>
            </div>
        </div>
        <button type="button"
                class="sidebar-user-footer__logout logout-btn"
                data-logout-url="{{ route('logout') }}"
                data-login-redirect-url="{{ route('login') }}"
                data-show-confirmation="false"
                title="Cerrar sesión">
            <i class="fas fa-sign-out-alt"></i>
        </button>
    </div>
</aside>
