import { createElement, animate } from './utils';

export let allChips: number[] = [];
export let posChip: number[] = [0];
export let lastRightChip: number | null = null;
export let isAnimating = false;
export let sizeChip = 0;
export let newMargin = 0;
export let maxPerView = 0;

export const containerHistoryChips = document.getElementById('container-history-chips')!;
export const containerAllChips = document.getElementById('container-all-chips')!;

export function resizeChips() {
    const containerHeight = containerHistoryChips.clientHeight;
    const firstChip = containerHistoryChips.querySelector<HTMLImageElement>('.img-chip');

    if (!firstChip) return;

    sizeChip = firstChip.width * 0.7;
    if (firstChip.width > containerHeight) sizeChip = containerHeight * 0.8;

    newMargin = sizeChip * 0.07;

    const chipContainers = containerHistoryChips.querySelectorAll<HTMLElement>('.container-image-history');
    chipContainers.forEach(c => {
        c.style.width = `${sizeChip}px`;
        c.style.height = `${sizeChip}px`;
        c.style.margin = `0 ${newMargin}px`;
    });
}

export function createChipElement(chip: number, preRoute: string) {
    const container = createElement('div', { className: 'container-image-history', styles: { width: `${sizeChip}px`, height: `${sizeChip}px`, margin: `0 ${newMargin}px` } });
    const img = createElement('img', { className: 'image-history img-chip', src: `${preRoute}img/bg/chips/${chip}.png`, alt: chip.toString() }) as HTMLImageElement;
    img.loading = 'lazy';
    container.appendChild(img);
    return container;
}

export function renderChipsView(preRoute: string, direction: 'L' | 'R' | null = null, newChip: number | null = null) {
    if (!allChips.length || isAnimating) return;
    const container = containerAllChips;
    const chipWidth = sizeChip + newMargin * 2;
    const containerWidth = (container.parentElement?.clientWidth || 0);
    maxPerView = Math.min(Math.floor(containerWidth / chipWidth), 12);
    const totalChips = allChips.length;

    if (!direction && !newChip) {
        container.innerHTML = '';
        allChips.slice(posChip[0], posChip[0] + maxPerView).forEach(chip => {
            container.appendChild(createChipElement(chip, preRoute));
        });
        lastRightChip = allChips[posChip[0] + maxPerView - 1];
        posChip[1] = posChip[0] + maxPerView - 1;
    } else if (newChip) {
        isAnimating = true;
        const chipEl = createChipElement(newChip, preRoute);
        chipEl.classList.add('fade-in-right');
        container.prepend(chipEl);
        if (container.children.length > maxPerView) container.removeChild(container.lastElementChild!);
        animate(250, () => { chipEl.classList.remove('fade-in-right'); isAnimating = false; });
        allChips.unshift(newChip);
    }
    // Lógica izquierda/derecha simplificada para optimizar
    else if (direction === 'L' && posChip[0] > 0) {
        posChip[0]--;
        isAnimating = true;
        const chipEl = createChipElement(allChips[posChip[0]], preRoute);
        chipEl.classList.add('fade-in-left');
        container.prepend(chipEl);
        container.removeChild(container.lastElementChild!);
        animate(250, () => { chipEl.classList.remove('fade-in-left'); isAnimating = false; });
    } else if (direction === 'R' && (posChip[0] + maxPerView) < totalChips) {
        posChip[0]++;
        isAnimating = true;
        const chipEl = createChipElement(allChips[posChip[0] + maxPerView - 1], preRoute);
        chipEl.classList.add('fade-in-right');
        container.appendChild(chipEl);
        container.removeChild(container.firstElementChild!);
        animate(250, () => { chipEl.classList.remove('fade-in-right'); isAnimating = false; });
    }
}
