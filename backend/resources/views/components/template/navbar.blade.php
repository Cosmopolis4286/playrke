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
                <img class="mr-2 user-img rounded-circle border border-2 border-primary"
                     src="{{ $navUser->foto ? asset('img/users/' . $navUser->foto) : asset('img/fav.ico') }}"
                     alt="{{ $navUser->username }}"
                     style="width:32px;height:32px;object-fit:cover;">
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
                            class="dropdown-item d-flex align-items-center px-3 py-2 hover:bg-gray-100 transition-all w-100 text-left border-0 bg-transparent"
                            data-bs-toggle="modal" data-bs-target="#passwordModal">
                        <i class="fas fa-lock me-3 text-muted" style="width:16px;"></i>
                        Contraseña
                    </button>
                </div>
                
                {{-- Separador --}}
                <div class="border-top mx-3" style="border-color: #e9ecef;"></div>
                <div class="border-top border-light"></div>
                
                {{-- Cerrar sesión --}}
                <div class="py-2">
                    <button type="button" 
                            class="dropdown-item d-flex align-items-center px-3 py-2 hover:bg-danger hover:text-white transition-all w-100 text-left border-0 bg-transparent text-danger"
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

{{-- Modal de Cambiar Contraseña (Global) --}}
<div id="passwordModal" class="modal fade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">
                    <i class="fas fa-lock me-2"></i>Cambiar Contraseña
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body">
                <form id="change-password-form">
                    <!-- Contraseña Actual -->
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Contraseña Actual</label>
                        <div class="input-group">
                            <input 
                                type="password"
                                id="current_password"
                                name="current_password"
                                class="form-control"
                                placeholder="Ingresa tu contraseña actual"
                                required
                            >
                            <button 
                                type="button" 
                                class="btn btn-outline-secondary toggle-password"
                                data-target="current_password"
                            >
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Nueva Contraseña -->
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Nueva Contraseña</label>
                        <div class="input-group">
                            <input 
                                type="password"
                                id="new_password"
                                name="new_password"
                                class="form-control"
                                placeholder="Ingresa tu nueva contraseña"
                                required
                            >
                            <button 
                                type="button" 
                                class="btn btn-outline-secondary toggle-password"
                                data-target="new_password"
                            >
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="form-text">Mínimo 8 caracteres, incluir mayúsculas y números</div>
                    </div>

                    <!-- Confirmar Nueva Contraseña -->
                    <div class="mb-3">
                        <label for="new_password_confirmation" class="form-label">Confirmar Nueva Contraseña</label>
                        <div class="input-group">
                            <input 
                                type="password"
                                id="new_password_confirmation"
                                name="new_password_confirmation"
                                class="form-control"
                                placeholder="Confirma tu nueva contraseña"
                                required
                            >
                            <button 
                                type="button" 
                                class="btn btn-outline-secondary toggle-password"
                                data-target="new_password_confirmation"
                            >
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Indicador de Fortaleza -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <small class="text-muted">Fortaleza de la contraseña</small>
                            <small id="password-strength-text" class="text-muted">Débil</small>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div id="password-strength-bar" class="progress-bar bg-danger" 
                                 style="width: 0%; transition: all 0.3s ease;"></div>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="submit-change-password" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Cambiar Contraseña
                </button>
            </div>
        </div>
    </div>
</div>

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

{{-- JavaScript para el Modal de Contraseña --}}
<script>
(function() {
    'use strict';
    
    // ── Funcionalidad del Modal de Contraseña ─────────────────────
    const passwordModal = document.getElementById('passwordModal');
    const changePasswordForm = document.getElementById('change-password-form');
    const submitButton = document.getElementById('submit-change-password');
    const strengthBar = document.getElementById('password-strength-bar');
    const strengthText = document.getElementById('password-strength-text');
    
    // ── Toggle de visibilidad de contraseña ─────────────────────
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.dataset.target;
            const input = document.getElementById(targetId);
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });
    
    // ── Validación de fortaleza de contraseña ────────────────────
    function checkPasswordStrength(password) {
        let strength = 0;
        let feedback = '';
        
        if (password.length >= 8) strength++;
        if (password.match(/[a-z]+/)) strength++;
        if (password.match(/[A-Z]+/)) strength++;
        if (password.match(/[0-9]+/)) strength++;
        if (password.match(/[$@#&!]+/)) strength++;
        
        const strengthLevels = [
            { width: '0%', class: 'bg-danger', text: 'Débil' },
            { width: '20%', class: 'bg-danger', text: 'Débil' },
            { width: '40%', class: 'bg-warning', text: 'Regular' },
            { width: '60%', class: 'bg-warning', text: 'Regular' },
            { width: '80%', class: 'bg-info', text: 'Fuerte' },
            { width: '100%', class: 'bg-success', text: 'Muy fuerte' }
        ];
        
        const level = strengthLevels[Math.min(strength, 5)];
        
        strengthBar.style.width = level.width;
        strengthBar.className = `progress-bar ${level.class}`;
        strengthText.textContent = level.text;
        strengthText.className = level.class.replace('bg-', 'text-');
        
        return strength >= 3;
    }
    
    // ── Event listener para el campo de nueva contraseña ─────────────
    const newPasswordInput = document.getElementById('new_password');
    if (newPasswordInput) {
        newPasswordInput.addEventListener('input', function() {
            checkPasswordStrength(this.value);
        });
    }
    
    // ── Envío del formulario ───────────────────────────────────
    submitButton.addEventListener('click', async function() {
        const formData = new FormData(changePasswordForm);
        
        // Validación básica
        const currentPassword = formData.get('current_password');
        const newPassword = formData.get('new_password');
        const confirmPassword = formData.get('new_password_confirmation');
        
        if (!currentPassword || !newPassword || !confirmPassword) {
            showToast('Por favor completa todos los campos', 'error');
            return;
        }
        
        if (newPassword !== confirmPassword) {
            showToast('Las contraseñas no coinciden', 'error');
            return;
        }
        
        if (newPassword.length < 8) {
            showToast('La contraseña debe tener al menos 8 caracteres', 'error');
            return;
        }
        
        if (!checkPasswordStrength(newPassword)) {
            showToast('La contraseña no es lo suficientemente fuerte', 'error');
            return;
        }
        
        // Deshabilitar botón y mostrar loading
        const originalText = submitButton.innerHTML;
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Cambiando...';
        
        try {
            // Obtener token CSRF
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            
            const response = await fetch('/profile/changePassword', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    current_password: currentPassword,
                    new_password: newPassword,
                    new_password_confirmation: confirmPassword
                })
            });
            
            const data = await response.json();
            
            if (response.ok && data.success) {
                showToast('Contraseña cambiada exitosamente', 'success');
                changePasswordForm.reset();
                
                // Resetear indicador de fortaleza
                strengthBar.style.width = '0%';
                strengthBar.className = 'progress-bar bg-danger';
                strengthText.textContent = 'Débil';
                strengthText.className = 'text-muted';
                
                // Cerrar modal
                const modal = bootstrap.Modal.getInstance(passwordModal);
                if (modal) {
                    modal.hide();
                }
            } else {
                showToast(data.message || 'Error al cambiar la contraseña', 'error');
            }
        } catch (error) {
            console.error('Error cambiando contraseña:', error);
            showToast('Error de conexión. Intenta nuevamente', 'error');
        } finally {
            // Restaurar botón
            submitButton.disabled = false;
            submitButton.innerHTML = originalText;
        }
    });
    
    // ── Resetear formulario al cerrar el modal ───────────────────
    passwordModal.addEventListener('hidden.bs.modal', function() {
        changePasswordForm.reset();
        strengthBar.style.width = '0%';
        strengthBar.className = 'progress-bar bg-danger';
        strengthText.textContent = 'Débil';
        strengthText.className = 'text-muted';
        
        // Resetear visibilidad de contraseñas
        document.querySelectorAll('.toggle-password i').forEach(icon => {
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        });
        document.querySelectorAll('input[type="text"]').forEach(input => {
            if (input.id.includes('password')) {
                input.type = 'password';
            }
        });
    });
    
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
