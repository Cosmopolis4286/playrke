function autoMarked(checkBoxes = []) {
    const markBoxes = (selector, numbers, attr = "box") => {
        document.querySelectorAll(selector).forEach((el) => {
            const num = Number(el.dataset[attr]);
            if (numbers.includes(num) && el.textContent == num) {
                el.innerHTML = `<div class="number-box-circle">${num}</div>`;
            }
        });
    };

    // Marcar cartones principales
    markBoxes(".number-box[data-box]", allChips);

    if (checkBoxes.length) {
        // // Limpiar vista secundaria
        // document.querySelectorAll(".number-box-view-ctn .number-box-number").forEach((el) => {
        //     el.textContent = '';
        // });

        // Marcar vista secundaria
        document.querySelectorAll(".number-box-view-ctn .number-box-number").forEach((el) => {
            const id = el.id?.split('-')[1]; // por ejemplo: de "box-O3" toma "O3"
            const num = Number(id?.replace(/[A-Z]/g, '')); // extrae "3" de "O3"

            if (checkBoxes.includes(num)) {
                el.innerHTML = `<div class="number-box-circle">${num}</div>`;
            }
        });
    }
}

window.autoMarked = autoMarked;

