import { GameInfo } from '../types/bingoTypes';

export function showWinners(arrInfo: GameInfo, numRound: number, coinSym: string, logo: string) {
    const round = arrInfo.rounds[numRound];
    if (!round) return;

    const container = document.getElementById('container-prize-ctns')!;
    container.innerHTML = '';

    Object.entries(round.prizeType).forEach(([prize, data]: any) => {
        const prizeDiv = document.createElement('div');
        prizeDiv.innerHTML = `<b>${prize}: <small>(<span>${data.prizeVal}</span>% del premio)</small></b>`;
        const row = document.createElement('div');
        row.className = 'row mb-1';
        Object.entries(data.players).forEach(([serial, ctnInfo]: any) => {
            const col = document.createElement('div');
            col.className = 'col-4 my-1 cont-win-info';
            col.innerHTML = `
                <div class="cont-win-img" data-id="${ctnInfo.ctn}" title="Ver Cartón">
                    <i class="eye-icon fa-solid fa-eye"></i>
                    <img class="user-img" src="${logo}">
                    <div class="winner-info-text fw-bold badge badge-success w-100">${serial}</div>
                    <div class="winner-info-text winner-prize-amount fw-bold badge bg-navy w-100">${coinSym} ${ctnInfo.gain}</div>
                </div>`;
            row.appendChild(col);
        });
        prizeDiv.appendChild(row);
        container.appendChild(prizeDiv);
    });

    const modal = document.getElementById('cont-info-winners');
    if (modal) modal.style.display = 'block';
}
