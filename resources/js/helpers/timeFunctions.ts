// ============================
// Utils para manejo de fechas y horas
// ============================

/**
 * Obtiene el desplazamiento GMT del equipo en formato ±HH:MM
 * @returns {string} - Ejemplo: "+02:00", "-05:30"
 */
function getGMTOffset(): string {
    const offset = new Date().getTimezoneOffset(); // en minutos, positivo para zonas detrás de GMT
    const sign = offset > 0 ? "-" : "+";
    const absOffset = Math.abs(offset);
    const hours = String(Math.floor(absOffset / 60)).padStart(2, "0");
    const minutes = String(absOffset % 60).padStart(2, "0");
    return `${sign}${hours}:${minutes}`;
}

/**
 * Convierte un timestamp Unix a una fecha y hora formateadas en local.
 * @param unixTimestamp - Timestamp Unix en segundos
 * @param showSeconds - Mostrar segundos en el formato de tiempo
 * @returns {string} - Fecha y hora formateadas
 */
function formatUnixTimestamp(unixTimestamp: number | string, showSeconds = false): string | undefined {
    if (!unixTimestamp) return;

    const timestamp = typeof unixTimestamp === "string" ? parseInt(unixTimestamp) : unixTimestamp;
    const date = new Date(timestamp * 1000);

    const formattedDate = date.toLocaleDateString("es-ES", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
    });

    const timeOptions: Intl.DateTimeFormatOptions = {
        hour: "2-digit",
        minute: "2-digit",
    };
    if (showSeconds) timeOptions.second = "2-digit";

    const formattedTime = date.toLocaleTimeString("es-ES", timeOptions);

    return `${formattedDate} - ${formattedTime}`;
}

/**
 * Formatea y muestra fecha y hora local a partir de un timestamp Unix en elementos del DOM.
 * @param unixTimestamp - Timestamp Unix en segundos
 */
function displayFormattedDateTime(unixTimestamp: number): void {
    const dateElement = document.getElementById("text-date");
    const timeElement = document.getElementById("text-time");
    if (!dateElement || !timeElement) return;

    const dateObj = new Date(unixTimestamp * 1000);

    const formattedDate = dateObj.toLocaleDateString("en-GB", {
        day: "2-digit",
        month: "2-digit",
        year: "2-digit",
    });

    const formattedTime = dateObj.toLocaleTimeString("en-US", {
        hour: "2-digit",
        minute: "2-digit",
        hour12: true,
    });

    dateElement.textContent = formattedDate;
    timeElement.textContent = formattedTime;
}

/**
 * Formatea y muestra fecha y hora a partir de fecha y hora en formato string,
 * ajustando a la zona local del equipo y mostrando zona horaria abreviada.
 * @param date - Fecha en formato 'YYYY-MM-DD'
 * @param time - Hora en formato 'HH:mm'
 */
function displayFormattedDateTimeFromStrings(date: string, time: string): void {
    const dateElement = document.getElementById("text-date");
    const timeElement = document.getElementById("text-time");
    if (!dateElement || !timeElement) return;

    const localDateTime = new Date(`${date}T${time}`);

    // Convertimos a ISO para asegurar consistencia
    const utcDateTime = new Date(localDateTime.toISOString());

    const formattedDate = utcDateTime.toLocaleDateString("en-GB", {
        day: "2-digit",
        month: "2-digit",
        year: "2-digit",
    });

    const formattedTime = utcDateTime.toLocaleTimeString("en-US", {
        hour: "2-digit",
        minute: "2-digit",
        hour12: true,
        timeZoneName: "short",
    });

    dateElement.textContent = formattedDate;
    timeElement.textContent = formattedTime;
}

// Exportamos funciones para que puedan usarse globalmente si es necesario
window.getGMTOffset = getGMTOffset;
window.formatUnixTimestamp = formatUnixTimestamp;
window.displayFormattedDateTime = displayFormattedDateTime;
window.displayFormattedDateTimeFromStrings = displayFormattedDateTimeFromStrings;
