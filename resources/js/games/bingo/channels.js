function initializeEcho(generateUniqueCodeed) {
    return {
        init() {
            Echo.channel(`bingo.${generateUniqueCodeed}`)
                .listen('BingoOFF_GetRoomInfo', (event) => {
                    console.log('Second Event:', event.data);
                })
                .listen('BingoOFF_ProccessDraw', (event) => {
                    console.log('Chip:', event.chip);
                    if (event.infoGame) {
                        const result = event.infoGame;
                    }
                    getNewChip(event.chip);
                });
        }
    }
}

// Asegúrate de que la función esté disponible globalmente
window.initializeEcho = initializeEcho;