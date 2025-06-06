<div id="container-bingo-info" class="mb-2 p-2">
    <div id="cont-prize-pool-info">
        <div id="cont-prize-chest" class="mr-1">
            <img id="image-prize-chest" src="{{ asset('img/cofre.png') }}">
        </div>
        <div id="cont-prize-pool-info-amount" class="p-1">
            <small id="cont-prize-pool-type">
                {{ $prizePoolType == 1 ? 'Bote' : ($prizePoolType == 2 ? 'Premio' : '') }}
            </small>
            <div id="cont-prize-pool-amount">
                <small id="prize-pool-coin-sym" class="mr-1">{{ $coinSym }}</small>
                <span id="prize-pool-amount">{{ $prizePool ?? 0 }}</span>
            </div>
        </div>
    </div>
    <div id="cont-bingo-info-extras">
        <div id="cont-game-mode-opcs">
            @if ($room->Config->rounds)
                <div id="container-round">Ronda<span id="num-round">#{{ $round }}</span></div>
            @endif
            <div id="text-game-mode"><span class="mx-1">Cartón Lleno</span></div>
            <div id="container-btn-options">
                <div class="btn-group btn-group-sm" role="group" aria-label="Opciones">
                    <button type="button" disabled id="btn-view-winners" class="btn btn-sm btn-success">
                        <i class="fa-solid fa-trophy"></i>
                    </button>
                    <button type="button" disabled id="btn-bingo-rules" class="btn btn-sm btn-secondary"
                        data-toggle="modal" data-target="#modal-bingo-rules">
                        <i class="fa-solid fa-circle-info"></i>
                    </button>
                    @if ($room->status == 3)
                        <button type="button" id="btn-volume" class="btn btn-sm btn-secondary">
                            <i id="icon-volume" class="fa-solid fa-volume-xmark"></i>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
