@props([
    'title' => 'Tablero',
    'contentHeader' => null,
    'loadDataTables' => true,
    'loadSelect2' => false,
    'inertia' => false,
])

<!DOCTYPE html>
<html lang="en" class="is-loading">
<x-template.head :title="$title" :inertia="$inertia" />

<body class="hold-transition sidebar-mini layout-fixed">
    {{-- Inicializar --sidebar-w antes de pintar cualquier elemento --}}
    <script>
    (function(){
        var b = document.body;
        var SK = 'lte.pushmenu.remember';
        var isMini = b.classList.contains('sidebar-mini');
        var isCollapse = b.classList.contains('sidebar-collapse');
        // Restaurar estado guardado por AdminLTE
        try {
            if (localStorage.getItem(SK) === 'sidebar-collapse') {
                b.classList.add('sidebar-collapse');
                isCollapse = true;
            }
        } catch(e){}
        var w = (isMini && isCollapse) ? '4.6rem' : (!isMini && isCollapse) ? '0px' : '250px';
        document.documentElement.style.setProperty('--sidebar-w', w);
    })();
    </script>

    {{-- Loader visible mientras la app se carga --}}
    <x-shared.app-loader />

    {{-- Contenedor principal, oculto hasta que la app esté lista --}}
    <div id="app-wrapper" class="wrapper d-flex flex-column vh-100">

        {{-- Barra de navegación --}}
        <x-template.navbar />

        {{-- Ticker de ganancias en vivo (montado por Vue) --}}
        <div id="platform-win-ticker"></div>

        {{-- Sincroniza en tiempo real el badge de pendientes del navbar y del
             sidebar (montado por Vue; ver PendingMovementsBadge.vue) --}}
        <div id="pending-movements-badge" data-initial-pending-count="{{ $navPendingCount ?? 0 }}"></div>

        {{-- Banner de sesión de soporte activa (montado por Vue solo si hay
             impersonación en curso; ver ImpersonationService/ImpersonationBanner.vue) --}}
        @if(session()->has('impersonator_id'))
            <div
                id="impersonation-banner"
                data-impersonating="true"
                data-admin-name="{{ \App\Models\User::find(session('impersonator_id'))?->username }}"
                data-player-name="{{ auth()->user()?->username }}"
            ></div>
        @endif

        {{-- Sidebar dinámico --}}
        <x-template.sidebar-dynamic />

        {{-- Alertas SweetAlert --}}
        @include('sweetalert::alert')

        {{-- Notificaciones globales --}}
        @include('includes.notifications')

        {{-- Contenido principal --}}
        <main class="content-wrapper flex-grow-1 d-flex flex-column">

            {{-- Contenido de la página: contenedor de scroll propio, separado de
                 .content-wrapper para que el min-height inline que AdminLTE le
                 fuerza a .content-wrapper (ver styles.css) no se mezcle con el
                 cálculo de 100vh de páginas como GameRooms/Lobby. --}}
            <section id="content-scrollable" class="content flex-grow-1 overflow-auto">

                {{-- Header del contenido --}}
                @isset($contentHeader)
                    <section class="content-header">
                        {{ $contentHeader }}
                    </section>
                @endisset

                <div id="slot-wrapper">
                    {{ $slot }}
                </div>
            </section>

        </main>

        {{-- Modal para cambiar de contraseña --}}
        <x-shared.modal id="change-password" title="Cambiar Contraseña" btnConfirm="btn-change-password"
            :form-view="'components.form.change-password-form'" />

        {{-- Pie de página --}}
        <x-template.footer />
    </div>

    {{-- Overlay de logout --}}
    <div id="logout-overlay" aria-hidden="true" style="display:none;position:fixed;inset:0;z-index:99999;align-items:center;justify-content:center;flex-direction:column;gap:1.25rem;background:var(--ds-overlay);backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);">
        <svg width="52" height="52" viewBox="0 0 50 50" style="animation:logout-spin 1s linear infinite;">
            <circle cx="25" cy="25" r="20" fill="none" stroke="var(--ds-border-2)" stroke-width="3"/>
            <circle cx="25" cy="25" r="20" fill="none" stroke="#6366f1" stroke-width="3" stroke-linecap="round" stroke-dasharray="80 126"/>
        </svg>
        <p style="margin:0;font-size:.875rem;font-weight:500;color:var(--ds-text-2);letter-spacing:.04em;">Cerrando sesión…</p>
    </div>
    <style>@keyframes logout-spin{to{transform:rotate(360deg)}}</style>

    {{-- Scripts adicionales y dependencias --}}
    <x-template.scripts :loadDataTables="$loadDataTables" :loadSelect2="$loadSelect2" />

    {{-- Pila para scripts específicos de cada vista --}}
    @stack('stack-scripts')

    @vite(['resources/js/app.ts'])
</body>

</html>
