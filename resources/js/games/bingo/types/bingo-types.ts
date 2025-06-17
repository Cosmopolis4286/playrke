// resources/js/games/bingo/types/bingo-types.ts

export interface Room {
    id: number;
    name: string;
    status: number;
    // ...otros campos
}

export interface Player {
    id: number;
    name: string;
    // ...
}

export interface Carton {
    id: number;
    numbers: number[];
    matched: number[];
    // ...
}

export interface BingoProps {
    room: Room;
    cartons: Carton[];
    customerLogo: string;
    coinSym: string;
    vanDraw: any;
    player: Player;
    chipsRest: any;
    draw: any;
    timeServ: number;
    allChips: any;
    infoGame: any;
    round: number;
    closingRoute: string;
    startTime: number;
    creatorType: string;
    creatorID: number;
    coinID: number;
    ctnPrice: number;
    encrypt: any;
    random: any;
}
