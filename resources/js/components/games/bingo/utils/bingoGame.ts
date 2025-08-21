import { allChips, renderChipsView } from './chips';
import { soundNext, actSound } from './sound';

export let endWin = 0;

export function endGame(closingRoute: string | null, useTimeout = true) {
    if (!closingRoute) return;
    const redirect = () => window.location.href = closingRoute;
    if (useTimeout) setTimeout(redirect, 25000);
    else redirect();
}

export function handleNewChip(newChip: number, preRoute: string) {
    if (allChips.includes(newChip)) return;
    const containerLastChips = document.getElementById('cont-last-chips')!;
    const chipDiv = document.createElement('div');
    chipDiv.className = 'cont-img-chip';
    const img = document.createElement('img');
    img.className = 'img-chip zoom';
    img.src = `${preRoute}img/bg/chips/${newChip}.png`;
    img.alt = newChip.toString();
    chipDiv.appendChild(img);

    containerLastChips.prepend(chipDiv);

    if (actSound === 1) soundNext.play();

    allChips.unshift(newChip);

    renderChipsView(preRoute, null, newChip);
}
