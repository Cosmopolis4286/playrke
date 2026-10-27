<?php

namespace Database\Seeders;

use App\Models\AppParameter;
use Illuminate\Database\Seeder;

class AppParameterSeeder extends Seeder
{
    public function run() : void
    {
        $parameters = [
            // ─────────────────────────────────────────────
            // AUTENTICACIÓN
            // ─────────────────────────────────────────────
            // Habilita/deshabilita el formulario de registro público.
            // Consumido por: AuthenticatedSessionController, RegisteredUserController, PublicController
            ['param' => 'register',          'value' => 'ACTIVE',            'status' => 'ACTIVE'],

            // Habilita/deshabilita el acceso al login.
            ['param' => 'login',             'value' => 'ACTIVE',            'status' => 'ACTIVE'],

            // ─────────────────────────────────────────────
            // APLICACIÓN
            // ─────────────────────────────────────────────
            // Nombre visible de la plataforma (usado en emails, UI).
            ['param' => 'app_name',          'value' => 'PlayRKE',           'status' => 'ACTIVE'],

            // Versión actual de la plataforma.
            // Consumido por: VersionManagerService vía config('app.version')
            ['param' => 'app_version',       'value' => '1.0.0',             'status' => 'ACTIVE'],

            // Zona horaria por defecto de la plataforma.
            // Consumido por: BingoController, GameTrait, RoomClosureService vía config('app.timezone')
            ['param' => 'timezone',          'value' => 'America/Caracas',   'status' => 'ACTIVE'],

            // Modo de mantenimiento global. ACTIVE = en mantenimiento.
            ['param' => 'maintenance_mode',  'value' => 'INACTIVE',          'status' => 'ACTIVE'],

            // ─────────────────────────────────────────────
            // REGISTRO DE USUARIOS
            // ─────────────────────────────────────────────
            // Requiere verificación de email tras el registro.
            ['param' => 'email_verification', 'value' => 'INACTIVE',         'status' => 'ACTIVE'],

            // Número máximo de intentos de login antes de bloqueo temporal.
            ['param' => 'max_login_attempts', 'value' => '5',                'status' => 'ACTIVE'],

            // ─────────────────────────────────────────────
            // COMISIONES GLOBALES
            // ─────────────────────────────────────────────
            // Comisión base de la plataforma (%) sobre ventas de cartones.
            // Usado como valor por defecto en salas sin comisión personalizada.
            ['param' => 'rke_commission_pct', 'value' => '5.00',             'status' => 'ACTIVE'],

            // Comisión máxima permitida que puede configurar un commerce (%).
            ['param' => 'max_room_commission_pct', 'value' => '30.00',       'status' => 'ACTIVE'],

            // ─────────────────────────────────────────────
            // BINGO – LÍMITES DE CARTONES
            // ─────────────────────────────────────────────
            // Mínimo de cartones por compra.
            ['param' => 'bingo_min_cards_per_purchase', 'value' => '1',      'status' => 'ACTIVE'],

            // Máximo de cartones por compra.
            ['param' => 'bingo_max_cards_per_purchase', 'value' => '10',     'status' => 'ACTIVE'],

            // Máximo de cartones totales por jugador por sala.
            ['param' => 'bingo_max_cards_per_player',   'value' => '20',     'status' => 'ACTIVE'],

            // Minutos antes de la hora de inicio en que se cierran las ventas
            // del VENDEDOR (punto de venta). Su pago nace PENDING y los cartones
            // no juegan hasta que el comercio aprueba el comprobante: el margen
            // es para que alcance a revisarlo antes del sorteo. No aplica a la
            // compra del jugador con billetera (pago inmediato).
            // Consumido por: BingoCardGenerateService vía config('app.bingo_seller_sales_cutoff_minutes').
            ['param' => 'bingo_seller_sales_cutoff_minutes', 'value' => '5', 'status' => 'ACTIVE'],

            // ─────────────────────────────────────────────
            // JUGADORES EXTERNOS
            // ─────────────────────────────────────────────
            // Días de vigencia del link de un jugador externo antes de expirar
            // (ver App\Services\Roles\Player\External\ExternalPlayerLinkService::resolveOrCreatePlayer()).
            ['param' => 'external_player_link_expiration_days', 'value' => '7', 'status' => 'ACTIVE'],

            // ─────────────────────────────────────────────
            // BINGO – JUEGO
            // ─────────────────────────────────────────────
            // Tiempo (segundos) entre cada bolilla sorteada.
            ['param' => 'bingo_chip_interval_sec',  'value' => '5',          'status' => 'ACTIVE'],

            // Tiempo (segundos) de espera tras declarar un ganador antes de cerrar la ronda.
            ['param' => 'bingo_winner_wait_sec',    'value' => '30',         'status' => 'ACTIVE'],

            // ─────────────────────────────────────────────
            // BILLETERA
            // ─────────────────────────────────────────────
            // Monto mínimo permitido en una recarga de billetera.
            ['param' => 'wallet_min_recharge',  'value' => '1.00',           'status' => 'ACTIVE'],

            // Monto máximo permitido en una recarga de billetera.
            ['param' => 'wallet_max_recharge',  'value' => '10000.00',       'status' => 'ACTIVE'],

            // ─────────────────────────────────────────────
            // SOPORTE / CONTACTO
            // ─────────────────────────────────────────────
            ['param' => 'support_email',     'value' => 'soporte@playrke.com', 'status' => 'ACTIVE'],
            ['param' => 'support_whatsapp',  'value' => '',                    'status' => 'INACTIVE'],

            // ─────────────────────────────────────────────
            // AUDITORÍA
            // ─────────────────────────────────────────────
            // Días de retención de registros en la tabla `audits` antes de
            // ser purgados por el comando audit:prune (ver
            // App\Services\Audit\AuditRetentionSettings).
            ['param' => 'audit_retention_days', 'value' => '90', 'status' => 'ACTIVE'],

            // Días de retención de registros en la tabla `system_logs` antes
            // de ser purgados por el comando logs:prune (ver
            // App\Services\Audit\SystemLogRetentionSettings).
            ['param' => 'system_log_retention_days', 'value' => '30', 'status' => 'ACTIVE'],

            // Umbral de errores (system_logs, level=error) dentro de la
            // ventana configurada que dispara una alerta — ver comando
            // logs:alert-threshold y App\Services\Audit\LogAlertSettings.
            ['param' => 'log_alert_error_threshold', 'value' => '20', 'status' => 'ACTIVE'],
            ['param' => 'log_alert_window_minutes',  'value' => '15', 'status' => 'ACTIVE'],

            // Alertas de tasas de cambio — ver ExchangeRateAlertService:
            // cambio mínimo (%) que se avisa a la red, horas sin actualizar una
            // tasa propia y desvío máximo (%) de la tasa propia vs. la oficial.
            ['param' => 'exchange_alert_change_pct',    'value' => '3',  'status' => 'ACTIVE'],
            ['param' => 'exchange_alert_stale_hours',   'value' => '24', 'status' => 'ACTIVE'],
            ['param' => 'exchange_alert_deviation_pct', 'value' => '10', 'status' => 'ACTIVE'],
            // Tope de desvío (%) de las tasas propias vs. la oficial: no se pueden
            // guardar/activar fuera de ese rango (ExchangeRateDeviationCap). 0 = sin tope.
            ['param' => 'exchange_rate_max_deviation_pct', 'value' => '0', 'status' => 'ACTIVE'],
        ];

        foreach ($parameters as $data) {
            AppParameter::updateOrCreate(
                ['param' => $data['param']],
                ['value' => $data['value'], 'status' => $data['status']],
            );
        }

        $this->command->info('✔ AppParameterSeeder: ' . count($parameters) . ' parámetros procesados.');
    }
}
