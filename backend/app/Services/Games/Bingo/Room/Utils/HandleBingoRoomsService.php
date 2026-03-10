<?php

namespace App\Services\Games\Bingo\Room\Utils;

use App\Models\Games\Bingo\Room\BingoRoom;
use App\Services\Games\Bingo\Room\Utils\RoomClosureService;
use App\Services\Games\Bingo\Room\Utils\FinishedRoomService;

final class HandleBingoRoomsService
{
    private RoomClosureService $closureService;
    private FinishedRoomService $finishedService;

    public function __construct(RoomClosureService $closureService, FinishedRoomService $finishedService)
    {
        $this->closureService = $closureService;
        $this->finishedService = $finishedService;
    }

    public function checkRoomClosure($room = '', bool $creatorUser = false): void
    {
        $query = BingoRoom::where('deleted_at', null)
            ->whereIn('status_id', [2, 3])
            ->when($creatorUser, fn($q) => $q->where('creator_user_id', $creatorUser))
            ->when($room, fn($q) => $q->where('id', $room))
            ->get('id')
            ->each(fn($r) => $this->closureService->processClosure($r));

        $this->handleFinishedRooms($creatorUser);
    }

    public function handleFinishedRooms($creatorUser = false): void
    {
        BingoRoom::with('Config:room,date,time')
            ->where('status_id', 4)
            ->when($creatorUser, fn($q) => $q->where('creator_user_id', $creatorUser))
            ->get(['id', 'creator_user_id'])
            ->each(fn($room) => $this->finishedService->processFinishedRoom($room));
    }
}
