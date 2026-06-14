{{-- $navWallet y $navUser son inyectados por NavbarComposer vía AppServiceProvider --}}
<nav class="main-header navbar navbar-expand navbar-bg justify-content-between">

    {{-- ── Izquierda: toggle + marca + billetera ───────────── --}}
    <ul class="navbar-nav align-items-center" style="gap:.25rem;">

        {{-- Toggle sidebar --}}
        <li class="nav-item">
            <a class="nb-icon-btn" data-widget="pushmenu" href="#" role="button" aria-label="Menú">
                <i class="fas fa-bars"></i>
            </a>
        </li>

        {{-- Marca PlayRKE: visible solo cuando el sidebar está oculto (móvil) --}}
        <li class="nav-item d-md-none">
            <a href="{{ url('/dashboard') }}" class="nb-brand" aria-label="Ir al dashboard">
                <img src="{{ asset('img/logo.png') }}" alt="PlayRKE" class="nb-brand-img">
            </a>
        </li>

        {{-- Billetera (solo si el usuario tiene una activa) --}}
        @if ($navWallet)
            <li class="nav-item">
                <a class="nb-wallet-pill" href="{{ route('wallet.index') }}" title="Mi billetera">
                    <span class="nb-wallet-icon"><i class="fas fa-wallet"></i></span>
                    <span id="navbar-balance" class="nb-wallet-balance">
                        <span class="nb-wallet-symbol">{{ $navWallet->currency->symbol }}</span>{{ number_format($navWallet->balance, $navWallet->currency->decimal_places ?? 2, ',', '.') }}
                    </span>
                </a>
            </li>
        @endif

    </ul>

    {{-- ── Centro: breadcrumb de navegación ─────────────────── --}}
    @if (!empty($navBreadcrumb) && count($navBreadcrumb) > 1)
        <nav class="nb-breadcrumb d-none d-md-flex" aria-label="Ruta de navegación">
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

    {{-- ── Derecha: pendientes + notificaciones + usuario ──── --}}
    <ul class="navbar-nav align-items-center" style="gap:.5rem;">

        {{-- 🟠 Acciones pendientes (depósitos/retiros): solo admin, superadmin y tesorero --}}
        @if ($navPendingCount > 0)
            <li class="nav-item d-none d-sm-block">
                <a class="nb-pending-pill" href="{{ route('wallet-movements.index') }}"
                   title="Movimientos pendientes por revisar">
                    <i class="fas fa-clock"></i>
                    <span>{{ $navPendingCount }} pendiente{{ $navPendingCount === 1 ? '' : 's' }}</span>
                </a>
            </li>
            {{-- Versión compacta para pantallas muy pequeñas --}}
            <li class="nav-item d-sm-none">
                <a class="nb-icon-btn position-relative" href="{{ route('wallet-movements.index') }}"
                   title="Movimientos pendientes por revisar">
                    <i class="fas fa-clock"></i>
                    <span class="nb-badge nb-badge--warning">{{ $navPendingCount > 99 ? '99+' : $navPendingCount }}</span>
                </a>
            </li>
        @endif

        {{-- 🔔 Notificaciones --}}
        <li class="nav-item dropdown">
            <a class="nb-icon-btn position-relative" href="#" id="notifToggle"
               data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
               title="Notificaciones">
                <i class="fas fa-bell"></i>
                <span id="notif-badge" class="nb-badge d-none">0</span>
            </a>

            <div class="dropdown-menu dropdown-menu-right p-0 nb-notif-menu"
                 aria-labelledby="notifToggle">

                <div class="nb-notif-header">
                    <span><i class="fas fa-bell mr-1"></i>Notificaciones</span>
                    <button type="button" id="notif-clear" class="nb-notif-clear">Limpiar todo</button>
                </div>

                <ul id="notif-list" class="list-unstyled mb-0 nb-notif-list">
                    <li id="notif-empty" class="nb-notif-empty">
                        <i class="fas fa-check-circle"></i>
                        <span>Sin notificaciones nuevas</span>
                    </li>
                </ul>
            </div>
        </li>

        {{-- 👤 Usuario --}}
        <li class="nav-item dropdown">
            <button class="nb-user-btn"
                    type="button" id="userDropdown"
                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                    aria-label="Opciones de usuario">
                <span class="nb-avatar-wrap">
                    <img class="user-img nb-avatar"
                         src="{{ $navUser->profile_photo_url ?? ($navUser->foto ? asset('img/users/' . $navUser->foto) : asset('img/fav.ico')) }}"
                         alt="{{ $navUser->username }}">
                    <span class="nb-avatar-online"></span>
                </span>
                <i class="fas fa-chevron-down nb-chevron"></i>
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
        </li>

    </ul>
</nav>

{{-- ── Notificaciones + WebSocket (todos los usuarios autenticados) ── --}}
@auth
<script>
(function () {
    'use strict';

    var USER_ID   = @json((string) Auth::id());
    var WALLET_ID = @json($navWallet ? (string) $navWallet->id : null);
    var IS_ADMIN  = @json(Auth::user()->hasRole(['admin', 'superadmin']));

    // ── Metadatos por tipo de movimiento ─────────────────────
    var TYPE_META = {
        DEPOSIT:      { label: 'Depósito',        icon: 'fa-arrow-down',  color: '#28a745', sign: '+' },
        WITHDRAWAL:   { label: 'Retiro',           icon: 'fa-arrow-up',    color: '#ffc107', sign: '-' },
        PURCHASE:     { label: 'Compra cartón',    icon: 'fa-ticket-alt',  color: '#dc3545', sign: '-' },
        PRIZE:        { label: '¡Premio! 🎉',      icon: 'fa-trophy',      color: '#fd7e14', sign: '+' },
        TRIVIA_ENTRY: { label: 'Entrada Trivia',   icon: 'fa-brain',       color: '#7c3aed', sign: '-' },
        TRIVIA_PRIZE: { label: '🏆 Premio Trivia', icon: 'fa-brain',       color: '#7c3aed', sign: '+' },
    };

    var ADMIN_META = {
        DEPOSIT:    { label: '💰 Depósito pendiente', icon: 'fa-arrow-down', color: '#28a745', sign: '+' },
        WITHDRAWAL: { label: '💸 Retiro pendiente',   icon: 'fa-arrow-up',   color: '#ffc107', sign: '-' },
    };

    function resolveResultMeta(type, status) {
        var ok = status === 'COMPLETED';
        if (type === 'DEPOSIT')    return ok
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
    var $balanceEl = document.getElementById('navbar-balance');
    var $clearBtn  = document.getElementById('notif-clear');
    var notifCount = 0;
    var panelOpened = false;

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
        return symbol + '\u00a0' + parseFloat(n).toLocaleString('es-VE', {
            minimumFractionDigits: 2, maximumFractionDigits: 2
        });
    }

    // ── Saldo navbar ──────────────────────────────────────────
    function updateBalance(data) {
        if (!$balanceEl) return;
        $balanceEl.innerHTML =
            '<span class="text-success font-weight-bold">' + data.currency_symbol + '</span>\u00a0' +
            parseFloat(data.balance).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        $balanceEl.style.transition = 'opacity .15s';
        $balanceEl.style.opacity = '0.2';
        setTimeout(function () { $balanceEl.style.opacity = '1'; }, 160);
    }

    // ── Badge ─────────────────────────────────────────────────
    function setBadge(n) {
        notifCount = Math.max(0, n);
        if (notifCount === 0) {
            $badge.classList.add('d-none');
        } else {
            $badge.textContent = notifCount > 99 ? '99+' : String(notifCount);
            $badge.classList.remove('d-none');
        }
    }
    function incBadge() { setBadge(notifCount + 1); }

    // ── Construir <li> ────────────────────────────────────────
    // variant: 'admin' (acción pendiente para admin/tesorero) | 'result' (depósito/retiro resuelto) | '' (movimiento normal)
    function buildListItem(meta, bodyHtml, time, url, notifId, variant) {
        var inner =
            '<div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 mt-1"' +
                 ' style="width:32px;height:32px;background:' + meta.color + '22">' +
              '<i class="fas ' + meta.icon + '" style="font-size:12px;color:' + meta.color + '"></i>' +
            '</div>' +
            '<div style="flex:1;min-width:0">' + bodyHtml + '</div>' +
            '<small class="text-muted flex-shrink-0" style="font-size:10px">' + time + '</small>';

        var li = document.createElement('li');
        li.className = 'border-bottom notif-entry' + (variant ? ' notif-entry--' + variant : '');
        if (notifId) li.setAttribute('data-notification-id', notifId);
        li.innerHTML = url
            ? '<a href="' + url + '" class="d-flex align-items-start px-3 py-2 text-decoration-none text-dark" style="gap:10px">' + inner + '</a>'
            : '<div class="d-flex align-items-start px-3 py-2" style="gap:10px">' + inner + '</div>';

        $list.insertBefore(li, $list.firstChild);
        var all = $list.querySelectorAll('.notif-entry');
        if (all.length > 25) all[all.length - 1].remove();
    }

    // ── Notificación en tiempo real (WebSocket wallet) ────────
    function addRealtimeWalletNotif(data) {
        var isResult = data.movement_status === 'COMPLETED' || data.movement_status === 'REJECTED';
        var meta = isResult
            ? resolveResultMeta(data.movement_type, data.movement_status)
            : (TYPE_META[data.movement_type] || { label: 'Movimiento', icon: 'fa-exchange-alt', color: '#6c757d', sign: '' });

        if ($empty) $empty.style.display = 'none';

        var detail = isResult
            ? (meta.sign
                ? '<span style="color:' + meta.color + ';font-weight:600">' + meta.sign + fmt(data.currency_symbol, data.amount) + '</span>'
                  + ' &middot; Saldo: ' + fmt(data.currency_symbol, data.balance)
                : 'El monto será devuelto a tu saldo disponible')
            : '<span style="color:' + meta.color + ';font-weight:600">' + meta.sign + fmt(data.currency_symbol, data.amount) + '</span>'
              + ' &middot; Saldo: ' + fmt(data.currency_symbol, data.balance);

        buildListItem(
            meta,
            '<p class="mb-0 font-weight-semibold" style="font-size:12px;line-height:1.4">' + meta.label + '</p>' +
            '<p class="mb-0 text-muted" style="font-size:11px">' + detail + '</p>',
            new Date().toLocaleTimeString('es-VE', { hour: '2-digit', minute: '2-digit' }),
            isResult ? (meta.url || '/wallet') : null,
            null,
            isResult ? 'result' : ''
        );
        incBadge();
    }

    // ── Notificación en tiempo real (WebSocket admin) ─────────
    function addRealtimeAdminNotif(data) {
        var meta  = ADMIN_META[data.movement_type] || { label: 'Movimiento pendiente', icon: 'fa-exchange-alt', color: '#6c757d', sign: '' };
        var label = data.movement_type === 'DEPOSIT' ? 'depósito' : 'retiro';

        if ($empty) $empty.style.display = 'none';

        buildListItem(
            meta,
            '<p class="mb-0 font-weight-semibold" style="font-size:12px;line-height:1.4">' + meta.label + '</p>' +
            '<p class="mb-0 text-muted" style="font-size:11px">' +
              '<strong>' + (data.user_name || 'Usuario') + '</strong> solicita un ' + label + ' de ' +
              '<span style="color:' + meta.color + ';font-weight:600">' + meta.sign + fmt(data.currency_symbol, data.amount) + '</span>' +
            '</p>',
            new Date().toLocaleTimeString('es-VE', { hour: '2-digit', minute: '2-digit' }),
            data.url || '/wallet-movements',
            null,
            'admin'
        );
        incBadge();

        // Sonido sutil
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

    // ── Notificación persistente (desde DB) ───────────────────
    function addPersistentNotification(notification) {
        if ($empty) $empty.style.display = 'none';

        var isAdminAlert = notification.type === 'WALLET_ADMIN_ALERT';
        var mvType   = notification.data && notification.data.movement_type;
        var mvStatus = notification.data && notification.data.movement_status;
        var isResult = mvStatus === 'COMPLETED' || mvStatus === 'REJECTED';
        var url      = (notification.data && notification.data.url) || (isResult ? '/wallet' : null);

        var meta;
        if (isAdminAlert) {
            meta = ADMIN_META[mvType] || { label: notification.title, icon: 'fa-bell', color: '#6c757d' };
        } else if (isResult) {
            meta = resolveResultMeta(mvType, mvStatus);
        } else {
            meta = TYPE_META[mvType] || { label: notification.title || 'Notificación', icon: 'fa-bell', color: '#6c757d' };
        }

        buildListItem(
            meta,
            '<p class="mb-0 font-weight-semibold" style="font-size:12px;line-height:1.4">' + (meta.label || notification.title) + '</p>' +
            '<p class="mb-0 text-muted" style="font-size:11px">' + notification.message + '</p>',
            new Date(notification.created_at).toLocaleTimeString('es-VE', { hour: '2-digit', minute: '2-digit' }),
            url,
            notification.id,
            isAdminAlert ? 'admin' : (isResult ? 'result' : '')
        );
    }

    // ── Estado del panel ──────────────────────────────────────
    var panelLoaded = false; // se cargaron datos desde API al menos una vez
    var panelOpen   = false; // panel actualmente abierto

    // ── Cargar no leídas desde API (lazy: solo al abrir) ──────
    function loadNotificationsFromAPI(callback) {
        apiFetch('/notifications/unread?limit=25')
            .then(function(r) { return r.ok ? r.json() : null; })
            .then(function(json) {
                if (!json) return;
                $list.querySelectorAll('.notif-entry').forEach(function(el) { el.remove(); });
                if ($empty) $empty.style.display = '';
                setBadge(0);
                if (json.data && json.data.length > 0) {
                    json.data.slice().reverse().forEach(addPersistentNotification);
                    setBadge(json.unread_count || json.data.length);
                }
                panelLoaded = true;
                if (callback) callback();
            })
            .catch(function() { if (callback) callback(); });
    }

    // ── Marcar leídas (sin borrar) ────────────────────────────
    function markAllReadInAPI() {
        apiFetch('/notifications/mark-all-read', 'POST').catch(function() {});
    }

    // ── "Limpiar todo": borrar de DB ──────────────────────────
    $clearBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        apiFetch('/notifications', 'DELETE')
            .then(function(r) { return r.ok ? r.json() : null; })
            .then(function(json) {
                if (json !== null) {
                    $list.querySelectorAll('.notif-entry').forEach(function(el) { el.remove(); });
                    if ($empty) $empty.style.display = '';
                    setBadge(0);
                    panelLoaded = false; // forzar recarga la próxima vez
                }
            })
            .catch(function() {});
    });

    // ── Abrir panel: lazy load + marcar leídas ────────────────
    document.getElementById('notifToggle').addEventListener('click', function () {
        if (panelOpen) { panelOpen = false; return; }
        panelOpen = true;

        // Primera apertura: cargar desde DB, luego marcar leídas
        if (!panelLoaded) {
            loadNotificationsFromAPI(function () {
                if (notifCount > 0) { markAllReadInAPI(); setBadge(0); }
            });
        } else if (notifCount > 0) {
            // Panel ya cargado: solo marcar leídas
            markAllReadInAPI();
            setBadge(0);
        }

        // Resetear flag cuando el dropdown se cierra
        document.addEventListener('click', function onOutside(ev) {
            if (!ev.target.closest('.nb-notif-menu') && !ev.target.closest('#notifToggle')) {
                panelOpen = false;
                document.removeEventListener('click', onOutside);
            }
        });
    });

    // ── wallet:updated desde Vue ──────────────────────────────
    window.addEventListener('wallet:updated', function (e) {
        updateBalance({ balance: e.detail.balance, currency_symbol: e.detail.currency_symbol });
    });

    // ── WebSocket ─────────────────────────────────────────────
    function boot() {
        if (!window.Echo) { setTimeout(boot, 250); return; }

        // Canal privado del usuario (wallet events)
        window.Echo.private('App.Models.User.' + USER_ID)
            .listen('.wallet.updated', function (data) {
                if (WALLET_ID && data.wallet_id !== WALLET_ID) return;
                if (WALLET_ID) updateBalance(data);
                addRealtimeWalletNotif(data);
            });

        // Canal de admins (movimientos pendientes)
        if (IS_ADMIN) {
            window.Echo.private('admins.wallet-movements')
                .listen('.wallet.movement.requested', function (data) {
                    addRealtimeAdminNotif(data);
                });
        }
    }

    // ── Badge inicial desde PHP — sin ninguna petición HTTP ───
    var serverCount = window.notificationsData && window.notificationsData.unreadCount
        ? parseInt(window.notificationsData.unreadCount, 10) : 0;
    if (serverCount > 0) setBadge(serverCount);

    // ── Init: solo WebSocket, nada de fetch al cargar ─────────
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

})();
</script>
@endauth
