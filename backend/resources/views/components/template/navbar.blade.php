{{-- $navWallet y $navUser son inyectados por NavbarComposer vía AppServiceProvider --}}
<nav class="main-header navbar navbar-expand navbar-bg justify-content-between">

    {{-- ── Izquierda: toggle + billetera ──────────────────── --}}
    <ul class="navbar-nav align-items-center" style="gap:.25rem;">

        {{-- Toggle sidebar --}}
        <li class="nav-item">
            <a class="nb-icon-btn" data-widget="pushmenu" href="#" role="button" aria-label="Menú">
                <i class="fas fa-bars"></i>
            </a>
        </li>

        {{-- Billetera --}}
        <li class="nav-item">
            <a class="nb-wallet-pill" href="{{ route('wallet.index') }}" title="Mi billetera">
                <span class="nb-wallet-icon"><i class="fas fa-wallet"></i></span>
                @if ($navWallet)
                    <span id="navbar-balance" class="nb-wallet-balance">
                        <span class="nb-wallet-symbol">{{ $navWallet->currency->symbol }}</span>{{ number_format($navWallet->balance, $navWallet->currency->decimal_places ?? 2, ',', '.') }}
                    </span>
                @else
                    <span class="nb-wallet-empty">Sin billetera</span>
                @endif
            </a>
        </li>

    </ul>

    {{-- ── Derecha: notificaciones + usuario ───────────────── --}}
    <ul class="navbar-nav align-items-center" style="gap:.5rem;">

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

{{-- ── WebSocket: billetera + notificaciones en tiempo real ── --}}
@if ($navWallet)
<script>
(function () {
    'use strict';

    var USER_ID   = @json((string) Auth::id());
    var WALLET_ID = @json((string) $navWallet->id);

    // ── Metadatos por tipo de movimiento (sin status) ────────
    var TYPE_META = {
        DEPOSIT:    { label: 'Depósito',      icon: 'fa-arrow-down',  color: '#28a745', sign: '+' },
        WITHDRAWAL: { label: 'Retiro',         icon: 'fa-arrow-up',    color: '#ffc107', sign: '-' },
        PURCHASE:   { label: 'Compra cartón',  icon: 'fa-ticket-alt',  color: '#dc3545', sign: '-' },
        PRIZE:      { label: '¡Premio! 🎉',    icon: 'fa-trophy',      color: '#fd7e14', sign: '+' },
    };

    // ── Metadatos según tipo + estado (aprobado/rechazado) ───
    function resolveResultMeta(type, status) {
        var approved = status === 'COMPLETED';
        if (type === 'DEPOSIT') {
            return approved
                ? { label: '✅ Depósito aprobado',          icon: 'fa-check-circle', color: '#28a745', sign: '+', url: '/wallet' }
                : { label: '❌ Depósito rechazado',          icon: 'fa-times-circle', color: '#dc3545', sign: '',  url: '/wallet' };
        }
        if (type === 'WITHDRAWAL') {
            return approved
                ? { label: '✅ Retiro procesado',            icon: 'fa-check-circle', color: '#28a745', sign: '-', url: '/wallet' }
                : { label: '↩️ Retiro rechazado — saldo devuelto', icon: 'fa-undo',  color: '#ffc107', sign: '',  url: '/wallet' };
        }
        return { label: 'Movimiento actualizado', icon: 'fa-exchange-alt', color: '#6c757d', sign: '', url: '/wallet' };
    }

    // ── DOM refs ──────────────────────────────────────────────
    var $badge     = document.getElementById('notif-badge');
    var $list      = document.getElementById('notif-list');
    var $empty     = document.getElementById('notif-empty');
    var $balanceEl = document.getElementById('navbar-balance');
    var $clearBtn  = document.getElementById('notif-clear');
    var notifCount = 0;
    var panelOpened = false; // evita marcar-leído múltiples veces por tick

    // ── CSRF helper ───────────────────────────────────────────
    function csrfToken() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }
    function apiFetch(url, method, body) {
        return fetch(url, {
            method: method || 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: body ? JSON.stringify(body) : undefined,
        });
    }

    // ── Formateador ───────────────────────────────────────────
    function fmt(symbol, n) {
        return symbol + '\u00a0' + parseFloat(n).toLocaleString('es-VE', {
            minimumFractionDigits: 2, maximumFractionDigits: 2
        });
    }

    // ── Actualizar saldo en navbar ────────────────────────────
    function updateBalance(data) {
        if (!$balanceEl) return;
        var formatted = parseFloat(data.balance).toLocaleString('es-VE', {
            minimumFractionDigits: 2, maximumFractionDigits: 2
        });
        $balanceEl.innerHTML =
            '<span class="text-success font-weight-bold">' + data.currency_symbol + '</span>\u00a0' + formatted;
        $balanceEl.style.transition = 'opacity .15s ease';
        $balanceEl.style.opacity    = '0.2';
        setTimeout(function () { $balanceEl.style.opacity = '1'; }, 160);
    }

    // ── Badge helpers ─────────────────────────────────────────
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

    // ── Construir item de lista ───────────────────────────────
    function buildListItem(meta, bodyHtml, time, url, notifId) {
        var inner =
            '<div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 mt-1" ' +
                 'style="width:32px;height:32px;background:' + meta.color + '22">' +
              '<i class="fas ' + meta.icon + '" style="font-size:12px;color:' + meta.color + '"></i>' +
            '</div>' +
            '<div style="flex:1;min-width:0">' + bodyHtml + '</div>' +
            '<small class="text-muted flex-shrink-0" style="font-size:10px">' + time + '</small>';

        var li = document.createElement('li');
        li.className = 'border-bottom notif-entry';
        if (notifId) li.setAttribute('data-notification-id', notifId);

        if (url) {
            li.innerHTML = '<a href="' + url + '" class="d-flex align-items-start px-3 py-2 text-decoration-none text-dark" style="gap:10px">' + inner + '</a>';
        } else {
            li.innerHTML = '<div class="d-flex align-items-start px-3 py-2" style="gap:10px">' + inner + '</div>';
        }

        $list.insertBefore(li, $list.firstChild);
        // Máximo 25 entradas
        var entries = $list.querySelectorAll('.notif-entry');
        if (entries.length > 25) entries[entries.length - 1].remove();
    }

    // ── Agregar notificación en tiempo real (WebSocket) ───────
    function addRealtimeNotification(data) {
        var isResult = data.movement_status === 'COMPLETED' || data.movement_status === 'REJECTED';
        var meta = isResult
            ? resolveResultMeta(data.movement_type, data.movement_status)
            : (TYPE_META[data.movement_type] || { label: 'Movimiento', icon: 'fa-exchange-alt', color: '#6c757d', sign: '', url: null });

        if ($empty) $empty.style.display = 'none';

        var detail = isResult
            ? (meta.sign
                ? '<span style="color:' + meta.color + ';font-weight:600">' + meta.sign + fmt(data.currency_symbol, data.amount) + '</span>'
                  + ' &middot; Saldo: ' + fmt(data.currency_symbol, data.balance)
                : 'El monto será devuelto a tu saldo disponible')
            : '<span style="color:' + meta.color + ';font-weight:600">' + meta.sign + fmt(data.currency_symbol, data.amount) + '</span>'
              + ' &middot; Saldo: ' + fmt(data.currency_symbol, data.balance);

        var body =
            '<p class="mb-0 font-weight-semibold" style="font-size:12px;line-height:1.4">' + meta.label + '</p>' +
            '<p class="mb-0 text-muted" style="font-size:11px">' + detail + '</p>';

        buildListItem(
            meta, body,
            new Date().toLocaleTimeString('es-VE', { hour: '2-digit', minute: '2-digit' }),
            isResult ? (meta.url || '/wallet') : null,
            null
        );
        incBadge();
    }

    // ── Agregar notificación persistente (cargada desde DB) ───
    function addPersistentNotification(notification) {
        if ($empty) $empty.style.display = 'none';

        var isAdminAlert = notification.type === 'WALLET_ADMIN_ALERT';
        var mvType   = notification.data && notification.data.movement_type;
        var mvStatus = notification.data && notification.data.movement_status;
        var isResult = mvStatus === 'COMPLETED' || mvStatus === 'REJECTED';
        var notifUrl = (notification.data && notification.data.url) || (isResult ? '/wallet' : null);

        var meta;
        if (isAdminAlert) {
            meta = mvType === 'DEPOSIT'
                ? { label: '💰 Depósito pendiente', icon: 'fa-arrow-down', color: '#28a745' }
                : { label: '💸 Retiro pendiente',   icon: 'fa-arrow-up',   color: '#ffc107' };
        } else if (isResult) {
            meta = resolveResultMeta(mvType, mvStatus);
        } else {
            meta = TYPE_META[mvType] || { label: notification.title || 'Notificación', icon: 'fa-bell', color: '#6c757d' };
        }

        var body =
            '<p class="mb-0 font-weight-semibold" style="font-size:12px;line-height:1.4">' + (meta.label || notification.title) + '</p>' +
            '<p class="mb-0 text-muted" style="font-size:11px">' + notification.message + '</p>';

        buildListItem(
            meta, body,
            new Date(notification.created_at).toLocaleTimeString('es-VE', { hour: '2-digit', minute: '2-digit' }),
            notifUrl,
            notification.id
        );
    }

    // ── Cargar notificaciones no leídas desde la API ──────────
    function loadNotificationsFromAPI() {
        apiFetch('/notifications/unread?limit=20')
            .then(function(r) { return r.ok ? r.json() : null; })
            .then(function(json) {
                if (!json) return;
                // Limpiar lista antes de renderizar
                $list.querySelectorAll('.notif-entry').forEach(function(el) { el.remove(); });
                if ($empty) $empty.style.display = '';
                setBadge(0);

                if (json.data && json.data.length > 0) {
                    // Invertir para que el más reciente quede arriba
                    json.data.slice().reverse().forEach(function(n) {
                        addPersistentNotification(n);
                    });
                    setBadge(json.unread_count || json.data.length);
                }
            })
            .catch(function() { /* silencioso */ });
    }

    // ── Marcar todas como leídas en la API (sin borrar) ──────
    function markAllReadInAPI() {
        apiFetch('/notifications/mark-all-read', 'POST')
            .then(function(r) { return r.ok ? r.json() : null; })
            .then(function(json) {
                if (json) setBadge(0);
            })
            .catch(function() {});
    }

    // ── "Limpiar todo": eliminar de DB + limpiar UI ───────────
    $clearBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        apiFetch('/notifications', 'DELETE')
            .then(function(r) { return r.ok ? r.json() : null; })
            .then(function(json) {
                if (json !== null) {
                    $list.querySelectorAll('.notif-entry').forEach(function(el) { el.remove(); });
                    if ($empty) $empty.style.display = '';
                    setBadge(0);
                }
            })
            .catch(function() {});
    });

    // ── Abrir panel: marcar todo como leído en API ────────────
    document.getElementById('notifToggle').addEventListener('click', function () {
        if (notifCount > 0 && !panelOpened) {
            panelOpened = true;
            setTimeout(function () {
                markAllReadInAPI();
                setBadge(0);
                panelOpened = false;
            }, 300);
        }
    });

    // ── Actualización directa desde Vue (wallet) ──────────────
    window.addEventListener('wallet:updated', function (e) {
        var d = e.detail;
        updateBalance({ balance: d.balance, currency_symbol: d.currency_symbol });
    });

    // ── WebSocket: Reverb / Echo ──────────────────────────────
    function boot() {
        if (!window.Echo) { setTimeout(boot, 250); return; }

        window.Echo.private('App.Models.User.' + USER_ID)
            .listen('.wallet.updated', function (data) {
                if (data.wallet_id !== WALLET_ID) return;
                updateBalance(data);
                addRealtimeNotification(data);
            });
    }

    // ── Inicializar badge con conteo del servidor (antes de API) ─
    (function initBadge() {
        var serverCount = window.notificationsData && window.notificationsData.unreadCount
            ? parseInt(window.notificationsData.unreadCount, 10)
            : 0;
        if (serverCount > 0) setBadge(serverCount);
    })();

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            loadNotificationsFromAPI();
            boot();
        });
    } else {
        loadNotificationsFromAPI();
        boot();
    }

})();
</script>
@endif

{{-- ── Notificaciones de movimientos pendientes para Admins/Superadmins ── --}}
@if(auth()->check() && auth()->user()->hasRole(['admin', 'superadmin']))
<script>
(function () {
    'use strict';

    var $badge  = document.getElementById('notif-badge');
    var $list   = document.getElementById('notif-list');
    var $empty  = document.getElementById('notif-empty');

    // ── Metadatos por tipo ──────────────────────────────────────
    var ADMIN_META = {
        DEPOSIT:    { label: 'Depósito pendiente',  icon: 'fa-arrow-down',  color: '#28a745', sign: '+' },
        WITHDRAWAL: { label: 'Retiro pendiente',     icon: 'fa-arrow-up',    color: '#ffc107', sign: '-' },
    };

    // ── Formateador ─────────────────────────────────────────────
    function fmt(symbol, n) {
        return symbol + '\u00a0' + parseFloat(n).toLocaleString('es-VE', {
            minimumFractionDigits: 2, maximumFractionDigits: 2
        });
    }

    // ── Agregar notificación de movimiento al panel ─────────────
    function addAdminMovementNotification(data) {
        var meta  = ADMIN_META[data.movement_type] || { label: 'Movimiento', icon: 'fa-exchange-alt', color: '#6c757d', sign: '' };
        var time  = new Date().toLocaleTimeString('es-VE', { hour: '2-digit', minute: '2-digit' });
        var label = data.movement_type === 'DEPOSIT' ? 'depósito' : 'retiro';

        if ($empty) $empty.style.display = 'none';

        var li = document.createElement('li');
        li.className = 'border-bottom notif-entry notif-admin-wallet cursor-pointer';
        li.setAttribute('data-url', data.url || '/wallet-movements');

        li.innerHTML =
            '<a href="' + (data.url || '/wallet-movements') + '" class="d-flex align-items-start px-3 py-2 text-decoration-none text-dark" style="gap:10px">' +
              '<div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 mt-1" ' +
                   'style="width:32px;height:32px;background:' + meta.color + '22">' +
                '<i class="fas ' + meta.icon + '" style="font-size:12px;color:' + meta.color + '"></i>' +
              '</div>' +
              '<div style="flex:1;min-width:0">' +
                '<p class="mb-0 font-weight-semibold text-dark" style="font-size:12px;line-height:1.4">' +
                  meta.label +
                '</p>' +
                '<p class="mb-0 text-muted" style="font-size:11px">' +
                  '<strong>' + (data.user_name || 'Usuario') + '</strong>' +
                  ' solicita un ' + label + ' de ' +
                  '<span style="color:' + meta.color + ';font-weight:600">' +
                    meta.sign + fmt(data.currency_symbol, data.amount) +
                  '</span>' +
                '</p>' +
              '</div>' +
              '<small class="text-muted flex-shrink-0" style="font-size:10px">' + time + '</small>' +
            '</a>';

        $list.insertBefore(li, $list.firstChild);

        // Máximo 20 entradas
        var entries = $list.querySelectorAll('.notif-entry');
        if (entries.length > 20) entries[entries.length - 1].remove();

        // Badge
        var currentCount = parseInt($badge.textContent || '0', 10);
        if ($badge.classList.contains('d-none')) currentCount = 0;
        currentCount++;
        $badge.textContent = currentCount > 99 ? '99+' : currentCount;
        $badge.classList.remove('d-none');

        // Sonido de notificación (sutil)
        try {
            var ctx = new (window.AudioContext || window.webkitAudioContext)();
            var osc = ctx.createOscillator();
            var gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.value = 880;
            gain.gain.setValueAtTime(0.1, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.3);
            osc.start(ctx.currentTime);
            osc.stop(ctx.currentTime + 0.3);
        } catch(e) {}
    }

    // ── WebSocket: escucha canal de admins ──────────────────────
    function bootAdmin() {
        if (!window.Echo) { setTimeout(bootAdmin, 250); return; }

        window.Echo.private('admins.wallet-movements')
            .listen('.wallet.movement.requested', function (data) {
                addAdminMovementNotification(data);
            });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootAdmin);
    } else {
        bootAdmin();
    }

})();
</script>
@endif
