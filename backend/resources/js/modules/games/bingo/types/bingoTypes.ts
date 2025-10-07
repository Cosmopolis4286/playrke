export interface ChipDraw {
    time: number;
    code: string;
}

export interface PlayerInfo {
    ctn: string;
    gain: number;
}

export interface PrizeData {
    players: Record<string, PlayerInfo>;
    prizeVal: number;
    numWRPT: number;
}

export interface RoundInfo {
    prizeType: Record<string, PrizeData>;
    startTime: string;
    lastChip: number;
    numWR: number;
}

export interface GameInfo {
    rounds: Record<number, RoundInfo>;
}
