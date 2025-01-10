function autoMarked() {
    document.querySelectorAll(".number-box").forEach((box) => {
        const numberCtn = box.dataset.num_ctn;
        if (allChips.includes(+numberCtn) && box.textContent != numberCtn) {
            box.innerHTML = `<div class="number-box-circle">${numberCtn}</div>`;
        }
    });
}

window.autoMarked = autoMarked;