{{-- $navWallet es inyectado por NavbarComposer vía AppServiceProvider --}}
@php $user = auth()->user() @endphp

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
                <img class="mr-1 user-img rounded-circle"
                     src="{{ $user->foto ? asset('img/users/' . $user->foto) : asset('img/fav.ico') }}"
                     alt="{{ $user->username }}">
                <span class="user-text-navbar">{{ $user->username }}</span>
            </button>
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown" style="font-size:13px">
                <button id="change-password" class="dropdown-item" type="button">
                    <i class="mr-1 fa-solid fa-lock"></i> Contraseña
                </button>
                <button type="button" class="dropdown-item logout-btn"
                        data-logout-url="{{ route('logout') }}"
                        data-login-redirect-url="{{ route('login') }}"
                        data-show-confirmation="false">
                    <i class="mr-1 fas fa-sign-out-alt"></i> Cerrar sesión
                </button>
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
        $list.querySelectorAll('.notif-entry').forEach(function (el) { el.remove(); });
        if ($empty) $empty.style.display = '';
        notifCount = 0;
        $badge.classList.add('d-none');
    });

    // Resetear badge al abrir panel
    document.getElementById('notifToggle').addEventListener('click', function () {
        setTimeout(function () {
            notifCount = 0;
            $badge.classList.add('d-none');
        }, 400);
    });

    // ── Inicializar Echo y suscribirse al canal ───────────────
    // echo.ts asigna window.Echo al cargar el bundle Vite (async).
    // Reintentamos hasta que esté disponible.
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
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

})();
</script>

<style>
@keyframes notifSlide {
    from { opacity: 0; transform: translateY(-6px); }
    to   { opacity: 1; transform: translateY(0); }
}
.notif-entry { animation: notifSlide .2s ease; }
.notif-entry:hover > div { background: #f8f9fa; }
#notif-list::-webkit-scrollbar { width: 4px; }
#notif-list::-webkit-scrollbar-thumb { background: #dee2e6; border-radius: 2px; }
</style>
@endif
