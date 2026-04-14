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

    // ── Metadatos por tipo de movimiento ─────────────────────
    var TYPE_META = {
        DEPOSIT:    { label: 'Depósito recibido',   icon: 'fa-arrow-down',  color: '#28a745', sign: '+' },
        WITHDRAWAL: { label: 'Retiro procesado',     icon: 'fa-arrow-up',    color: '#ffc107', sign: '-' },
        PURCHASE:   { label: 'Compra de cartón',     icon: 'fa-ticket-alt',  color: '#dc3545', sign: '-' },
        PRIZE:      { label: '¡Premio ganado! 🎉',    icon: 'fa-trophy',      color: '#fd7e14', sign: '+' },
    };

    // ── DOM refs ──────────────────────────────────────────────
    var $badge     = document.getElementById('notif-badge');
    var $list      = document.getElementById('notif-list');
    var $empty     = document.getElementById('notif-empty');
    var $balanceEl = document.getElementById('navbar-balance');
    var notifCount = 0;

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

    // ── Agregar notificación al panel ─────────────────────────
    function addNotification(data) {
        var meta = TYPE_META[data.movement_type] || { label: 'Movimiento', icon: 'fa-exchange-alt', color: '#6c757d', sign: '' };
        var time = new Date().toLocaleTimeString('es-VE', { hour: '2-digit', minute: '2-digit' });

        if ($empty) $empty.style.display = 'none';

        var li = document.createElement('li');
        li.className = 'border-bottom notif-entry';
        li.innerHTML =
            '<div class="d-flex align-items-start px-3 py-2" style="gap:10px">' +
              '<div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 mt-1" ' +
                   'style="width:32px;height:32px;background:' + meta.color + '22">' +
                '<i class="fas ' + meta.icon + '" style="font-size:12px;color:' + meta.color + '"></i>' +
              '</div>' +
              '<div style="flex:1;min-width:0">' +
                '<p class="mb-0 font-weight-semibold" style="font-size:12px;line-height:1.4">' + meta.label + '</p>' +
                '<p class="mb-0" style="font-size:11px">' +
                  '<span style="color:' + meta.color + ';font-weight:600">' + meta.sign + fmt(data.currency_symbol, data.amount) + '</span>' +
                  ' &middot; Saldo: ' + fmt(data.currency_symbol, data.balance) +
                '</p>' +
              '</div>' +
              '<small class="text-muted flex-shrink-0" style="font-size:10px">' + time + '</small>' +
            '</div>';

        $list.insertBefore(li, $list.firstChild);

        // Máximo 20 entradas
        var entries = $list.querySelectorAll('.notif-entry');
        if (entries.length > 20) entries[entries.length - 1].remove();

        // Actualizar badge
        notifCount++;
        $badge.textContent = notifCount > 99 ? '99+' : notifCount;
        $badge.classList.remove('d-none');
    }

    // ── Limpiar panel ─────────────────────────────────────────
    document.getElementById('notif-clear').addEventListener('click', function (e) {
        e.stopPropagation();
        clearAllNotifications();
    });

    // ── Marcar todas como leídas ─────────────────────────────
    async function clearAllNotifications() {
        try {
            // Llamar a API para marcar todas como leídas
            const response = await fetch('/notifications/mark-all-read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                }
            });

            if (response.ok) {
                // Limpiar UI
                $list.querySelectorAll('.notif-entry').forEach(function (el) { el.remove(); });
                if ($empty) $empty.style.display = '';
                notifCount = 0;
                $badge.classList.add('d-none');

                // Actualizar datos globales
                if (window.notificationsData) {
                    window.notificationsData.unreadCount = 0;
                    window.notificationsData.notifications = [];
                }

                console.log('✅ Todas las notificaciones marcadas como leídas');
            } else {
                console.error('❌ Error al marcar notificaciones como leídas');
            }
        } catch (error) {
            console.error('❌ Error en clearAllNotifications:', error);
        }
    }

    // ── Marcar notificación individual como leída ─────────────
    async function markNotificationAsRead(notificationId, element) {
        try {
            const response = await fetch(`/notifications/${notificationId}/mark-read`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                }
            });

            if (response.ok) {
                // Reducir contador
                notifCount = Math.max(0, notifCount - 1);
                if (notifCount === 0) {
                    $badge.classList.add('d-none');
                } else {
                    $badge.textContent = notifCount > 99 ? '99+' : notifCount;
                }

                // Marcar como leída visualmente
                element.classList.add('read');

                console.log(`✅ Notificación ${notificationId} marcada como leída`);
            } else {
                console.error(`❌ Error al marcar notificación ${notificationId} como leída`);
            }
        } catch (error) {
            console.error(`❌ Error en markNotificationAsRead:`, error);
        }
    }

    // Resetear badge al abrir panel
    document.getElementById('notifToggle').addEventListener('click', function () {
        setTimeout(function () {
            notifCount = 0;
            $badge.classList.add('d-none');
        }, 400);
    });

    // ── Actualización directa desde Vue (sin WebSocket) ──────
    // useWallet.ts emite este evento al completar deposit/withdraw
    window.addEventListener('wallet:updated', function (e) {
        var d = e.detail;
        updateBalance({
            balance:         d.balance,
            currency_symbol: d.currency_symbol,
        });
    });

    // ── Cargar notificaciones persistentes ─────────────────────
    function loadPersistentNotifications() {
        if (window.notificationsData && window.notificationsData.notifications) {
            console.log('🔔 Cargando notificaciones persistentes en navbar:', window.notificationsData.unreadCount);

            var notifications = window.notificationsData.notifications;
            notifCount = window.notificationsData.unreadCount;

            // Actualizar badge
            if (notifCount > 0) {
                $badge.textContent = notifCount > 99 ? '99+' : notifCount;
                $badge.classList.remove('d-none');
            }

            // Agregar cada notificación persistente al navbar
            notifications.forEach(function(notification) {
                addPersistentNotification(notification);
            });
        }
    }

    // ── Agregar notificación persistente al navbar ───────────────
    function addPersistentNotification(notification) {
        if ($empty) $empty.style.display = 'none';

        var isAdminAlert = notification.type === 'WALLET_ADMIN_ALERT';
        var notifUrl = notification.data && notification.data.url ? notification.data.url : null;

        var meta;
        if (isAdminAlert) {
            var mvType = notification.data && notification.data.movement_type;
            meta = mvType === 'DEPOSIT'
                ? { label: '💰 Depósito pendiente', icon: 'fa-arrow-down', color: '#28a745', sign: '+' }
                : { label: '💸 Retiro pendiente',   icon: 'fa-arrow-up',   color: '#ffc107', sign: '-' };
        } else {
            meta = TYPE_META[notification.data && notification.data.movement_type] || {
                label: notification.title,
                icon:  'fa-exchange-alt',
                color: '#6c757d',
                sign:  ''
            };
        }

        var time = new Date(notification.created_at).toLocaleTimeString('es-VE', {
            hour: '2-digit',
            minute: '2-digit'
        });

        var innerContent =
            '<div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 mt-1" ' +
                 'style="width:32px;height:32px;background:' + meta.color + '22">' +
              '<i class="fas ' + meta.icon + '" style="font-size:12px;color:' + meta.color + '"></i>' +
            '</div>' +
            '<div style="flex:1;min-width:0">' +
              '<p class="mb-0 font-weight-semibold" style="font-size:12px;line-height:1.4">' + (isAdminAlert ? meta.label : notification.title) + '</p>' +
              '<p class="mb-0 text-muted" style="font-size:11px">' + notification.message + '</p>' +
            '</div>' +
            '<small class="text-muted flex-shrink-0" style="font-size:10px">' + time + '</small>';

        var li = document.createElement('li');
        li.className = 'border-bottom notif-entry cursor-pointer';
        li.setAttribute('data-notification-id', notification.id);

        if (notifUrl) {
            // Notificación con link (admin)
            li.innerHTML = '<a href="' + notifUrl + '" class="d-flex align-items-start px-3 py-2 text-decoration-none text-dark" style="gap:10px">' +
                innerContent + '</a>';
            li.addEventListener('click', function() {
                markNotificationAsRead(notification.id, li);
            });
        } else {
            // Notificación sin link (usuario)
            li.innerHTML = '<div class="d-flex align-items-start px-3 py-2" style="gap:10px">' + innerContent + '</div>';
            li.addEventListener('click', function() {
                markNotificationAsRead(notification.id, li);
            });
        }

        $list.insertBefore(li, $list.firstChild);

        // Máximo 20 entradas
        var entries = $list.querySelectorAll('.notif-entry');
        if (entries.length > 20) entries[entries.length - 1].remove();
    }

    // ── WebSocket: Reverb / Echo ──────────────────────────────
    // Complementario: también escucha el canal privado si Reverb corre.
    // echo.ts asigna window.Echo al cargar el bundle Vite (async).
    function boot() {
        if (!window.Echo) { setTimeout(boot, 250); return; }

        window.Echo.private('App.Models.User.' + USER_ID)
            .listen('.wallet.updated', function (data) {
                if (data.wallet_id !== WALLET_ID) return;
                updateBalance(data);
                addNotification(data);
            });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            loadPersistentNotifications();
            boot();
        });
    } else {
        loadPersistentNotifications();
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
