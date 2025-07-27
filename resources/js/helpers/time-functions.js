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

window.convertAndDisplay = convertAndDisplay;