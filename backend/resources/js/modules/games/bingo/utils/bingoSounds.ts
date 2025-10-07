export let actSound = 0;
export const soundNext = new Audio(); // asigna tu src

export function toggleSound() {
    if (actSound === 1 || actSound === 2) {
        soundNext.muted = !soundNext.muted;
        actSound = soundNext.muted ? 2 : 1;
    }
}

export function initSound() {
    document.body.addEventListener('click', () => {
        if (actSound === 0) {
            actSound = 1;
            soundNext.muted = false;
        }
    });
}
