{{-- $navWallet y $navUser son inyectados por NavbarComposer vía AppServiceProvider --}}
<nav class="main-header navbar navbar-expand navbar-bg justify-content-between">

    {{-- ── Izquierda: toggle + billetera ──────────────────── --}}
    <ul class="navbar-nav align-items-center">

        <li class="nav-item ml-n2 mr-1">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button" aria-label="Menú">
                <i class="fas fa-bars"></i>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link d-flex align-items-center" href="{{ route('wallet.index') }}" title="Mi billetera"
               style="gap:6px">
                <i class="fas fa-wallet text-success" style="font-size:13px"></i>
                @if ($navWallet)
                    <span id="navbar-balance" class="text-light" style="font-size:14px">
                        <span class="text-success font-weight-bold">{{ $navWallet->currency->symbol }}</span>
                        {{ number_format($navWallet->balance, $navWallet->currency->decimal_places ?? 2, ',', '.') }}
                    </span>
                @else
                    <span class="text-muted" style="font-size:13px">Sin billetera</span>
                @endif
            </a>
        </li>

    </ul>

    {{-- ── Derecha: notificaciones + usuario ───────────────── --}}
    <ul class="navbar-nav align-items-center">

        {{-- 🔔 Notificaciones --}}
        <li class="nav-item dropdown">
            <a class="nav-link position-relative px-3" href="#" id="notifToggle"
               data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
               title="Notificaciones">
                <i class="fas fa-bell" style="font-size:16px"></i>
                <span id="notif-badge"
                      class="badge badge-danger badge-pill position-absolute d-none"
                      style="top:4px;right:4px;font-size:9px;min-width:16px;padding:2px 4px">
                    0
                </span>
            </a>

            <div class="dropdown-menu dropdown-menu-right p-0 shadow"
                 aria-labelledby="notifToggle"
                 style="width:320px;max-height:420px;overflow:hidden;border-radius:10px">

                <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom"
                     style="background:#f8f9fa">
                    <span class="font-weight-semibold" style="font-size:13px">
                        <i class="fas fa-bell text-primary mr-1"></i>Notificaciones
                    </span>
                    <button type="button" id="notif-clear"
                            class="btn btn-link btn-sm text-muted p-0" style="font-size:11px">
                        Limpiar todo
                    </button>
                </div>

                <ul id="notif-list" class="list-unstyled mb-0"
                    style="max-height:340px;overflow-y:auto">
                    <li id="notif-empty" class="text-center text-muted py-5" style="font-size:13px">
                        <i class="fas fa-check-circle text-success d-block mb-2" style="font-size:24px"></i>
                        Sin notificaciones nuevas
                    </li>
                </ul>
            </div>
        </li>

        {{-- 👤 Usuario --}}
        <li class="nav-item dropdown">
            <button class="btn btn-sm btn-outline-primary dropdown-toggle d-flex align-items-center"
                    type="button" id="userDropdown"
                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                    aria-label="Opciones de usuario">
                <img class="mr-2 user-img"
                     src="{{ $navUser->profile_photo_url ?? ($navUser->foto ? asset('img/users/' . $navUser->foto) : asset('img/fav.ico')) }}"
                     alt="{{ $navUser->username }}"
                     style="width:32px;height:32px;object-fit:cover;border-radius:50%;border:2px solid #667eea;">
                <div class="text-left">
                    <div class="user-text-navbar font-weight-bold">{{ $navUser->username }}</div>
                    <div class="text-muted" style="font-size:10px;line-height:1">Mi cuenta</div>
                </div>
            </button>
            
            <div class="dropdown-menu dropdown-menu-right p-0 shadow-lg border-0" 
                 aria-labelledby="userDropdown" 
                 style="font-size:13px;min-width:220px;border-radius:12px;overflow:hidden;">
                
                {{-- Header compacto --}}
                <div class="px-3 py-3 bg-light border-bottom">
                    <div class="d-flex align-items-center">
                        <img class="user-img"
                             src="{{ $navUser->profile_photo_url ?? ($navUser->foto ? asset('img/users/' . $navUser->foto) : asset('img/fav.ico')) }}"
                             alt="{{ $navUser->username }}"
                             style="width:36px;height:36px;object-fit:cover;border-radius:50%;border:2px solid #fff;margin-right:10px;flex-shrink:0;">
                        <div class="flex-1">
                            <div class="fw-medium">{{ $navUser->username }}</div>
                            <div class="text-muted small">{{ $navUser->email ?? 'usuario@playrke.com' }}</div>
                        </div>
                        @if($navUser->email_verified_at)
                            <i class="fas fa-check-circle text-success ms-1" style="font-size:12px;"></i>
                        @endif
                    </div>
                </div>
                
                {{-- Opciones simplificadas --}}
                <div class="py-1">
                    <a href="{{ route('profile.index') }}" 
                       class="dropdown-item d-flex align-items-center px-3 py-2 hover:bg-gray-100 transition-all">
                        <i class="fas fa-user me-3 text-muted" style="width:16px;"></i>
                        Mi Perfil
                    </a>
                    
                    <a href="{{ route('wallet.index') }}" 
                       class="dropdown-item d-flex align-items-center px-3 py-2 hover:bg-gray-100 transition-all">
                        <i class="fas fa-wallet me-3 text-muted" style="width:16px;"></i>
                        Mi Billetera
                    </a>
                </div>
                
                {{-- Separador --}}
                <div class="border-top mx-3" style="border-color: #e9ecef;"></div>
                <div class="border-top border-light"></div>
                
                {{-- Cerrar sesión --}}
                <div class="py-2">
                    <button type="button" 
                            class="logout-btn dropdown-item d-flex align-items-center px-3 py-2 hover:bg-danger hover:text-white transition-all w-100 text-left border-0 bg-transparent text-danger"
                            data-logout-url="{{ route('logout') }}"
                            data-login-redirect-url="{{ route('login') }}"
                            data-show-confirmation="false">
                        <i class="fas fa-sign-out-alt mr-3" style="width:16px;"></i>
                        <div class="flex-1">
                            <div class="font-weight-medium">Cerrar Sesión</div>
                            <small class="text-danger opacity-75">Salir de tu cuenta</small>
                        </div>
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

        var meta = TYPE_META[notification.data.movement_type] || { 
            label: notification.title, 
            icon: 'fa-exchange-alt', 
            color: '#6c757d', 
            sign: '' 
        };
        var time = new Date(notification.created_at).toLocaleTimeString('es-VE', { 
            hour: '2-digit', 
            minute: '2-digit' 
        });

        var li = document.createElement('li');
        li.className = 'border-bottom notif-entry cursor-pointer';
        li.setAttribute('data-notification-id', notification.id);
        li.innerHTML =
            '<div class="d-flex align-items-start px-3 py-2" style="gap:10px">' +
                  '<div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 mt-1" ' +
                       'style="width:32px;height:32px;background:' + meta.color + '22">' +
                  '<i class="fas ' + meta.icon + '" style="font-size:12px;color:' + meta.color + '"></i>' +
                  '</div>' +
                  '<div style="flex:1;min-width:0">' +
                    '<p class="mb-0 font-weight-semibold" style="font-size:12px;line-height:1.4">' + meta.label + '</p>' +
                    '<p class="mb-0" style="font-size:11px">' +
                      notification.message +
                      ' &middot; Saldo: ' + fmt(notification.data.currency_symbol, notification.data.balance) +
                    '</p>' +
                  '</div>' +
                  '<small class="text-muted flex-shrink-0" style="font-size:10px">' + time + '</small>' +
                '</div>';

        // Agregar evento click para marcar como leída
        li.addEventListener('click', function() {
            markNotificationAsRead(notification.id, li);
        });

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
</script>

<style>
@keyframes notifSlide {
    from { opacity: 0; transform: translateY(-6px); }
    to   { opacity: 1; transform: translateY(0); }
}
.notif-entry { 
    animation: notifSlide .2s ease; 
    transition: all 0.2s ease;
    cursor: pointer;
}
.notif-entry:hover > div { background: #f8f9fa; }
.notif-entry.read {
    opacity: 0.7;
    border-left: 3px solid #28a745 !important;
}
.notif-entry.read:hover > div { background: #f0f9f0; }
#notif-list::-webkit-scrollbar { width: 4px; }
#notif-list::-webkit-scrollbar-thumb { background: #dee2e6; border-radius: 2px; }
</style>
@endif
