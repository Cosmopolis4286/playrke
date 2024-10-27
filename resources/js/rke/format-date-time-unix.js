function formatDateTime(unixTimestamp) {
    // Crear un objeto Date utilizando el timestamp en milisegundos
    const fullDateTime = new Date(unixTimestamp * 1000); // Convertir el timestamp de segundos a milisegundos

    // Formatear la fecha en formato "dd/mm/yy"
    const formattedDate = fullDateTime.toLocaleDateString('en-GB', {
        day: '2-digit',
        month: '2-digit',
        year: '2-digit',
    });

    // Convertir la hora a la zona horaria local del equipo
    let formattedTime = fullDateTime.toLocaleTimeString("en-US", {
        hour: "2-digit",
        minute: "2-digit",
        hour12: true,
    });

    // Mostrar la fecha y la hora formateadas en los elementos correspondientes
    $('#text-date').text(formattedDate);
    $('#text-time').text(formattedTime);
}

window.formatDateTime = formatDateTime;