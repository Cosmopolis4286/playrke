<?php

namespace App\Providers;

use App\Domains\Common\Status\StatusChanger;
use App\Models\Games\Common\Utils\GameStatus;
use App\Observers\Games\Common\GameStatusObserver;
use App\Repositories\BaseRepository;
use App\Services\Route\RouteService;
use App\Support\EnvValidator;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/**
 * AppServiceProvider
 *
 * Proveedor principal de la aplicación.
 *
 * Funciones principales:
 *  - Registrar singletons y bindings necesarios
 *  - Forzar HTTPS en producción
 *  - Compartir datos globales con todas las vistas
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap de la aplicación
     *
     * Este método se ejecuta después de que todos los servicios han sido
     * registrados. Aquí podemos:
     *  - Configurar URL seguras
     *  - Compartir variables globales con las vistas
     */
    public function boot() : void
    {
        // -------------------------------------------
        // Validar variables de entorno críticas al startup
        // -------------------------------------------
        // Falla rápido y claro si falta o es inválida alguna variable.
        // Solo se ejecuta fuera de tests para no interferir con phpunit.
        if (! $this->app->runningUnitTests()) {
            EnvValidator::validate();
        }

        GameStatus::observe(GameStatusObserver::class);

        // Rate-limit: compra de cartones de jugador — 5 intentos por minuto por (usuario + sala).
        RateLimiter::for('bingo-player-buy', function(Request $request) {
            $key = ($request->user()?->id ?? 'guest') . '|' . $request->route('room');

            return Limit::perMinute(5)->by($key);
        });

        // Rate-limit: verificación de bingo — 10 intentos por minuto por (usuario + sala).
        // Protege contra multi-tab o clics rápidos que salten el guard claimingBingo del cliente.
        RateLimiter::for('bingo-check-bingo', function(Request $request) {
            $key = ($request->user()?->id ?? 'guest') . '|' . $request->route('room');

            return Limit::perMinute(10)->by($key);
        });

        // Rate-limit: verificación de acceso a sala privada — 10 intentos por minuto
        // por (usuario + sala). Mitiga fuerza bruta sobre código/contraseña.
        RateLimiter::for('bingo-verify-access', function(Request $request) {
            $key = ($request->user()?->id ?? 'guest') . '|' . $request->route('room');

            return Limit::perMinute(10)->by($key);
        });

        // Rate-limit: vista pública de juego de un ExternalPlayer (sin
        // sesión — ver ExternalPlayerGameController) — clave por `link`, el
        // token de 28 caracteres que actúa como credencial de acceso. Más
        // generoso que bingo-check-bingo porque agrupa TODAS las acciones
        // de esta pantalla (game, drawState, cards, checkBingo), no solo el
        // reclamo de bingo.
        RateLimiter::for('external-player-game', function(Request $request) {
            return Limit::perMinute(30)->by($request->route('link') ?? $request->ip());
        });

        // Rate-limit: unirse a sala privada por código ("Tengo un código") — 6
        // intentos por minuto por usuario. Más estricto que bingo-verify-access
        // porque ahí la clave incluye el room_id (los intentos se diluyen entre
        // salas distintas); aquí la sala es desconocida hasta resolver el
        // código, así que no hay esa dilución natural.
        RateLimiter::for('bingo-join-by-code', function(Request $request) {
            return Limit::perMinute(6)->by($request->user()?->id ?? $request->ip());
        });

        // Rate-limit: cambio de contraseña — 5 intentos por minuto por usuario.
        // Mitiga fuerza bruta sobre "current_password" desde una sesión ya autenticada.
        RateLimiter::for('profile-change-password', function(Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?? $request->ip());
        });

        // Rate-limit: fijar/cambiar PIN de retiro — mismo criterio que
        // profile-change-password (mitiga fuerza bruta sobre current_password
        // en este endpoint). El lockout del PIN EN SÍ durante un retiro vive
        // en WalletFraudService::checkWithdrawalPinLockout(), no aquí — este
        // limita cuántas veces se puede intentar CAMBIAR el PIN, no probarlo.
        RateLimiter::for('profile-withdrawal-pin', function(Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?? $request->ip());
        });

        // Rate-limit: actualización de perfil (incluye cambio de email) — 10 por minuto por usuario.
        RateLimiter::for('profile-update', function(Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?? $request->ip());
        });

        // Rate-limit: lecturas de wallet (movimientos, directorios, límites,
        // operación pendiente) — antes sin ningún throttle de ruta, solo
        // detrás de auth+permission. 60/min por usuario es generoso para uso
        // normal (polling del frontend, paginación) pero cierra scraping/DoS
        // de bajo costo contra la BD desde una sesión autenticada válida.
        RateLimiter::for('wallet-read', function(Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?? $request->ip());
        });

        // Rate-limit: subida/eliminación de avatar — 10 por minuto por usuario.
        RateLimiter::for('profile-avatar', function(Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?? $request->ip());
        });

        // Rate-limit: suma manual de XP — 10 por minuto por usuario.
        // El endpoint no verifica el origen del XP más allá de un "reason" libre;
        // limitar la tasa mitiga scripting hasta que exista un trigger real de juego.
        RateLimiter::for('profile-add-xp', function(Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?? $request->ip());
        });

        // Rate-limit: re-evaluación manual de logros — 5 por minuto por usuario.
        // Es de respaldo (el flujo normal es automático vía GameCompletionService);
        // no debería necesitarse a alta frecuencia desde el cliente.
        RateLimiter::for('profile-achievements-check', function(Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?? $request->ip());
        });

        // Rate-limit: otorgar XP a un usuario desde el panel admin — 20 por minuto
        // por admin (más alto que el self-service porque un admin puede operar
        // legítimamente sobre varios usuarios seguidos).
        RateLimiter::for('admin-grant-xp', function(Request $request) {
            return Limit::perMinute(20)->by($request->user()?->id ?? $request->ip());
        });

        // Rate-limit: guardado en batch de la matriz de permisos (solo
        // superadmin) — acción de alto impacto (puede tocar decenas de
        // asignaciones rol↔permiso a la vez) sin límite previo.
        RateLimiter::for('admin-permissions-matrix', function(Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?? $request->ip());
        });

        // Rate-limit: acciones masivas destructivas sobre usuarios
        // (activar/desactivar, eliminar en lote) — antes sin límite pese a
        // ser irreversibles o de alto impacto sobre múltiples cuentas.
        RateLimiter::for('admin-users-bulk', function(Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?? $request->ip());
        });

        // Rate-limit: aprobación/rechazo en lote de movimientos de billetera
        // — 5 requests por minuto por admin. Más restrictivo que
        // admin-users-bulk (10/min) porque cada request puede mover dinero
        // real hasta 100 veces (ver WalletMovementController::processBulk()),
        // a diferencia de un toggle de estado de usuario.
        RateLimiter::for('wallet-movements-bulk', function(Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?? $request->ip());
        });

        // Rate-limit: registro de cuentas nuevas por IP — 20 por día. Ya
        // existe throttle:5,1 (por minuto) en routes/auth.php, pero un
        // ataque de sybil farming (crear muchas cuentas para acumular XP de
        // referido, ver AwardReferralXpOnVerifiedListener) se ejecuta
        // fácilmente por debajo de un límite por minuto con solo espaciar
        // los intentos. Un límite diario acota el volumen total sin afectar
        // el caso legítimo (una IP compartida — NAT, oficina, red móvil —
        // registrando un puñado de cuentas reales por día).
        //
        // NOTA: sin TrustProxies configurado en bootstrap/app.php,
        // $request->ip() puede no ser la IP real del cliente si el
        // despliegue queda detrás de un proxy/CDN — mismo caveat que ya
        // aplica a los demás limiters ->by($request->ip()) de este archivo.
        RateLimiter::for('register-by-ip', function(Request $request) {
            return Limit::perDay(20)->by($request->ip());
        });

        // -------------------------------------------
        // Forzar HTTPS en producción
        // -------------------------------------------
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // -------------------------------------------
        // Compartir prefijo del rol principal en todas las vistas
        // -------------------------------------------
        // DISABLED: Causing conflicts with Auth and View loading order
        // This can be enabled later if needed, but must be loaded after
        // all services are fully booted
        if ($this->app->has('view')) {
            // Datos del navbar (billetera activa del usuario)
            View::composer('components.template.navbar', \App\Http\View\NavbarComposer::class);

            View::composer('*', function($view) : void {
                $prefix = null;
                if (Auth::check()) {
                    $user = Auth::user();
                    $firstRole = $user->roles->first();
                    if ($firstRole !== null) {
                        $prefix = $firstRole->prefix;
                    }
                }
                $view->with('userRolePrefix', $prefix);
            });
        }
    }

    /**
     * Registrar servicios en el contenedor de Laravel
     *
     * Aquí registramos singletons y bindings que deben estar disponibles
     * durante toda la ejecución de la aplicación.
     */
    public function register() : void
    {
        // -------------------------------------------
        // Singletons de servicios de la aplicación
        // -------------------------------------------

        // Resolver para inyectar StatusChanger en cualquier BaseRepository
        $this->app->resolving(BaseRepository::class, function($repo, $app) {
            if (method_exists($repo, 'setStatusChanger')) {
                $repo->setStatusChanger($app->make(StatusChanger::class));
            }
        });

        // Servicio para manejo de rutas o helpers globales de rutas
        $this->app->singleton(RouteService::class, fn ($app) => new RouteService);

        // -------------------------------------------
        // Binding obligatorio para 'files'
        // -------------------------------------------
        // Esto evita el error "Target class [files] does not exist"
        // que Laravel puede lanzar al usar Filesystem en algunos servicios.
        $this->app->singleton('files', function() {
            return new Filesystem;
        });

        // -------------------------------------------
        // Binding de middleware 'tos' en el contenedor
        // -------------------------------------------
        // Necesario para que Laravel pueda resolver el alias antes de que
        // el router haya registrado los aliases de middleware.
        $this->app->bind('tos', \App\Http\Middleware\RequireTosAcceptance::class);

        // -------------------------------------------
        // Detalle admin de eventos de ganancia
        // -------------------------------------------
        // Cada juego etiqueta su resolver con WinEventDetailResolver::TAG en
        // su propio ServiceProvider (Bingo, Trivia, …); aquí solo se inyecta
        // la colección, así un juego nuevo no toca este archivo.
        $this->app->when(\App\Domains\Platform\WinEvent\Services\WinEventDetailService::class)
            ->needs('$resolvers')
            ->giveTagged(\App\Domains\Platform\WinEvent\Contracts\WinEventDetailResolver::TAG);
    }
}
