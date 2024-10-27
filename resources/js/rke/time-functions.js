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

function countdownt(startTime) {
    // Establece la fecha y hora de destino
    const targetDate = startTime;

    // Calcula la diferencia entre la fecha de destino y la fecha actual en segundos
    const distance = Math.floor((targetDate - timeServ));

    // Calcula los días, horas, minutos y segundos restantes
    const days = Math.floor(distance / (60 * 60 * 24));
    const hours = Math.floor((distance % (60 * 60 * 24)) / (60 * 60));
    const minutes = Math.floor((distance % (60 * 60)) / 60);
    const seconds = Math.floor(distance % 60);

    // Muestra los resultados en los elementos correspondientes
    $('#days').text(days);
    $('#hours').text(hours);
    $('#minutes').text(minutes);
    $('#seconds').text(seconds);

    // Si la cuenta regresiva ha terminado, muestra un mensaje
    if (distance <= 0) {
        $('#game-clock').html("¡La cuenta regresiva ha terminado!");
        if (waitingTime) {
            clearInterval(waitingTime);
            setTimeout(function () {
                location.reload();
            }, 3000);
        }
    } else {
        if (days == 0) $('#cont-D').hide();
        $('#cont-H').toggle(hours !== 0);
        $('#cont-M').toggle(minutes !== 0);
    }
}

window.convertAndDisplay = convertAndDisplay;
window.countdownt = countdownt;
