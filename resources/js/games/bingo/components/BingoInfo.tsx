import React from 'react';
import ContainerLastChips from './ContainerLastChips';
import ContainerBingoInfo from './ContainerBingoInfo';
import ContainerHistoryChips from './ContainerHistoryChips';
import type { BingoProps } from '../types/bingo-types';

const BingoInGame: React.FC<BingoProps> = (props) => {
    const { latestChips, coinSym, room, infoGame, round, historyChips } = props;

    return (
        <div className="col-md-11 col-lg-7 col-xl-8 m-auto">
            <ContainerLastChips room={room} />
            {/* <ContainerBingoInfo roomStatus={room.status} infoGame={infoGame} round={round} />
            {room.status > 2 && <ContainerHistoryChips historyChips={historyChips} />} */}
        </div>
    );
};

export default BingoInGame;
