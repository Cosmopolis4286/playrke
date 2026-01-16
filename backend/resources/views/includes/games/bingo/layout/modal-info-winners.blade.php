{{-- resources/views/includes/modal-info-winners.blade.php  --}}
<div class="modal fade" id="modal-info-winners" tabindex="-1" role="dialog" aria-labelledby="modal-info-winnersLabel"
    aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-sm mx-auto" role="document" style="max-width: 320px">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title mt-n1" id="modal-info-winnersLabel">Información del Juego</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="input-group mb-2">
                    <div class="input-group-prepend">
                        <span class="input-group-text">Ronda</span>
                    </div>
                    <input type="number" step="1" inputmode="numeric" id="num-round-info-winners"
                        class="form-control" style="max-width: 50px">
                </div>
                <h6>Inicio del Sorteo:</h6>
                <div class="d-flex">
                    <div id="draw-date"></div>
                    <div class="clearfix ml-2 mt-1" id="loaderCtn" style="display: none">
                        <div class="spinner-border spinner-border-sm text-primary float-left" role="status">
                            <span class="sr-only"></span>
                        </div>
                    </div>
                </div>
                <div id="cont-info-winners">
                    <span>Ganadores:</span><span class="ml-1 mr-1" id="count-winners"></span>
                    <span>|</span>
                    <span class="ml-1">Última Ficha:</span>
                    <img class="ml-1 mb-1" id="last-win-chip">
                </div>
                <div id="cont-ctn-award-winning">
                    <div class="card">
                        <div class="card-header py-1">
                            <h6 class="card-title d-inline m-0">Cartón Ganador</h6>
                            <button type="button" id="btn-close-carton-awarded" class="close" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="card container-view-carton">
                                @php
                                    $columns = ['B' => 1, 'I' => 1, 'N' => 1, 'G' => 1, 'O' => 1];
                                @endphp
                                @foreach ($columns as $letter => $_)
                                    <div class="section-letter">
                                        <div class="number-box-letter"><b>{{ $letter }}</b></div>
                                        @for ($i = 1; $i <= 5; $i++)
                                            @if ($letter === 'N' && $i === 3)
                                                <div class="number-box-view-ctn">
                                                    <img class="img-fluid img-logo-ctn"
                                                        src="{{ asset($customerLogo ?? 'img/rke.png') }}">
                                                </div>
                                            @else
                                                <div class="number-box-view-ctn">
                                                    <span id="box-{{ $letter }}{{ $i }}"
                                                        class="number-box-number"></span>
                                                </div>
                                            @endif
                                        @endfor
                                    </div>
                                @endforeach
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
                <div id="container-prize-ctns"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary leave-room">
                    {{ $isLogged ? 'Continuar' : 'Cerrar' }}
                </button>
            </div>
        </div>
    </div>
</div>
