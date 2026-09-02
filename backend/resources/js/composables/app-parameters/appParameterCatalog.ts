export type AppParameterType = 'boolean' | 'integer' | 'decimal' | 'string'

export interface AppParameterMeta {
  type: AppParameterType
  label: string
  category: string
}

/**
 * Metadata de presentación para los parámetros conocidos (ver AppParameterSeeder,
 * backend/database/seeders/AppParameterSeeder.php). Un parámetro fuera de este
 * catálogo (creado ad-hoc desde la UI) cae al tipo 'string' por defecto — el
 * catálogo no es exhaustivo por diseño, es ayuda de presentación (qué input
 * renderizar, cómo agrupar), no una restricción dura de qué parámetros pueden
 * existir en la tabla.
 */
export const APP_PARAMETER_CATALOG: Record<string, AppParameterMeta> = {
  register:            { type: 'boolean', label: 'Registro público',        category: 'Autenticación' },
  login:               { type: 'boolean', label: 'Acceso al login',         category: 'Autenticación' },

  app_name:            { type: 'string',  label: 'Nombre de la plataforma', category: 'Aplicación' },
  app_version:         { type: 'string',  label: 'Versión',                 category: 'Aplicación' },
  timezone:            { type: 'string',  label: 'Zona horaria',            category: 'Aplicación' },
  maintenance_mode:    { type: 'boolean', label: 'Modo mantenimiento',      category: 'Aplicación' },

  email_verification:  { type: 'boolean', label: 'Verificación de email',   category: 'Registro de usuarios' },
  max_login_attempts:  { type: 'integer', label: 'Máx. intentos de login',  category: 'Registro de usuarios' },

  rke_commission_pct:      { type: 'decimal', label: 'Comisión base (%)',         category: 'Comisiones globales' },
  max_room_commission_pct: { type: 'decimal', label: 'Comisión máx. por sala (%)', category: 'Comisiones globales' },

  bingo_min_cards_per_purchase: { type: 'integer', label: 'Mín. cartones por compra',  category: 'Bingo — límites' },
  bingo_max_cards_per_purchase: { type: 'integer', label: 'Máx. cartones por compra',  category: 'Bingo — límites' },
  bingo_max_cards_per_player:   { type: 'integer', label: 'Máx. cartones por jugador', category: 'Bingo — límites' },

  external_player_link_expiration_days: { type: 'integer', label: 'Expiración link jugador externo (días)', category: 'Jugadores externos' },

  bingo_chip_interval_sec: { type: 'integer', label: 'Intervalo entre bolillas (seg)', category: 'Bingo — juego' },
  bingo_winner_wait_sec:   { type: 'integer', label: 'Espera tras ganador (seg)',      category: 'Bingo — juego' },

  wallet_min_recharge: { type: 'decimal', label: 'Recarga mínima', category: 'Billetera' },
  wallet_max_recharge: { type: 'decimal', label: 'Recarga máxima', category: 'Billetera' },

  support_email:    { type: 'string', label: 'Email de soporte',    category: 'Soporte / Contacto' },
  support_whatsapp: { type: 'string', label: 'WhatsApp de soporte', category: 'Soporte / Contacto' },

  audit_retention_days:      { type: 'integer', label: 'Retención de auditoría (días)', category: 'Auditoría' },
  system_log_retention_days: { type: 'integer', label: 'Retención de logs (días)',      category: 'Auditoría' },
  log_alert_error_threshold: { type: 'integer', label: 'Umbral de errores para alerta', category: 'Auditoría' },
  log_alert_window_minutes:  { type: 'integer', label: 'Ventana de alerta (min)',       category: 'Auditoría' },
}

const DEFAULT_META: AppParameterMeta = { type: 'string', label: '', category: 'Otros' }

export function getParamMeta(param: string): AppParameterMeta {
  return APP_PARAMETER_CATALOG[param] ?? { ...DEFAULT_META, label: param }
}
