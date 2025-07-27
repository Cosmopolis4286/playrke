function autoMarked() {
    const validSet = new Set(allChips);

    document.querySelectorAll('.card[data-carton-index]').forEach((cardEl, index) => {
        const carton = allCartons[index];
        const fields = ['b1', 'b2', 'b3', 'b4', 'b5', 'i1', 'i2', 'i3', 'i4', 'i5', 'n1', 'n2', 'n4', 'n5', 'g1', 'g2', 'g3', 'g4', 'g5', 'o1', 'o2', 'o3', 'o4', 'o5'];

        fields.forEach(field => {
            const box = cardEl.querySelector(`.number-box[data-pos="${field}"]`);
            if (!box) return;

            const expectedNum = Number(carton[field]);
            if (isNaN(expectedNum)) return;

            let span = box.querySelector('.number-box-number');
            const currentText = span?.textContent.trim() ?? '';
            const currentNum = Number(currentText);

            // Corregir número si fue manipulado o falta
            if (!span || isNaN(currentNum) || currentNum !== expectedNum) {
                box.innerHTML = `<span class="number-box-number">${expectedNum}</span>`;
                span = box.querySelector('.number-box-number');
            }

            // Añadir o quitar círculo según si está en validSet
            const circle = box.querySelector('.number-box-circle');

            if (validSet.has(expectedNum)) {
                if (!circle) {
                    const newCircle = document.createElement('div');
                    newCircle.classList.add('number-box-circle');
                    box.appendChild(newCircle);
                }
                // Aplica color blanco al número
                const span = box.querySelector('.number-box-number');
                if (span) {
                    span.style.color = 'var(--white)';
                }
            } else {
                if (circle) circle.remove();
                // Quita el color blanco al número si ya no está marcado
                const span = box.querySelector('.number-box-number');
                if (span) {
                    span.style.color = ''; // vuelve al color por defecto
                }
            }
        });
    });
}

function clearMarkedBoxes() {
    const selector = ".number-box-view-ctn .number-box-number";
    const className = 'number-box-circle';

    document.querySelectorAll(selector).forEach((el) => {
        el.textContent = '';
        el.classList.remove(className);
    });

    $('#cont-ctn-award-winning').hide();
    $("#ctn-player-name").text('playerName');
    $("#serial-ctn").text('');
}

function markCartonNumbers(data = {}) {
    // Limpiar casillas
    clearMarkedBoxes();

    // Si es un carton premiado por serial retorna 
    if (data.srl) return;

    const carton = data.carton;
    let checkBoxes = [];

    // Llenar números del carton y crear lista de números a marcar
    Object.entries(carton).forEach(([key, elem]) => {
        const target = document.querySelector(`#box-${key.toUpperCase()}`);
        if (target) {
            target.textContent = elem.value;
            if (elem.check) {
                checkBoxes.push(Number(elem.value));
            }
        }
    });

    const validSet = new Set(checkBoxes);

    const positions = [
        'B1', 'B2', 'B3', 'B4', 'B5',
        'I1', 'I2', 'I3', 'I4', 'I5',
        'N1', 'N2', 'N4', 'N5',
        'G1', 'G2', 'G3', 'G4', 'G5',
        'O1', 'O2', 'O3', 'O4', 'O5'
    ];

    positions.forEach(pos => {
        const span = document.getElementById(`box-${pos}`);
        if (!span) return;

        // Obtener número esperado del carton (prop nombre en minúscula)
        const expectedNum = Number(carton[pos]?.value);

        if (isNaN(expectedNum)) return;

        // Colocar el número correcto
        span.textContent = expectedNum;

        // El contenedor padre para añadir/eliminar el círculo
        const container = span.parentElement;
        if (!container) return;

        // Buscar si ya tiene el círculo
        let circle = container.querySelector('.number-box-circle');

        if (validSet.has(expectedNum)) {
            // Añadir círculo si no existe
            if (!circle) {
                circle = document.createElement('div');
                circle.classList.add('number-box-circle');
                container.appendChild(circle);
            }
            // Cambiar color del número a blanco
            span.style.color = 'var(--white)';
        } else {
            // Quitar círculo si existe
            if (circle) circle.remove();
            // Color normal
            span.style.color = '';
        }
    });

    $("#ctn-player-name").text(data.playerName);
    $("#serial-ctn").text(`Serial: ${data.serialCtn}`);
    $("#cont-ctn-award-winning").show();
}

window.autoMarked = autoMarked;
window.clearMarkedBoxes = clearMarkedBoxes;
window.markCartonNumbers = markCartonNumbers;