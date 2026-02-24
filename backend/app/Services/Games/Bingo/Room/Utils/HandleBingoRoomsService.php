<?php

namespace App\Services\Games\Bingo\Room;

use App\Models\BingoRoom;
use App\Services\Games\Bingo\Rooms\{RoomClosureService, FinishedRoomService};

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
        BingoRoom::with('Config:room,unix_date')
            ->whereIn('status', [2, 3])
            ->when($creatorUser, fn($q) => $q->where('creator_user_id', $creatorUser))
            ->when($room, fn($q) => $q->where('id', $room))
            ->get('id')
            ->each(fn($r) => $this->closureService->processClosure($r));

        $this->handleFinishedRooms($creatorUser);
    }

    public function handleFinishedRooms($creatorUser = false): void
    {
        BingoRoom::with('Config:room,date,time')
            ->where('status', 4)
            ->when($creatorUser, fn($q) => $q->where('creator_user_id', $creatorUser))
            ->get(['id', 'creator_user_id'])
            ->each(fn($room) => $this->finishedService->processFinishedRoom($room));
    }
}
