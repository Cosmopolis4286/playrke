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
            <div id="text-game-mode">Cartón Lleno</div>
            <div id="cont-game-ops" class="mt-1">
                <div class="btn-group" role="group" aria-label="Opciones">
                    <button type="button" disabled id="btn-view-winners" class="btn btn-sm btn-primary">
                        <i class="fa-solid fa-trophy"></i>
                    </button>
                    <button type="button" disabled id="btn-bingo-rules" class="btn btn-sm btn-primary"
                        data-toggle="modal" data-target="#modal-bingo-rules">
                        <i class="fa-solid fa-circle-info"></i>
                    </button>
                    <button type="button" id="btn-volume" class="btn btn-sm btn-secondary"
                        @if ($room->status != 3) disabled @endif>
                        <i id="icon-volume" class="fa-solid fa-volume-xmark"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
