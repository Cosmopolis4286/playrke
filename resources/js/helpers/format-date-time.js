function formatDateTime(date, time) {
    // Crear un objeto Date con la fecha y hora proporcionadas
    const fullDateTime = new Date(`${date}T${time}`);

    // Convertir la hora a UTC
    const utcTime = fullDateTime.toISOString();

    // Crear un nuevo objeto Date usando el UTC generado
    const utcDateTime = new Date(utcTime);

    // Formatear la fecha en formato "dd/mm/yy"
    const formattedDate = utcDateTime.toLocaleDateString('en-GB', {
        day: '2-digit',
        month: '2-digit',
        year: '2-digit',
    });

    // Convertir la hora UTC a la zona horaria local del equipo
    const formattedTime = utcDateTime.toLocaleTimeString("en-US", {
        hour: "2-digit",
        minute: "2-digit",
        hour12: true,
        timeZoneName: "short" // Esto añade la zona horaria (e.g. GMT, PST, etc.)
    });

    // Mostrar la fecha y la hora formateadas en los elementos correspondientes
    $('#text-date').text(formattedDate);
    $('#text-time').text(formattedTime);
}

window.formatDateTime = formatDateTime;