{{-- $navWallet y $navUser son inyectados por NavbarComposer vía AppServiceProvider --}}
<nav class="main-header navbar navbar-expand navbar-bg nb-root">

    {{-- ══ IZQUIERDA: toggle + logo (siempre visible) ══ --}}
    <div class="nb-left">
        <a class="nb-icon-btn" data-widget="pushmenu" href="#" role="button" aria-label="Menú">
            <i class="fas fa-bars"></i>
        </a>
        {{-- Logo: solo en móvil (sidebar colapsado) --}}
        <a href="{{ url('/dashboard') }}" class="nb-brand d-md-none" aria-label="Inicio">
            <img src="{{ asset('img/logo.png') }}" alt="PlayRKE" class="nb-brand-img">
        </a>
    </div>

    {{-- ══ CENTRO MÓVIL: billetera compacta (solo sm-, encima del buscador) ══ --}}
    @if ($navWallet)
    <div class="nb-wallet-center d-md-none">
        <a class="nb-wallet-pill nb-wallet-pill--center" href="{{ route('wallet.index') }}" title="Mi billetera">
            <span class="nb-wallet-icon"><i class="fas fa-wallet"></i></span>
            <span id="navbar-balance" class="nb-wallet-balance">
                <span class="nb-wallet-symbol">{{ $navWallet->currency->symbol }}</span>{{ number_format($navWallet->balance, $navWallet->currency->decimal_places ?? 2, ',', '.') }}
            </span>
        </a>
    </div>
    @endif

    {{-- ══ CENTRO DESKTOP: buscador + breadcrumb + quick chips (md+) ══ --}}
    <div class="nb-center-zone d-none d-md-flex align-items-center" style="flex:1; justify-content:center; gap:1rem; min-width:0; padding:0 12px;">

        {{-- Billetera desktop: dentro del centro para no presionar al avatar --}}
        @if ($navWallet)
        <a class="nb-wallet-pill" href="{{ route('wallet.index') }}" title="Mi billetera">
            <span class="nb-wallet-icon"><i class="fas fa-wallet"></i></span>
            <span id="navbar-balance-desktop" class="nb-wallet-balance">
                <span class="nb-wallet-symbol">{{ $navWallet->currency->symbol }}</span>{{ number_format($navWallet->balance, $navWallet->currency->decimal_places ?? 2, ',', '.') }}
            </span>
        </a>
        @endif

        @if (!empty($navBreadcrumb) && count($navBreadcrumb) > 1)
            {{-- Breadcrumb de navegación --}}
            <nav class="nb-breadcrumb" aria-label="Ruta de navegación">
                @foreach ($navBreadcrumb as $index => $crumb)
                    @if ($index > 0)
                        <i class="fas fa-chevron-right nb-breadcrumb__sep"></i>
                    @endif
                    @if (!$loop->last && $crumb['route'])
                        <a href="{{ route($crumb['route']) }}" class="nb-breadcrumb__link">{{ $crumb['title'] }}</a>
                    @else
                        <span class="nb-breadcrumb__current">{{ $crumb['title'] }}</span>
                    @endif
                @endforeach
            </nav>
        @endif

        {{-- Buscador global rápido (visible en md+, colapsa a solo lupa sin foco/texto) --}}
        <div class="nb-search-wrap" id="nb-search-wrap">
            <form action="{{ url('/search') }}" method="GET" class="nb-search-form" onsubmit="return nbGlobalSearch(event, this)">
                <i class="fas fa-search nb-search-icon" id="nb-search-icon" aria-hidden="true"></i>
                <input
                    type="search"
                    name="q"
                    placeholder="Buscar usuario, sala, transacción…"
                    class="nb-search-input"
                    autocomplete="off"
                    maxlength="80"
                >
                <kbd class="nb-search-kbd">⌘K</kbd>
            </form>
        </div>

        {{-- Chips de acceso rápido según rol --}}
        <div class="nb-quick-chips d-none d-lg-flex">
            @if (auth()->user()?->hasRole(['admin','superadmin']))
                <a href="{{ route('wallet-movements.index') }}" class="nb-qchip nb-qchip--finance" title="Movimientos pendientes">
                    <i class="fas fa-exchange-alt"></i>
                    <span>Movimientos</span>
                </a>
                <a href="{{ route('directory.index') }}" class="nb-qchip nb-qchip--rooms" title="Salas de bingo">
                    <i class="fas fa-door-open"></i>
                    <span>Salas</span>
                </a>
                <a href="{{ route('admin.users') }}" class="nb-qchip nb-qchip--users" title="Gestión de usuarios">
                    <i class="fas fa-users"></i>
                    <span>Usuarios</span>
                </a>
            @elseif (auth()->user()?->hasRole('treasurer'))
                <a href="{{ route('wallet-movements.index') }}" class="nb-qchip nb-qchip--finance" title="Movimientos">
                    <i class="fas fa-exchange-alt"></i>
                    <span>Movimientos</span>
                </a>
            @elseif (auth()->user()?->hasRole('seller'))
                <a href="{{ route('directory.index') }}" class="nb-qchip nb-qchip--rooms" title="Salas de bingo">
                    <i class="fas fa-door-open"></i>
                    <span>Salas</span>
                </a>
            @endif
            {{-- Hora del servidor --}}
            <span class="nb-qchip nb-qchip--clock" title="Hora del servidor">
                <i class="fas fa-clock"></i>
                <span id="nb-server-clock">{{ now()->format('H:i') }}</span>
            </span>
        </div>

    </div>

    {{-- ══ DERECHA: pendientes + notificaciones + usuario ══ --}}
    <div class="nb-right">

        {{-- Pendientes: pill en sm+, ícono en xs --}}
        <a class="nb-pending-pill d-none d-sm-flex" id="nb-pending-pill-wrap"
           href="{{ route('wallet-movements.index') }}" title="Movimientos pendientes"
           style="{{ $navPendingCount > 0 ? '' : 'display:none!important' }}">
            <i class="fas fa-clock"></i>
            <span id="nb-pending-label">{{ $navPendingCount }} pendiente{{ $navPendingCount === 1 ? '' : 's' }}</span>
        </a>
        <a class="nb-icon-btn position-relative d-sm-none" id="nb-pending-pill-wrap-sm"
           href="{{ route('wallet-movements.index') }}" title="Movimientos pendientes"
           style="{{ $navPendingCount > 0 ? '' : 'display:none!important' }}">
            <i class="fas fa-clock"></i>
            <span id="nb-pending-badge" class="nb-badge nb-badge--warning">{{ $navPendingCount > 99 ? '99+' : $navPendingCount }}</span>
        </a>

        {{-- Notificaciones --}}
        <div class="nav-item dropdown">
            <a class="nb-icon-btn position-relative" href="#" id="notifToggle"
               data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
               title="Notificaciones">
                <i class="fas fa-bell"></i>
                <span id="notif-badge" class="nb-badge{{ ($navUnreadCount ?? 0) > 0 ? '' : ' d-none' }}">{{ ($navUnreadCount ?? 0) > 99 ? '99+' : ($navUnreadCount ?? 0) }}</span>
            </a>
            <div class="dropdown-menu dropdown-menu-right p-0 nb-notif-menu" aria-labelledby="notifToggle">
                <div class="nb-notif-header">
                    <span><i class="fas fa-bell mr-1"></i>Notificaciones</span>
                    <button type="button" id="notif-clear" class="nb-notif-clear">Limpiar todo</button>
                </div>
                <ul id="notif-list" class="list-unstyled mb-0 nb-notif-list">
                    <li id="notif-empty" class="nb-notif-empty" style="{{ empty($navNotifications) ? '' : 'display:none' }}">
                        <i class="fas fa-check-circle"></i>
                        <span>Sin notificaciones nuevas</span>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Usuario — $navUser es null para guests (ver NavbarComposer::compose());
             este bloque completo asume un usuario autenticado, así que no se
             renderiza sin uno para evitar acceder a propiedades de null. --}}
        @if ($navUser)
        <div class="nav-item dropdown">
            <button class="nb-user-btn"
                    type="button" id="userDropdown"
                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                    aria-label="Opciones de usuario">
                <span class="nb-avatar-wrap">
                    <img class="user-img nb-avatar"
                         src="{{ $navUser->profile_photo_url ?? ($navUser->foto ? asset('img/users/' . $navUser->foto) : asset('img/fav.ico')) }}"
                         alt="{{ $navUser->username }}">
                    <span class="nb-avatar-online"></span>
                    @if ($navUserLevel)
                        <span class="nb-avatar-level" title="Nivel {{ $navUserLevel->current_level }}">{{ $navUserLevel->current_level }}</span>
                    @endif
                </span>
                <i class="fas fa-chevron-down nb-chevron d-none d-sm-block"></i>
            </button>

            <div class="dropdown-menu dropdown-menu-right p-0 nb-user-menu"
                 aria-labelledby="userDropdown">

                {{-- Header con gradiente --}}
                <div class="nb-user-header">
                    <img class="user-img nb-user-header-avatar"
                         src="{{ $navUser->profile_photo_url ?? ($navUser->foto ? asset('img/users/' . $navUser->foto) : asset('img/fav.ico')) }}"
                         alt="{{ $navUser->username }}">
                    <div class="nb-user-header-info">
                        <div class="nb-user-header-name">
                            {{ $navUser->username }}
                            @if($navUser->email_verified_at)
                                <i class="fas fa-check-circle nb-verified-icon" title="Verificado"></i>
                            @endif
                        </div>
                        <div class="nb-user-header-email">{{ $navUser->email ?? '' }}</div>
                    </div>
                </div>

                {{-- Nivel y progreso de experiencia --}}
                @if ($navUserLevel)
                    <div class="nb-user-level">
                        <div class="nb-user-level-row">
                            <span class="nb-user-level-badge"><i class="fas fa-star"></i> Nivel {{ $navUserLevel->current_level }}</span>
                            <span class="nb-user-level-xp">{{ $navUserLevel->current_xp }} / {{ $navUserLevel->xp_to_next_level }} XP</span>
                        </div>
                        <div class="nb-user-level-bar">
                            <div class="nb-user-level-bar-fill" style="width: {{ $navUserLevel->progress_percentage }}%"></div>
                        </div>
                    </div>
                @endif

                {{-- Opciones --}}
                <div class="nb-user-menu-body">
                    <a href="{{ route('profile.index') }}" class="nb-menu-item">
                        <span class="nb-menu-icon nb-icon-profile"><i class="fas fa-user"></i></span>
                        <span>Mi Perfil</span>
                    </a>
                    <a href="{{ route('wallet.index') }}" class="nb-menu-item">
                        <span class="nb-menu-icon nb-icon-wallet"><i class="fas fa-wallet"></i></span>
                        <span>Mi Billetera</span>
                    </a>

                    {{-- Toggle de tema --}}
                    <button type="button" id="nb-theme-toggle" class="nb-menu-item nb-menu-item--btn">
                        <span class="nb-menu-icon nb-icon-theme" id="nb-theme-icon-wrap">
                            {{-- SVG sol (dark mode → mostrar sol para "activar claro") --}}
                            <svg id="nb-theme-svg-sun" xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2a1 1 0 0 1 1 1v1a1 1 0 1 1-2 0V3a1 1 0 0 1 1-1zm0 16a1 1 0 0 1 1 1v1a1 1 0 1 1-2 0v-1a1 1 0 0 1 1-1zm8.66-10a1 1 0 0 1-.366 1.366l-.866.5a1 1 0 1 1-1-1.732l.866-.5A1 1 0 0 1 20.66 8zM5.706 15.134a1 1 0 0 1-.366 1.366l-.866.5a1 1 0 1 1-1-1.732l.866-.5a1 1 0 0 1 1.366.366zM20.66 16a1 1 0 0 1-1.366.366l-.866-.5a1 1 0 1 1 1-1.732l.866.5A1 1 0 0 1 20.66 16zM5.706 8.866a1 1 0 0 1-1.366.366l-.866-.5a1 1 0 1 1 1-1.732l.866.5a1 1 0 0 1 .366 1.366zM22 12a1 1 0 0 1-1 1h-1a1 1 0 1 1 0-2h1a1 1 0 0 1 1 1zM4 12a1 1 0 0 1-1 1H2a1 1 0 1 1 0-2h1a1 1 0 0 1 1 1zm8-5a5 5 0 1 0 0 10A5 5 0 0 0 12 7z"/>
                            </svg>
                            {{-- SVG luna (light mode → mostrar luna para "activar oscuro") --}}
                            <svg id="nb-theme-svg-moon" xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="currentColor" style="display:none">
                                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                            </svg>
                        </span>
                        <span id="nb-theme-label">Tema claro</span>
                        <span class="nb-theme-pill" id="nb-theme-pill">
                            <span class="nb-theme-pill__dot"></span>
                        </span>
                    </button>
                </div>

                <div class="nb-menu-divider"></div>

                <div class="nb-user-menu-footer">
                    <button type="button"
                            class="logout-btn nb-logout-btn"
                            data-logout-url="{{ route('logout') }}"
                            data-login-redirect-url="{{ route('login') }}"
                            data-show-confirmation="false">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Cerrar Sesión</span>
                    </button>
                </div>
            </div>
        </div>
        @endif

    </div>{{-- /.nb-right --}}
</nav>

{{-- ── Notificaciones + WebSocket (todos los usuarios autenticados) ── --}}
@auth
<script>
(function () {
    'use strict';

    var USER_ID   = @json((string) Auth::id());
    var WALLET_ID = @json($navWallet ? (string) $navWallet->id : null);
    var IS_ADMIN  = @json(Auth::user()->hasRole(['admin', 'superadmin']));

    // Notificaciones pre-cargadas desde PHP (sin API call al primer abrir)
    var INITIAL_NOTIFICATIONS = @json(array_reverse($navNotifications ?? []));

    // ── Metadatos por tipo de movimiento ─────────────────────
    var TYPE_META = {
        DEPOSIT:        { label: 'Depósito',      icon: 'fa-arrow-down',  color: '#28a745', sign: '+' },
        WITHDRAWAL:     { label: 'Retiro',         icon: 'fa-arrow-up',    color: '#ffc107', sign: '-' },
        PURCHASE:       { label: 'Compra cartón',  icon: 'fa-ticket-alt',  color: '#dc3545', sign: '-' },
        PRIZE:          { label: '¡Premio! 🎉',    icon: 'fa-trophy',      color: '#fd7e14', sign: '+' },
        TRIVIA_ENTRY:   { label: 'Entrada Trivia', icon: 'fa-brain',       color: '#7c3aed', sign: '-' },
        REFUND:         { label: 'Reembolso',      icon: 'fa-undo',       color: '#0891b2', sign: '+' },
        RKE_COMMISSION: { label: 'Comisión RKE',   icon: 'fa-percent',    color: '#667eea', sign: '+' },
    };

    var ADMIN_META = {
        DEPOSIT:    { label: '💰 Depósito pendiente', icon: 'fa-arrow-down', color: '#28a745', sign: '+' },
        WITHDRAWAL: { label: '💸 Retiro pendiente',   icon: 'fa-arrow-up',   color: '#ffc107', sign: '-' },
    };

    function resolveResultMeta(type, status) {
        var ok = status === 'COMPLETED';
        if (type === 'DEPOSIT') return ok
            ? { label: '✅ Depósito aprobado',               icon: 'fa-check-circle', color: '#28a745', sign: '+', url: '/wallet' }
            : { label: '❌ Depósito rechazado',               icon: 'fa-times-circle', color: '#dc3545', sign: '',  url: '/wallet' };
        if (type === 'WITHDRAWAL') return ok
            ? { label: '✅ Retiro procesado',                 icon: 'fa-check-circle', color: '#28a745', sign: '-', url: '/wallet' }
            : { label: '↩️ Retiro rechazado — saldo devuelto', icon: 'fa-undo',        color: '#ffc107', sign: '',  url: '/wallet' };
        return { label: 'Movimiento actualizado', icon: 'fa-exchange-alt', color: '#6c757d', sign: '', url: '/wallet' };
    }

    // ── DOM refs ──────────────────────────────────────────────
    var $badge     = document.getElementById('notif-badge');
    var $list      = document.getElementById('notif-list');
    var $empty     = document.getElementById('notif-empty');
    var $balanceEl         = document.getElementById('navbar-balance');
    var $balanceElDesktop  = document.getElementById('navbar-balance-desktop');
    var $clearBtn  = document.getElementById('notif-clear');
    var notifCount = 0;

    // ── Helpers ───────────────────────────────────────────────
    function csrfToken() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }
    function apiFetch(url, method) {
        return fetch(url, {
            method: method || 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken(),
            },
        });
    }
    function fmt(symbol, n) {
        return symbol + ' ' + parseFloat(n).toLocaleString('es-VE', {
            minimumFractionDigits: 2, maximumFractionDigits: 2
        });
    }

    function timeAgo(dateStr) {
        var diff = Math.floor((Date.now() - new Date(dateStr).getTime()) / 1000);
        if (diff < 5)      return 'Justo ahora';
        if (diff < 60)     return 'Hace ' + diff + ' s';
        var minutes = Math.floor(diff / 60);
        if (minutes < 60)  return 'Hace ' + minutes + ' min';
        var hours = Math.floor(minutes / 60);
        if (hours < 24)    return 'Hace ' + hours + ' h';
        var days = Math.floor(hours / 24);
        if (days < 7)      return 'Hace ' + days + ' d';
        return new Date(dateStr).toLocaleDateString('es-VE', { day: '2-digit', month: 'short' });
    }
    function fullDateTime(dateStr) {
        return new Date(dateStr).toLocaleString('es-VE', {
            day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
        });
    }
    function tickRelativeTimes() {
        $list.querySelectorAll('[data-created-at]').forEach(function (el) {
            el.textContent = timeAgo(el.getAttribute('data-created-at'));
        });
    }
    setInterval(tickRelativeTimes, 30000);

    // ── Saldo navbar ──────────────────────────────────────────
    function updateBalance(data) {
        var html = '<span class="nb-wallet-symbol">' + data.currency_symbol + '</span>' +
            parseFloat(data.balance).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        [$balanceEl, $balanceElDesktop].forEach(function (el) {
            if (!el) return;
            el.innerHTML = html;
            el.style.transition = 'opacity .15s';
            el.style.opacity = '0.2';
            setTimeout(function () { el.style.opacity = '1'; }, 160);
        });
    }

    // ── Badge de notificaciones ───────────────────────────────
    function setBadge(n) {
        notifCount = Math.max(0, n);
        if (notifCount === 0) {
            $badge.classList.add('d-none');
            $badge.textContent = '0';
        } else {
            $badge.textContent = notifCount > 99 ? '99+' : String(notifCount);
            $badge.classList.remove('d-none');
        }
    }
    function incBadge() { setBadge(notifCount + 1); }

    // ── Badge / pill de pendientes (admins) ───────────────────
    var pendingWrap   = document.getElementById('nb-pending-pill-wrap');
    var pendingWrapSm = document.getElementById('nb-pending-pill-wrap-sm');
    var pendingLabel  = document.getElementById('nb-pending-label');
    var pendingBadge  = document.getElementById('nb-pending-badge');

    function setPendingCount(n) {
        n = Math.max(0, n);
        var show = n > 0;
        var label = n + ' pendiente' + (n === 1 ? '' : 's');
        var badgeTxt = n > 99 ? '99+' : String(n);

        if (pendingWrap)   { pendingWrap.style.display   = show ? '' : 'none'; }
        if (pendingWrapSm) { pendingWrapSm.style.display = show ? '' : 'none'; }
        if (pendingLabel)  { pendingLabel.textContent  = label; }
        if (pendingBadge)  { pendingBadge.textContent  = badgeTxt; }
    }
    function incPending()  {
        var cur = pendingLabel ? parseInt(pendingLabel.textContent, 10) || 0 : 0;
        setPendingCount(cur + 1);
    }

    // ── Construir <li> ────────────────────────────────────────
    function buildListItem(meta, bodyHtml, createdAt, url, notifId, variant, isRead) {
        if (notifId && $list.querySelector('[data-notification-id="' + notifId + '"]')) return null;

        var inner =
            '<div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 mt-1"' +
                 ' style="width:32px;height:32px;background:' + meta.color + '22">' +
              '<i class="fas ' + meta.icon + '" style="font-size:12px;color:' + meta.color + '"></i>' +
            '</div>' +
            '<div style="flex:1;min-width:0">' + bodyHtml + '</div>' +
            '<small class="text-muted flex-shrink-0" data-created-at="' + createdAt + '" title="' + fullDateTime(createdAt) + '" style="font-size:10px;white-space:nowrap">' + timeAgo(createdAt) + '</small>';

        var li = document.createElement('li');
        li.className = 'border-bottom notif-entry' +
            (variant ? ' notif-entry--' + variant : '') +
            (isRead  ? ' read' : '');
        if (notifId) li.setAttribute('data-notification-id', notifId);
        li.innerHTML = url
            ? '<a href="' + url + '" class="d-flex align-items-start px-3 py-2 text-decoration-none notif-entry-link" style="gap:10px">' + inner + '</a>'
            : '<div class="d-flex align-items-start px-3 py-2" style="gap:10px">' + inner + '</div>';

        $list.insertBefore(li, $list.firstChild);
        var all = $list.querySelectorAll('.notif-entry');
        if (all.length > 25) all[all.length - 1].remove();
        return li;
    }

    // ── Resolución de meta a partir de una notificación ────────
    function resolveMeta(notification) {
        var isAdminAlert = notification.type === 'WALLET_ADMIN_ALERT';
        var mvType   = notification.data && notification.data.movement_type;
        var mvStatus = notification.data && notification.data.movement_status;
        var isResult = mvStatus === 'COMPLETED' || mvStatus === 'REJECTED';

        if (isAdminAlert)  return { meta: ADMIN_META[mvType] || { label: notification.title, icon: 'fa-bell', color: '#6c757d' }, isResult: false, isAdmin: true };
        if (isResult)      return { meta: resolveResultMeta(mvType, mvStatus), isResult: true,  isAdmin: false };
        return { meta: TYPE_META[mvType] || { label: notification.title || 'Notificación', icon: 'fa-bell', color: '#6c757d' }, isResult: false, isAdmin: false };
    }

    // ── Renderizar una notificación (desde PHP o desde API) ────
    function renderNotification(notification, prepend) {
        if ($empty) $empty.style.display = 'none';

        var r    = resolveMeta(notification);
        var url  = (notification.data && notification.data.url) || (r.isResult ? '/wallet' : null);
        var data = notification.data || {};

        var amountChip = '';
        if (data.amount != null && data.currency_symbol && r.meta.sign) {
            amountChip =
                '<span class="flex-shrink-0 font-weight-bold" style="font-size:12px;color:' + r.meta.color + '">' +
                    r.meta.sign + fmt(data.currency_symbol, data.amount) +
                '</span>';
        }

        var li = buildListItem(
            r.meta,
            '<div class="d-flex align-items-start justify-content-between" style="gap:8px">' +
                '<p class="mb-0 font-weight-semibold notif-entry-title" style="font-size:12px;line-height:1.4">' + (notification.title || r.meta.label) + '</p>' +
                amountChip +
            '</div>' +
            '<p class="mb-0 notif-entry-message" style="font-size:11px">' + notification.message + '</p>',
            notification.created_at,
            url,
            notification.id,
            r.isAdmin ? 'admin' : (r.isResult ? 'result' : ''),
            !!notification.is_read
        );

        if (li && prepend) {
            // Animación de entrada
            li.style.transition = 'background .4s';
            li.style.background = r.meta.color + '18';
            setTimeout(function () { li.style.background = ''; }, 800);
        }
        return li;
    }

    // ── Cargar notificaciones iniciales desde PHP (sin API call) ─
    function renderInitial() {
        if (!INITIAL_NOTIFICATIONS || !INITIAL_NOTIFICATIONS.length) return;
        INITIAL_NOTIFICATIONS.forEach(function (n) { renderNotification(n, false); });
    }

    // ── WS: nueva notificación con ID real de DB ───────────────
    function addRealtimeNotification(notification) {
        var li = renderNotification(notification, true);
        if (li) incBadge();
    }

    // ── WS: sonido para admin cuando llega movimiento pendiente ─
    function playAdminSound() {
        try {
            var ctx = new (window.AudioContext || window.webkitAudioContext)();
            var osc = ctx.createOscillator(); var gain = ctx.createGain();
            osc.connect(gain); gain.connect(ctx.destination);
            osc.frequency.value = 880;
            gain.gain.setValueAtTime(0.1, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.3);
            osc.start(ctx.currentTime); osc.stop(ctx.currentTime + 0.3);
        } catch(e) {}
    }

    // ── Marcar leídas en DOM y API ─────────────────────────────
    function markAllReadInAPI() {
        apiFetch('/notifications/mark-all-read', 'POST')
            .then(function () {
                $list.querySelectorAll('.notif-entry:not(.read)').forEach(function (el) {
                    el.classList.add('read');
                });
            })
            .catch(function () {});
    }

    // ── "Limpiar todo": borrar de DB ──────────────────────────
    $clearBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        apiFetch('/notifications', 'DELETE')
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (json) {
                if (json !== null) {
                    $list.querySelectorAll('.notif-entry').forEach(function (el) { el.remove(); });
                    if ($empty) $empty.style.display = '';
                    setBadge(0);
                }
            })
            .catch(function () {});
    });

    // ── Abrir panel: marcar leídas (sin reload — ya están renderizadas) ──
    var panelOpen = false;
    document.getElementById('notifToggle').addEventListener('click', function () {
        if (panelOpen) { panelOpen = false; return; }
        panelOpen = true;

        if (notifCount > 0) {
            markAllReadInAPI();
            setBadge(0);
        }

        document.addEventListener('click', function onOutside(ev) {
            if (!ev.target.closest('.nb-notif-menu') && !ev.target.closest('#notifToggle')) {
                panelOpen = false;
                document.removeEventListener('click', onOutside);
            }
        });
    });

    // ── wallet:updated desde Vue (solo saldo) ─────────────────
    window.addEventListener('wallet:updated', function (e) {
        updateBalance({ balance: e.detail.balance, currency_symbol: e.detail.currency_symbol });
    });

    // ── WebSocket ─────────────────────────────────────────────
    function boot() {
        if (!window.Echo) { setTimeout(boot, 250); return; }

        window.Echo.private('App.Models.User.' + USER_ID)
            // Saldo en tiempo real
            .listen('.wallet.updated', function (data) {
                if (WALLET_ID && data.wallet_id !== WALLET_ID) return;
                if (WALLET_ID) updateBalance(data);
            })
            // Nueva notificación con ID real → insertar sin API call
            .listen('.notification.created', function (data) {
                addRealtimeNotification(data);
            });

        if (IS_ADMIN) {
            window.Echo.private('admins.wallet-movements')
                // Nuevo movimiento pendiente → incrementar contador + sonido
                .listen('.wallet.movement.requested', function () {
                    incPending();
                    playAdminSound();
                })
                // Movimiento procesado → actualizar contador con valor exacto del server
                .listen('.wallet.movement.processed', function (data) {
                    setPendingCount(data.pending_count);
                });
        }
    }

    // ── Badge inicial desde PHP (ya renderizado en HTML, solo sincronizar JS) ─
    setBadge({{ $navUnreadCount ?? 0 }});

    // ── Renderizar notificaciones iniciales desde PHP ──────────
    document.addEventListener('DOMContentLoaded', function () {
        renderInitial();
        boot();
    });

})();
</script>
@endauth

{{-- ── Reloj del servidor (navbar centro) ── --}}
<script>
(function () {
    var el = document.getElementById('nb-server-clock');
    if (!el) return;
    function tick() {
        var now = new Date();
        el.textContent = now.toLocaleTimeString('es-VE', { hour: '2-digit', minute: '2-digit' });
    }
    setInterval(tick, 30000);
})();

// ── Sync de avatar tras editarlo desde /profile (Vue emite este evento) ──
window.addEventListener('profile:avatar-updated', function (e) {
    var url = e.detail && e.detail.avatarUrl;
    if (!url) return;
    document.querySelectorAll('.user-img').forEach(function (img) { img.src = url; });
});

function nbGlobalSearch(e, form) {
    var q = form.querySelector('input[name=q]').value.trim();
    if (!q) { e.preventDefault(); return false; }
    return true;
}

// Atajo de teclado ⌘K / Ctrl+K
document.addEventListener('keydown', function (e) {
    if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
        e.preventDefault();
        var inp = document.querySelector('.nb-search-input');
        if (inp) inp.focus();
    }
});

// ── Buscador colapsable: mantener expandido si hay texto, ─
// ── click en la lupa colapsada expande y enfoca el input ──
(function () {
    var wrap = document.getElementById('nb-search-wrap');
    if (!wrap) return;
    var input = wrap.querySelector('.nb-search-input');
    var icon  = document.getElementById('nb-search-icon');

    function syncActive() {
        wrap.classList.toggle('nb-search-wrap--active', !!input.value.trim());
    }
    input.addEventListener('input', syncActive);
    input.addEventListener('blur', syncActive);
    syncActive();

    // mousedown (no click): dispara antes de que el navegador resuelva el
    // foco del click, y preventDefault evita que el input pierda el foco
    // que le estamos por dar. Expandir primero: mientras está colapsado
    // el input es display:none y no puede recibir foco programático.
    icon.addEventListener('mousedown', function (e) {
        e.preventDefault();
        wrap.classList.add('nb-search-wrap--active');
        input.focus();
    });
})();

// ── Theme toggle ─────────────────────────────────────────
window.__playrkeTheme = (function () {
    var STORAGE_KEY = 'playrke-theme';
    var html = document.documentElement;

    function applyTheme(theme) {
        var label   = document.getElementById('nb-theme-label');
        var pill    = document.getElementById('nb-theme-pill');
        var svgSun  = document.getElementById('nb-theme-svg-sun');
        var svgMoon = document.getElementById('nb-theme-svg-moon');
        if (theme === 'light') {
            html.setAttribute('data-theme', 'light');
            if (label)   label.textContent = 'Modo oscuro';
            if (pill)    pill.classList.add('nb-theme-pill--on');
            if (svgSun)  svgSun.style.display  = 'none';
            if (svgMoon) svgMoon.style.display = '';
        } else {
            html.removeAttribute('data-theme');
            if (label)   label.textContent = 'Modo claro';
            if (pill)    pill.classList.remove('nb-theme-pill--on');
            if (svgSun)  svgSun.style.display  = '';
            if (svgMoon) svgMoon.style.display = 'none';
        }
    }

    function toggle() {
        var next = html.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
        localStorage.setItem(STORAGE_KEY, next);
        applyTheme(next);
    }

    // Inicializar con el tema guardado
    applyTheme(localStorage.getItem(STORAGE_KEY) || 'dark');

    // Registrar al hacer DOMContentLoaded para asegurarse que el botón existe
    document.addEventListener('DOMContentLoaded', function () {
        var btn = document.getElementById('nb-theme-toggle');
        if (btn) {
            btn.addEventListener('mousedown', function (e) {
                e.preventDefault();  // evita que Bootstrap cierre el dropdown en mousedown
            });
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                toggle();
            });
        }
    });

    return { toggle: toggle, apply: applyTheme };
})();

// ── Sidebar width sync → CSS var --sidebar-w ─────────────
(function () {
    var root = document.documentElement;
    var body = document.body;

    function calcW() {
        var isMini     = body.classList.contains('sidebar-mini');
        var isCollapse = body.classList.contains('sidebar-collapse');
        if (window.innerWidth < 768) return '0px';
        if (isMini && isCollapse)    return '4.6rem';
        if (!isMini && isCollapse)   return '0px';
        return '250px';
    }

    function applyW() { root.style.setProperty('--sidebar-w', calcW()); }

    // Aplicar ahora (body ya existe — este script va al final del <body>)
    applyW();

    // Reaccionar a cada toggle de AdminLTE (agrega/quita 'sidebar-collapse')
    if (window.MutationObserver) {
        new MutationObserver(applyW).observe(body, {
            attributes: true, attributeFilter: ['class']
        });
    }
    window.addEventListener('resize', applyW);
})();
</script>
