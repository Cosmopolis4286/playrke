{{-- $navWallet y $navUser son inyectados por NavbarComposer vía AppServiceProvider --}}
<nav class="navbar navbar-expand-lg sticky-top" 
     style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);box-shadow: 0 2px 20px rgba(0,0,0,0.1);backdrop-filter: blur(10px);">
    
    <div class="container-fluid px-4">
        
        {{-- ── Brand y Toggle ───────────────────────────────────── --}}
        <a class="navbar-brand d-flex align-items-center text-white fw-bold" href="{{ route('dashboard') }}">
            <img src="{{ asset('img/fav.ico') }}" alt="PlayRKE" 
                 class="me-2" style="width:32px;height:32px;">
            <span class="d-none d-md-inline">PlayRKE</span>
        </a>
        
        <button class="navbar-toggler border-0 bg-transparent text-white" 
                type="button" data-bs-toggle="collapse" 
                data-bs-target="#navbarNav" aria-controls="navbarNav" 
                aria-expanded="false" aria-label="Toggle navigation">
            <i class="fas fa-bars fs-5"></i>
        </button>
        
        {{-- ── Contenido Principal ───────────────────────────── --}}
        <div class="collapse navbar-collapse" id="navbarNav">
            
            {{-- ── Izquierda: Navegación Principal ───────────────── --}}
            <ul class="navbar-nav me-auto">
                
                {{-- Dashboard --}}
                <li class="nav-item">
                    <a class="nav-link text-white d-flex align-items-center px-3 py-2 rounded-pill mx-1" 
                       href="{{ route('dashboard') }}" 
                       style="transition: all 0.3s ease;">
                        <i class="fas fa-home me-2"></i>
                        <span class="d-none d-md-inline">Inicio</span>
                    </a>
                </li>
                
                {{-- Juegos --}}
                <li class="nav-item dropdown">
                    <a class="nav-link text-white d-flex align-items-center px-3 py-2 rounded-pill mx-1 dropdown-toggle" 
                       href="#" role="button" data-bs-toggle="dropdown" 
                       aria-expanded="false" style="transition: all 0.3s ease;">
                        <i class="fas fa-gamepad me-2"></i>
                        <span class="d-none d-md-inline">Juegos</span>
                        <i class="fas fa-chevron-down ms-2"></i>
                    </a>
                    <ul class="dropdown-menu border-0 shadow-lg" 
                        style="border-radius:12px;min-width:200px;">
                        <li><a class="dropdown-item" href="{{ route('bingo.index') }}">
                            <i class="fas fa-dice me-2 text-primary"></i>Bingo
                        </a></li>
                        <li><a class="dropdown-item" href="#">
                            <i class="fas fa-trophy me-2 text-warning"></i>Torneos
                        </a></li>
                        <li><a class="dropdown-item" href="#">
                            <i class="fas fa-slot-machine me-2 text-success"></i>Tragamonedas
                        </a></li>
                    </ul>
                </li>
                
                {{-- Tienda --}}
                <li class="nav-item">
                    <a class="nav-link text-white d-flex align-items-center px-3 py-2 rounded-pill mx-1" 
                       href="#" style="transition: all 0.3s ease;">
                        <i class="fas fa-shopping-cart me-2"></i>
                        <span class="d-none d-md-inline">Tienda</span>
                    </a>
                </li>
                
                {{-- Soporte --}}
                <li class="nav-item">
                    <a class="nav-link text-white d-flex align-items-center px-3 py-2 rounded-pill mx-1" 
                       href="#" style="transition: all 0.3s ease;">
                        <i class="fas fa-headset me-2"></i>
                        <span class="d-none d-md-inline">Soporte</span>
                    </a>
                </li>
            </ul>
            
            {{-- ── Derecha: Wallet + Notificaciones + Usuario ───────────────── --}}
            <ul class="navbar-nav align-items-center">
                
                {{-- � Billetera --}}
                <li class="nav-item">
                    <a class="nav-link text-white d-flex align-items-center px-3 py-2 rounded-pill mx-1" 
                       href="{{ route('wallet.index') }}" 
                       style="background: rgba(255,255,255,0.1);backdrop-filter: blur(5px);transition: all 0.3s ease;">
                        <i class="fas fa-wallet me-2"></i>
                        @if($navWallet)
                            <div class="d-flex flex-column align-items-start">
                                <span class="small opacity-75">Saldo</span>
                                <span class="fw-bold">
                                    {{ $navWallet->currency->symbol }}{{ number_format($navWallet->balance, $navWallet->currency->decimal_places ?? 2, ',', '.') }}
                                </span>
                            </div>
                        @else
                            <span class="small">Sin billetera</span>
                        @endif
                    </a>
                </li>
                
                {{-- �🔔 Notificaciones --}}
                <li class="nav-item dropdown">
                    <a class="nav-link text-white position-relative px-3 py-2 rounded-pill mx-1 dropdown-toggle" 
                       href="#" role="button" data-bs-toggle="dropdown" 
                       aria-expanded="false" id="notifToggle"
                       style="background: rgba(255,255,255,0.1);backdrop-filter: blur(5px);transition: all 0.3s ease;">
                        <i class="fas fa-bell me-2"></i>
                        <span id="notif-badge"
                              class="badge bg-danger text-white rounded-pill position-absolute d-none"
                              style="top:-2px;right:-2px;font-size:10px;min-width:18px;padding:1px 4px;">
                            0
                        </span>
                    </a>
                    
                    <div class="dropdown-menu dropdown-menu-end p-0 shadow-lg border-0" 
                         aria-labelledby="notifToggle"
                         style="width:350px;max-height:450px;overflow:hidden;border-radius:12px;">
                        
                        {{-- Header de notificaciones --}}
                        <div class="d-flex align-items-center justify-content-between px-3 py-3 border-bottom bg-light">
                            <span class="fw-semibold text-dark">
                                <i class="fas fa-bell text-primary me-2"></i>Notificaciones
                            </span>
                            <button type="button" id="notif-clear"
                                    class="btn btn-sm btn-outline-secondary rounded-pill">
                                <i class="fas fa-check-double me-1"></i>Limpiar
                            </button>
                        </div>
                        
                        {{-- Lista de notificaciones --}}
                        <ul id="notif-list" class="list-unstyled mb-0"
                            style="max-height:350px;overflow-y:auto;">
                            <li id="notif-empty" class="text-center text-muted py-4">
                                <i class="fas fa-bell-slash d-block mb-2" style="font-size:32px;opacity:0.5;"></i>
                                <div class="small">No tienes notificaciones nuevas</div>
                            </li>
                        </ul>
                    </div>
                </li>
                
                {{-- 👤 Usuario --}}
                <li class="nav-item dropdown">
                    <button class="nav-link text-white d-flex align-items-center px-3 py-2 rounded-pill mx-1 dropdown-toggle" 
                            type="button" id="userDropdown"
                            data-bs-toggle="dropdown" aria-haspopup="true" 
                            aria-expanded="false"
                            style="background: rgba(255,255,255,0.1);backdrop-filter: blur(5px);transition: all 0.3s ease;">
                        <img class="rounded-circle border-2 border-white me-2"
                             src="{{ $navUser->foto ? asset('img/users/' . $navUser->foto) : asset('img/fav.ico') }}"
                             alt="{{ $navUser->username }}"
                             style="width:28px;height:28px;object-fit:cover;">
                        <div class="d-none d-md-block text-start">
                            <div class="fw-medium">{{ $navUser->username }}</div>
                            <div class="small opacity-75">Mi cuenta</div>
                        </div>
                        <i class="fas fa-chevron-down ms-2"></i>
                    </button>
                    
                    <div class="dropdown-menu dropdown-menu-end p-0 shadow-lg border-0" 
                         aria-labelledby="userDropdown" 
                         style="font-size:13px;min-width:240px;border-radius:12px;overflow:hidden;">
                        
                        {{-- Header compacto --}}
                        <div class="px-3 py-3 bg-light border-bottom">
                            <div class="d-flex align-items-center">
                                <img class="rounded-circle border-2 border-white me-2"
                                     src="{{ $navUser->foto ? asset('img/users/' . $navUser->foto) : asset('img/fav.ico') }}"
                                     alt="{{ $navUser->username }}"
                                     style="width:36px;height:36px;object-fit:cover;">
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
                            
                            <button id="change-password" 
                                    class="dropdown-item d-flex align-items-center px-3 py-2 hover:bg-gray-100 transition-all w-100 text-left border-0 bg-transparent">
                                <i class="fas fa-lock me-3 text-muted" style="width:16px;"></i>
                                Contraseña
                            </button>
                        </div>
                        
                        {{-- Separador --}}
                        <div class="border-top mx-3" style="border-color: #e9ecef;"></div>
                        
                        {{-- Cerrar sesión minimalista --}}
                        <div class="py-1">
                            <button type="button" 
                                    class="logout-btn dropdown-item d-flex align-items-center px-3 py-2 hover:bg-danger hover:text-white transition-all w-100 text-left border-0 bg-transparent text-danger">
                                <i class="fas fa-sign-out-alt me-3" style="width:16px;"></i>
                                Cerrar Sesión
                            </button>
                        </div>
                    </div>
                </li>
                
            </ul>
        </div>
    </div>
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
