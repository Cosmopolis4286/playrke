function markBoxes({ selector, getValue, validValues, className = 'number-box-circle' }) {
    const validSet = new Set(validValues);

    document.querySelectorAll(selector).forEach((el) => {
        const value = getValue(el);
        if (value && validSet.has(value)) {
            el.classList.add(className);
        }
    });
}

function autoMarked(checkBoxes = []) {
    // Marcar cartones principales
    markBoxes({
        selector: ".number-box[data-box]",
        getValue: el => {
            const num = Number(el.dataset.box);
            return el.textContent == num ? num : null;
        },
        validValues: allChips
    });

    // Marcar cartones premiados
    if (checkBoxes.length) {
        markBoxes({
            selector: ".number-box-view-ctn .number-box-number",
            getValue: el => el.id?.split('-')[1],
            validValues: checkBoxes
        });
    }
}

window.autoMarked = autoMarked;

