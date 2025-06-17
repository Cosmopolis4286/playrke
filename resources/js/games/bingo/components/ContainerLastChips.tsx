import React from 'react';
import type { Room } from '../types/bingo-types';

interface ContainerLastChipsProps {
  room: Room;
}

const ContainerLastChips: React.FC<ContainerLastChipsProps> = ({ room }) => {
  return (
    <div className="position-relative">
      <div id="cont-last-chips" className="py-2">
        <div id="cont-loader">
          <div id="loader-last-chips"></div>
        </div>
      </div>
      {room.status === 3 && (
        <div id="text-check-bingo">
          Comprobando si hay <b>BINGO</b>
        </div>
      )}
    </div>
  );
};

export default ContainerLastChips;
