@php
    use App\Services\Menu\SidebarMenuService;

    $menuService = app(SidebarMenuService::class);
    $menuItems = $menuService->generateMenu();
    $userRoleDisplay = $menuService->getUserRoleDisplay();
    $authUser     = auth()->user();
    $favorites    = $authUser ? $menuService->getFavorites($authUser) : [];
    $favoriteKeys = collect($favorites)->pluck('item_key')->all();
    $sidebarPhoto = $authUser?->profile_photo_url
        ?? ($authUser?->foto ? asset('img/users/' . $authUser->foto) : asset('img/fav.ico'));
    $sidebarName  = $authUser?->username ?? $authUser?->name ?? 'Usuario';
@endphp

<aside class="main-sidebar sidebar-dark-primary elevation-3" role="complementary" aria-label="Sidebar principal">
    <a href="{{ url('/dashboard') }}" class="menu-image-play cold" aria-label="Ir al dashboard">
        <img src="{{ asset('img/prke.png') }}" alt="Logo PlayRKE" class="brand-image">
        <span class="sidebar-brand-version">v1.0</span>
    </a>

    {{-- Filtro incremental: oculta <li> del árbol que no matchean (JS al final) --}}
    <div class="sidebar-filter-wrap px-3 mt-2">
        <div class="sidebar-filter-input-group">
            <i class="fas fa-search sidebar-filter-icon" aria-hidden="true"></i>
            <input
                type="search"
                id="sidebar-filter-input"
                class="sidebar-filter-input"
                placeholder="Filtrar menú..."
                autocomplete="off"
                aria-label="Filtrar items del menú"
            >
        </div>
    </div>

    <section class="sidebar mt-2" role="navigation" aria-label="Menú lateral">
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                data-accordion="false" id="sidebar-menu-tree">

                {{-- Favoritos del usuario --}}
                @if(!empty($favorites))
                    <li class="nav-header sidebar-favorites-header">Favoritos</li>
                    @foreach($favorites as $favorite)
                        <li class="nav-item" data-favorite-key="{{ $favorite['item_key'] }}">
                            <a href="{{ $favorite['url'] }}" class="nav-link">
                                <i class="nav-icon fas fa-star sidebar-favorite-star-icon"></i>
                                <p>{{ $favorite['title'] }}</p>
                                <button
                                    type="button"
                                    class="sidebar-favorite-toggle sidebar-favorite-toggle--active"
                                    data-item-key="{{ $favorite['item_key'] }}"
                                    title="Quitar de favoritos"
                                    aria-label="Quitar de favoritos"
                                ><i class="fas fa-star"></i></button>
                            </a>
                        </li>
                    @endforeach
                    <li class="nav-header sidebar-favorites-divider"></li>
                @endif

                {{-- Menú dinámico basado en permisos --}}
                @foreach($menuItems as $key => $item)
                    @if(isset($item['submenu']))
                        {{-- Menú con submenús --}}
                        @php
                            $isSubmenuActive = $menuService->hasActiveSubmenu($item['submenu']);
                            // Suma de badges de los subitems visibles — el padre
                            // muestra el total agregado para que sea visible
                            // incluso con el submenú colapsado (ver id
                            // "sidebar-parent-badge-{key}" abajo, actualizado en
                            // vivo por PendingMovementsBadge.vue junto con el del
                            // subitem cuando el padre incluye wallet-movements).
                            $parentBadgeTotal = collect($item['submenu'])->sum(fn ($s) => (int) ($s['badge'] ?? 0));
                            $hasRealtimeBadge = collect($item['submenu'])->contains(fn ($s) => $s['route'] === 'wallet-movements.index');
                        @endphp
                        <li class="nav-item has-treeview {{ $isSubmenuActive ? 'menu-open' : '' }}" data-menu-key="{{ $key }}">
                            <a href="#" class="nav-link {{ $isSubmenuActive ? 'active' : '' }}">
                                <span class="sidebar-icon-badge-wrap">
                                    <i class="nav-icon {{ $item['icon'] }}"></i>
                                    @if($hasRealtimeBadge)
                                        <span
                                            id="sidebar-parent-badge-{{ $key }}"
                                            class="sidebar-icon-badge"
                                            style="{{ $parentBadgeTotal > 0 ? '' : 'display:none' }}"
                                        >{{ $parentBadgeTotal }}</span>
                                    @elseif($parentBadgeTotal > 0)
                                        <span class="sidebar-icon-badge">{{ $parentBadgeTotal }}</span>
                                    @endif
                                </span>
                                <p>
                                    {{ $item['title'] }}
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                @foreach($item['submenu'] as $subkey => $subitem)
                                    @php $subItemKey = "{$key}.{$subkey}"; @endphp
                                    <li class="nav-item">
                                        <a href="{{ route($subitem['route']) }}"
                                           title="{{ $subitem['title'] }}"
                                           class="{{ $menuService->getMenuItemClasses($subitem['route']) }}">
                                            <p>
                                                {{ $subitem['title'] }}
                                                @if($subitem['route'] === 'wallet-movements.index')
                                                    {{-- Recalculado en tiempo real por PendingMovementsBadge.vue (ver global.ts) --}}
                                                    <span
                                                        id="sidebar-pending-badge"
                                                        class="badge badge-danger right"
                                                        style="{{ empty($subitem['badge']) ? 'display:none' : '' }}"
                                                    >{{ $subitem['badge'] ?? 0 }}</span>
                                                @elseif(!empty($subitem['badge']))
                                                    <span class="badge badge-danger right">{{ $subitem['badge'] }}</span>
                                                @endif
                                            </p>
                                        </a>
                                        <button
                                            type="button"
                                            class="sidebar-favorite-toggle {{ in_array($subItemKey, $favoriteKeys) ? 'sidebar-favorite-toggle--active' : '' }}"
                                            data-item-key="{{ $subItemKey }}"
                                            title="{{ in_array($subItemKey, $favoriteKeys) ? 'Quitar de favoritos' : 'Añadir a favoritos' }}"
                                            aria-label="Alternar favorito"
                                        ><i class="fas fa-star"></i></button>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @else
                        {{-- Menú simple --}}
                        <li class="nav-item" data-menu-key="{{ $key }}">
                            <a href="{{ route($item['route']) }}"
                               class="{{ $menuService->getMenuItemClasses($item['route']) }}"
                               aria-current="{{ $menuService->isActive($item['route']) ? 'page' : false }}">
                                <i class="nav-icon {{ $item['icon'] }}"></i>
                                <p>{{ $item['title'] }}</p>
                            </a>
                            <button
                                type="button"
                                class="sidebar-favorite-toggle {{ in_array($key, $favoriteKeys) ? 'sidebar-favorite-toggle--active' : '' }}"
                                data-item-key="{{ $key }}"
                                title="{{ in_array($key, $favoriteKeys) ? 'Quitar de favoritos' : 'Añadir a favoritos' }}"
                                aria-label="Alternar favorito"
                            ><i class="fas fa-star"></i></button>
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
            <p class="sidebar-filter-empty" id="sidebar-filter-empty" hidden>Sin resultados.</p>
        </nav>
    </section>

    {{-- Pie del sidebar: info del usuario + cerrar sesión --}}
    <div class="sidebar-user-footer">
        <a href="{{ route('profile.index') }}" class="sidebar-user-footer__info" title="Ver mi perfil">
            <div class="sidebar-user-footer__avatar">
                <img src="{{ $sidebarPhoto }}" alt="{{ $sidebarName }}" class="sidebar-user-footer__photo">
            </div>
            <div class="sidebar-user-footer__details">
                <span class="sidebar-user-footer__name">{{ $sidebarName }}</span>
                <span class="sidebar-user-footer__role">{{ $userRoleDisplay }}</span>
            </div>
        </a>
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

<style>
    /* Tema oscuro (default del sidebar, degradado morado/negro — ver
       aside.main-sidebar en styles.css): texto e íconos claros sobre fondo
       oscuro. Sobreescrito abajo bajo [data-theme="light"], donde el sidebar
       cambia a fondo claro (#f0f2fa) — ver esa misma regla en styles.css. */
    .sidebar-filter-icon {
        position: absolute; left: 0.65rem; top: 50%; transform: translateY(-50%);
        font-size: 0.75rem; color: rgba(255,255,255,0.4); pointer-events: none;
    }
    .sidebar-filter-input-group { position: relative; }
    .sidebar-filter-input {
        width: 100%; padding: 0.35rem 0.6rem 0.35rem 1.85rem;
        font-size: 0.8rem; border-radius: 6px;
        border: 1px solid rgba(255,255,255,0.15);
        background: rgba(255,255,255,0.06); color: #fff;
    }
    .sidebar-filter-input::placeholder { color: rgba(255,255,255,0.35); }
    .sidebar-filter-empty { text-align: center; color: rgba(255,255,255,0.4); font-size: 0.8rem; padding: 0.75rem 0; margin: 0; }

    .sidebar-favorites-header { font-size: 0.7rem; letter-spacing: .04em; }
    .sidebar-favorites-divider { border-bottom: 1px solid rgba(255,255,255,0.1); margin: 0 0.9rem 0.4rem; padding: 0 !important; height: 0; }
    .sidebar-favorite-star-icon { color: #fbbf24; }

    /* Badge flotante sobre el ícono del padre (Bingo, Billetera, etc.) —
       esquina superior-izquierda del ícono, no en línea con el texto. */
    .sidebar-icon-badge-wrap { position: relative; display: inline-flex; }
    .sidebar-icon-badge {
        position: absolute; top: -0.35rem; left: -0.5rem;
        min-width: 15px; height: 15px; padding: 0 3px;
        border-radius: 999px; background: #dc3545; color: #fff;
        font-size: 0.62rem; font-weight: 700; line-height: 15px; text-align: center;
        box-shadow: 0 0 0 2px #14123f; /* borde a juego con el fondo oscuro del sidebar */
    }

    .nav-sidebar .nav-item { position: relative; }
    /* Badge de conteo en el submenú (ej. "Movimientos"): posición fija a la
       IZQUIERDA de la estrella de favorito (que conserva su posición
       original a la derecha) — ya no usa la clase .right de AdminLTE, que
       lo hacía chocar con el botón de favorito en la misma esquina. */
    .nav-treeview .badge.right {
        position: absolute; right: 1.8rem; top: 50%; transform: translateY(-50%);
        float: none; margin: 0;
    }
    /* Reserva el espacio de badge + estrella para que un título largo no
       quede tapado debajo de ninguno de los dos (antes lo evitaba el float
       de AdminLTE, perdido al pasar el badge a position:absolute arriba). */
    .nav-treeview > .nav-item > a.nav-link { padding-right: 2.6rem; }
    .sidebar-favorite-toggle {
        position: absolute; right: 0.4rem; top: 50%; transform: translateY(-50%);
        background: none; border: none; padding: 0.25rem; line-height: 1;
        color: rgba(255,255,255,0.25); font-size: 0.75rem; cursor: pointer;
        opacity: 0; transition: opacity .12s, color .12s;
    }
    .nav-sidebar .nav-item:hover .sidebar-favorite-toggle,
    .sidebar-favorite-toggle--active { opacity: 1; }
    .sidebar-favorite-toggle--active { color: #fbbf24; }
    .sidebar-favorite-toggle:hover { color: #fbbf24; }

    /* Tema claro — mismo selector que usa styles.css para recolorear el
       sidebar completo ([data-theme="light"] [class*='sidebar-dark-']). */
    [data-theme="light"] .sidebar-filter-icon { color: #6a7a9a; }
    [data-theme="light"] .sidebar-filter-input {
        border-color: #c8cee0;
        background: #ffffff;
        color: #3a4a6a;
    }
    [data-theme="light"] .sidebar-filter-input::placeholder { color: #8a9ab5; }
    [data-theme="light"] .sidebar-filter-empty { color: #8a9ab5; }
    [data-theme="light"] .sidebar-favorites-divider { border-bottom-color: #d8dde8; }
    [data-theme="light"] .sidebar-favorite-toggle { color: #b8c0d8; }
    [data-theme="light"] .sidebar-icon-badge { box-shadow: 0 0 0 2px #f0f2fa; }
    [data-theme="light"] .sidebar-favorite-toggle--active,
    [data-theme="light"] .sidebar-favorite-toggle:hover { color: #d97706; }
</style>

<script nonce="{{ csp_nonce() }}">
(function () {
    'use strict';

    // ── Filtro incremental del árbol ────────────────────────────────
    var filterInput = document.getElementById('sidebar-filter-input');
    var menuTree     = document.getElementById('sidebar-menu-tree');
    var emptyMsg     = document.getElementById('sidebar-filter-empty');

    function applyFilter() {
        var term = filterInput.value.trim().toLowerCase();
        var topLevelItems = menuTree.querySelectorAll(':scope > li.nav-item');
        var anyVisible = false;

        topLevelItems.forEach(function (li) {
            if (!term) {
                li.hidden = false;
                if (li.classList.contains('has-treeview')) {
                    li.querySelectorAll('.nav-treeview > li').forEach(function (sub) { sub.hidden = false; });
                }
                anyVisible = true;
                return;
            }

            var isTreeview = li.classList.contains('has-treeview');
            if (isTreeview) {
                var subItems = li.querySelectorAll('.nav-treeview > li');
                var subMatch = false;
                subItems.forEach(function (sub) {
                    var text = (sub.textContent || '').toLowerCase();
                    var matches = text.indexOf(term) !== -1;
                    sub.hidden = !matches;
                    if (matches) subMatch = true;
                });
                var parentText = (li.querySelector(':scope > a p') || {}).textContent || '';
                var parentMatch = parentText.toLowerCase().indexOf(term) !== -1;
                var show = subMatch || parentMatch;
                li.hidden = !show;
                if (show) {
                    anyVisible = true;
                    if (subMatch) li.classList.add('menu-open');
                }
            } else {
                var itemText = (li.textContent || '').toLowerCase();
                var itemMatch = itemText.indexOf(term) !== -1;
                li.hidden = !itemMatch;
                if (itemMatch) anyVisible = true;
            }
        });

        if (emptyMsg) emptyMsg.hidden = anyVisible;
    }

    if (filterInput && menuTree) {
        filterInput.addEventListener('input', applyFilter);
    }

    // ── Persistencia de expansión manual de submenús ────────────────
    var OPEN_KEY = 'playrke-sidebar-open';

    function getOpenKeys() {
        try {
            return JSON.parse(localStorage.getItem(OPEN_KEY) || '[]');
        } catch (e) { return []; }
    }

    function setOpenKeys(keys) {
        try { localStorage.setItem(OPEN_KEY, JSON.stringify(keys)); } catch (e) {}
    }

    // Al cargar: además del submenú con la ruta activa (ya viene con
    // .menu-open desde el servidor), reabrir los que el usuario dejó
    // expandidos manualmente en una sesión anterior.
    (function restoreOpenState() {
        var openKeys = getOpenKeys();
        if (!openKeys.length || !menuTree) return;
        openKeys.forEach(function (key) {
            var li = menuTree.querySelector('li.has-treeview[data-menu-key="' + CSS.escape(key) + '"]');
            if (li) li.classList.add('menu-open');
        });
    })();

    if (menuTree) {
        menuTree.querySelectorAll(':scope > li.has-treeview').forEach(function (li) {
            var link = li.querySelector(':scope > a');
            if (!link) return;
            link.addEventListener('click', function () {
                var key = li.getAttribute('data-menu-key');
                if (!key) return;
                // El estado de .menu-open lo alterna AdminLTE (treeview
                // widget) al mismo click; leemos el estado resultante en
                // el siguiente tick para persistirlo ya actualizado.
                setTimeout(function () {
                    var isOpen = li.classList.contains('menu-open');
                    var openKeys = getOpenKeys().filter(function (k) { return k !== key; });
                    if (isOpen) openKeys.push(key);
                    setOpenKeys(openKeys);
                }, 0);
            });
        });
    }

    // ── Favoritos: toggle vía fetch ──────────────────────────────────
    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    document.querySelectorAll('.sidebar-favorite-toggle').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            var itemKey = btn.getAttribute('data-item-key');
            var isActive = btn.classList.contains('sidebar-favorite-toggle--active');
            var url = isActive
                ? '/sidebar/favorites/' + encodeURIComponent(itemKey)
                : '/sidebar/favorites';

            fetch(url, {
                method: isActive ? 'DELETE' : 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: isActive ? undefined : JSON.stringify({ item_key: itemKey }),
            })
                .then(function (r) { if (r.ok) window.location.reload(); })
                .catch(function () {});
        });
    });
})();
</script>
