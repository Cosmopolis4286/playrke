{{-- resources/views/components/modal-info-winners.blade.php  --}}
<div class="modal fade" id="modal-info-winners" tabindex="-1" role="dialog" aria-labelledby="modal-info-winnersLabel"
    aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-sm mx-auto" role="document" style="max-width: 320px">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-info-winnersLabel">Información del Juego</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="cont-ctn-award-winning">
                    <div class="card">
                        <div class="card-header py-1">
                            <h6 class="card-title d-inline m-0">Cartón Ganador</h6>
                            <button type="button" id="btn-close-carton-awarded" class="close" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="card container-carton mb-n1">
                                <div class="section-letter">
                                    <div class="number-box-letter"><b>B</b></div>
                                    <div class="number-box-view-ctn">
                                        <span id="ctn-vchip0" class="number-box-number"></span>
                                    </div>
                                    <div class="number-box-view-ctn">
                                        <span id="ctn-vchip1" class="number-box-number"></span>
                                    </div>
                                    <div class="number-box-view-ctn">
                                        <span id="ctn-vchip2" class="number-box-number"></span>
                                    </div>
                                    <div class="number-box-view-ctn">
                                        <span id="ctn-vchip3" class="number-box-number"></span>
                                    </div>
                                    <div class="number-box-view-ctn">
                                        <span id="ctn-vchip4" class="number-box-number"></span>
                                    </div>
                                </div>
                                <div class="section-letter">
                                    <div class="number-box-letter"><b>I</b></div>
                                    <div class="number-box-view-ctn">
                                        <span id="ctn-vchip5" class="number-box-number"></span>
                                    </div>
                                    <div class="number-box-view-ctn">
                                        <span id="ctn-vchip6" class="number-box-number"></span>
                                    </div>
                                    <div class="number-box-view-ctn">
                                        <span id="ctn-vchip7" class="number-box-number"></span>
                                    </div>
                                    <div class="number-box-view-ctn">
                                        <span id="ctn-vchip8" class="number-box-number"></span>
                                    </div>
                                    <div class="number-box-view-ctn">
                                        <span id="ctn-vchip9" class="number-box-number"></span>
                                    </div>
                                </div>
                                <div class="section-letter">
                                    <div class="number-box-letter"><b>N</b></div>
                                    <div class="number-box-view-ctn">
                                        <span id="ctn-vchip10" class="number-box-number"></span>
                                    </div>
                                    <div class="number-box-view-ctn" class="number-box-number">
                                        <span id="ctn-vchip11"></span>
                                    </div>
                                    <div class="number-box-view-ctn">
                                        <img class="img-fluid img-logo-ctn" src="{{ asset('img/rke.png') }}"
                                            alt="PlayRKE">
                                    </div>
                                    <div class="number-box-view-ctn">
                                        <span id="ctn-vchip12" class="number-box-number"></span>
                                    </div>
                                    <div class="number-box-view-ctn">
                                        <span id="ctn-vchip13" class="number-box-number"></span>
                                    </div>
                                </div>
                                <div class="section-letter">
                                    <div class="box number-box-letter"><b>G</b></div>
                                    <div class="number-box-view-ctn">
                                        <span id="ctn-vchip14" class="number-box-number"></span>
                                    </div>
                                    <div class="number-box-view-ctn">
                                        <span id="ctn-vchip15" class="number-box-number"></span>
                                    </div>
                                    <div class="number-box-view-ctn">
                                        <span id="ctn-vchip16" class="number-box-number"></span>
                                    </div>
                                    <div class="number-box-view-ctn">
                                        <span id="ctn-vchip17" class="number-box-number"></span>
                                    </div>
                                    <div class="number-box-view-ctn">
                                        <span id="ctn-vchip18" class="number-box-number"></span>
                                    </div>
                                </div>
                                <div class="section-letter">
                                    <div class="number-box-letter"><b>O</b></div>
                                    <div class="number-box-view-ctn">
                                        <span id="ctn-vchip19" class="number-box-number"></span>
                                    </div>
                                    <div class="number-box-view-ctn">
                                        <span id="ctn-vchip20" class="number-box-number"></span>
                                    </div>
                                    <div class="number-box-view-ctn">
                                        <span id="ctn-vchip21" class="number-box-number"></span>
                                    </div>
                                    <div class="number-box-view-ctn">
                                        <span id="ctn-vchip22" class="number-box-number"></span>
                                    </div>
                                    <div class="number-box-view-ctn">
                                        <span id="ctn-vchip23" class="number-box-number"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-muted py-1">
                            <div id="container-info-win-ctn">
                                <span id="ctn-player-name"></span>
                                <span id="serial-ctn"></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-flex">
                    <div>
                        <h6>Inicio del Sorteo:</h6> {{ $room->date }} {{ $room->time }} <small>GTM-4</small>
                    </div>
                    <div class="clearfix" id="loaderCtn" style="display: none">
                        <div class="spinner-border text-primary float-left" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>
                </div>
                <div id="cont-info-winners">
                    <span>Ganadores:</span><span class="ml-1 mr-1" id="count-winners"></span>
                    <span>|</span>
                    <span class="ml-1">Última Ficha:</span>
                    <img class="ml-1 mb-1" id="last-win-chip">
                </div>
                <b>Premio Mayor: <small>(<span id="jackpot-value"></span>% del Bote)</small></b>
                <div class="row mb-1" id="jackpot-winners"></div>
                <div id="cont-serials" class="hide-elem">
                    <b>Seriales: <small>(<span id="serial-value"></span>% del Bote)</small></b>
                    <div class="row" id="serials-winners"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary leave-room">Continuar</button>
            </div>
        </div>
    </div>
</div>
