{{-- resources/views/components/modal-info-draw.blade.php  --}}
<div class="modal fade" id="modal-info-draw" tabindex="-1" role="dialog" aria-labelledby="modal-info-drawLabel"
    aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-sm mx-auto" role="document" style="max-width: 270px">
        <div class="modal-content">
            <div class="modal-header">
                <div id="modal-info-draw-title"></div>
            </div>
            <div class="modal-body">
                <div id="cont-btn-buy-ctns" class="text-center mb-2">
                    <button type="button" id="btn-buy-ctns" class="btn btn-danger mdl-buy-ctns">Comprar
                        Cartones</button>
                </div>
                <div id="game-clock">
                    <div id="cont-D" class="cont-time-clock"><span id="days"></span><span>D</span></div>
                    <div id="cont-H" class="cont-time-clock"><span id="hours"></span><span>H</span></div>
                    <div id="cont-M" class="cont-time-clock"><span id="minutes"></span><span>M</span></div>
                    <div id="cont-S" class="cont-time-clock"><span id="seconds"></span><span>S</span></div>
                </div>
                <section id="form-buy-ctns" class="hide-elem">
                    <div class="row">
                        <div class="form-group col-7">
                            <label for="ctn-price">Precio Cartón:</label>
                            <div class="form-control_d">
                                <span id="ctn-price-coin-symbol" class="mr-1"></span>
                                <span id="ctn-price-amount"></span>
                            </div>
                        </div>
                        <div class="form-group col-5">
                            <label for="num-ctns">Cantidad:</label>
                            <input id="num-ctns" class="form-control" type="number" name="num_ctns" value="1"
                                step="1" min="1" max="30" required>
                        </div>
                        @if ($sellerType = 'comm')
                            <div class="col-12">@include('components.info-payment-form')</div>
                        @endif
                        <div class="col-12 text-center">
                            <button type="button" id="btn-cancel-buy-ctns"
                                class="btn btn-secondary mdl-buy-ctns">Cancelar</button>
                            <button type="button" id="btn-buy-ctns-confirm" class="btn btn-primary">Comprar</button>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>
