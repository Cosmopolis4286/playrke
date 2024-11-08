<div id="container-bingo-info" class="p-2">
    <div id="cont-jackpot-info">
        <div id="cont-prize-chest" class="mr-1">
            <img id="image-prize-chest" src="{{ asset('img/cofre.png') }}">
        </div>
        <div id="cont-jackpot-info-amount" class="p-1">
            <small id="cont-jackpot-type">
                {{ $jackpotType == 1 ? 'Bote' : ($jackpotType == 2 ? 'Premio' : '') }}
            </small>
            <div id="cont-jackpot-amount">
                <small id="jackpot-coin-sym" class="mr-2">{{ $coinSym }}</small>
                <span id="jackpot-amount">{{ $jackpot ?? 0 }}</span>
            </div>
        </div>
    </div>
    <div id="cont-bingo-info-extras">
        {{-- <div id="cont-image-publish">
            <img id="image-publish" src='{{ asset('/img/bg/estelar.png') }}'>
        </div> --}}
        <div id="cont-game-mode-opcs">
            <div id="text-game-mode">Cartón&nbspLleno</div>
            <div id="cont-game-ops" class="mt-1">
                <div class="btn-group" role="group" aria-label="Opciones">
                    <button disabled type="button" id="btn-view-winners" class="btn btn-sm btn-primary">
                        <i class="fa-solid fa-trophy"></i>
                    </button>
                    {{-- <button disabled type="button" id="btn-view-history-chips" class="btn btn-sm btn-primary">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </button> --}}
                    <button type="button" id="btn-volume" class="btn btn-sm btn-secondary"
                        @if ($room->status != 3) disabled @endif>
                        <i id="icon-volume" class="fa-solid fa-volume-xmark"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
