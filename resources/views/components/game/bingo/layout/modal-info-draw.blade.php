{{-- resources/views/components/modal-info-draw.blade.php  --}}
<div class="modal fade" id="modal-info-draw" tabindex="-1" role="dialog" aria-labelledby="modal-info-drawLabel"
    aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-sm mx-auto" role="document" style="max-width: 320px">
        <div class="modal-content">
            <div class="modal-header">
                <div id="modal-info-draw-title"></div>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <button type="button" id="buy-ctns" class="btn btn-success">Comprar Cartones</button>
                </div>
                <div id="game-clock" class="mt-2">
                    <div id="cont-D" class="cont-time-clock"><span id="days"></span><span>D</span></div>
                    <div id="cont-H" class="cont-time-clock"><span id="hours"></span><span>H</span></div>
                    <div id="cont-M" class="cont-time-clock"><span id="minutes"></span><span>M</span></div>
                    <div id="cont-S" class="cont-time-clock"><span id="seconds"></span><span>S</span></div>
                </div>
                <div id="form-buy-ctns elem-hide">
                    <div class="form-group col-6 col-sm-4">
                        <label for="ctn-price">Precio Carton</label>
                        <input type="hidden" id="coin" name="coin">
                        <div class="form-control_d" id="tx-ctn-price"></div>
                    </div>
                    <div class="form-group col-6 col-sm-3">
                        <label for="num-ctns">#CTNs</label>
                        <input id="num-ctns" class="form-control" type="number" name="num_ctns" min="1"
                            max="30" required disabled>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
