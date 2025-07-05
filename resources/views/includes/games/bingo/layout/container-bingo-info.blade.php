<div id="container-bingo-info" class="mb-2 p-2">
    <div id="cont-prize-pool-info">
        <div id="container-btn-options" class="mr-2">
            <div class="btn-group-vertical">
                <button type="button" disabled id="btn-view-winners" class="btn btn-sm btn-dark">
                    <i class="fa-solid fa-trophy"></i>
                </button>
                <button type="button" disabled id="btn-bingo-rules" class="btn btn-sm btn-dark" data-toggle="modal"
                    data-target="#modal-bingo-rules">
                    <i class="fa-solid fa-circle-info"></i>
                </button>
            </div>
        </div>
        <div id="cont-prize-pool-info-amount">
            <span id="cont-prize-pool-type">{{ $prizePoolType }}</span>
            <div id="cont-prize-pool-amount">
                <span id="prize-pool-coin-sym">{{ $coinSym }}</span>
                <span id="prize-pool-amount">{{ $prizePool ?? 0 }}</span>
            </div>
        </div>
    </div>
    <div id="cont-bingo-info-extras">
        <div id="cont-game-mode-opcs">
            @if ($room->Config->rounds)
                <div id="container-round">Ronda<span id="num-round">#{{ $round }}</span></div>
            @endif
            <div id="text-game-mode">{{ $bingoPattern }}</div>
        </div>
    </div>
</div>
