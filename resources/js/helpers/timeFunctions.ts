// Función para obtener el desplazamiento GMT del equipo
function getGMTOffset() {
    const offset = new Date().getTimezoneOffset();
    const sign = offset > 0 ? "-" : "+";
    const absOffset = Math.abs(offset);
    const hours = String(Math.floor(absOffset / 60)).padStart(2, '0');
    const minutes = String(absOffset % 60).padStart(2, '0');
    return `${sign}${hours}:${minutes}`;
}

// Función para convertir y mostrar la hora local del equipo desde un timestamp Unix
function convertAndDisplay(unixTimestamp, seconds = false) {
    if (!unixTimestamp) return;

    const date = new Date(parseInt(unixTimestamp) * 1000); // Convertir a milisegundos
    const formattedDate = date.toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });

    let timeOptions = {
        hour: '2-digit',
        minute: '2-digit'
    };

    if (seconds) {
        timeOptions.second = '2-digit';
    }

    const formattedTime = date.toLocaleTimeString('es-ES', timeOptions);

    const formattedDateTime = `${formattedDate} - ${formattedTime}`;
    const gmtOffset = getGMTOffset();
    const formattedOutput = `${formattedDateTime}`;

    return formattedOutput;
}
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

window.convertAndDisplay = convertAndDisplay;