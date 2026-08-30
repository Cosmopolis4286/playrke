/**
 * Formatea timestamps ISO 8601 (con offset) en la zona horaria local del
 * navegador del usuario, y arma una subinfo comparándola contra la zona
 * horaria en la que corre el servidor — útil cuando ambas difieren y el
 * usuario necesita saber "a qué hora fue esto, para mí".
 */

const browserTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone

function formatInTimezone(iso: string, timeZone: string): string {
    return new Intl.DateTimeFormat('es-ES', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit', second: '2-digit',
        hour12: false,
        timeZone,
    }).format(new Date(iso))
}

function formatDatePart(iso: string, timeZone: string): string {
    return new Intl.DateTimeFormat('es-ES', {
        day: '2-digit', month: '2-digit', year: 'numeric', timeZone,
    }).format(new Date(iso))
}

function formatTimePart(iso: string, timeZone: string): string {
    return new Intl.DateTimeFormat('es-ES', {
        hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false, timeZone,
    }).format(new Date(iso))
}

function timezoneAbbrev(iso: string, timeZone: string): string {
    const part = new Intl.DateTimeFormat('en-US', { timeZone, timeZoneName: 'short' })
        .formatToParts(new Date(iso))
        .find(p => p.type === 'timeZoneName')
    return part?.value ?? timeZone
}

export interface LocalDateTimeInfo {
    /** Fecha/hora formateada en la zona horaria local del navegador. */
    local: string
    /** Solo la fecha (dd/mm/aaaa), zona horaria local. */
    localDate: string
    /** Solo la hora (HH:mm:ss), zona horaria local. */
    localTime: string
    /** Abreviatura de la zona horaria local (ej. "GMT-4", "UTC"). */
    localAbbrev: string
    /** IANA timezone del navegador (ej. "America/Caracas"). */
    localTimezone: string
    /** true si la zona del servidor difiere de la del navegador. */
    differsFromServer: boolean
    /** Fecha/hora formateada en la zona horaria del servidor, o null si no se proveyó. */
    server: string | null
    /** IANA timezone del servidor, o null si no se proveyó. */
    serverTimezone: string | null
}

export function useLocalDateTime() {
    function describe(iso: string | null | undefined, serverTimezone: string | null): LocalDateTimeInfo | null {
        if (!iso) return null

        const local = formatInTimezone(iso, browserTimezone)
        const localAbbrev = timezoneAbbrev(iso, browserTimezone)

        return {
            local,
            localDate: formatDatePart(iso, browserTimezone),
            localTime: formatTimePart(iso, browserTimezone),
            localAbbrev,
            localTimezone: browserTimezone,
            differsFromServer: !!serverTimezone && serverTimezone !== browserTimezone,
            server: serverTimezone ? formatInTimezone(iso, serverTimezone) : null,
            serverTimezone,
        }
    }

    return { describe, browserTimezone }
}
