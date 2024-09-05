{{-- resources/views/components/modal-info-draw.blade.php  --}}
<div class="modal fade" id="modal-info-draw" tabindex="-1" role="dialog" aria-labelledby="modal-info-drawLabel"
    aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-sm mx-auto" role="document" style="max-width: 320px">
        <div class="modal-content">
            <div class="modal-header">
                <div id="modal-info-draw-title"></div>
            </div>
            <div class="modal-body pb-2">
                <div id="cont-btn-buy-ctns" class="text-center mb-2">
                    <button type="button" id="btn-buy-ctns" class="btn btn-danger mdl-buy-ctns">Comprar
                        Cartones</button>
                </div>
                <div id="game-clock" class="mb-1">
                    <div id="cont-D" class="cont-time-clock"><span id="days"></span><span>D</span></div>
                    <div id="cont-H" class="cont-time-clock"><span id="hours"></span><span>H</span></div>
                    <div id="cont-M" class="cont-time-clock"><span id="minutes"></span><span>M</span></div>
                    <div id="cont-S" class="cont-time-clock"><span id="seconds"></span><span>S</span></div>
                </div>
                <div class="card text-center hide-elem" id="card-form-buy-ctns">
                    <div class="card-header py-1">COMPRAR CARTONES</div>
                    <section id="form-buy-ctns">
                        <div class="card-body py-2">
                            @if ($sellerType = 'comm')
                                <div id="commerce-details" class="row hide-elem">
                                    <div class="col-12">
                                        <h5 class="mb-2">¿Enviaste <span id="sent-amount"></span>?</h5>
                                        <div id="cont-payment-method"
                                            class="mt-n1 d-flex justify-content-center flex-column">
                                            <span id="commerce-name"></span>
                                            <h6 id="payment-subtitle"></h6>
                                            <span id="payment-method-1" class="payment-method"></span>
                                            <span id="payment-method-2" class="payment-method"></span>
                                            <span id="payment-method-3" class="payment-method"></span>
                                        </div>
                                    </div>
                                    <div class="col-12 mt-1 text-center">
                                        <label for="payment-proof">Cargar Comprobante:</label>
                                        <input type="file" id="payment-proof" class="form-control-file"
                                            name="payment_proof" accept="image/png, image/jpeg" required>
                                    </div>
                                </div>
                                <hr>
                            @endif
                            <div class="alert alert-primary py-1" role="alert">
                                <span><b>Precio Cartón:</b></span>
                                <span id="ctn-price-coin-symbol" class="mr-1"></span>
                                <span id="ctn-price-amount"></span>
                            </div>
                            @if ($sellerType = 'comm')
                                <div class="form-group">
                                    <input type="text" class="form-control" id="purchase-code" name="purchase_code"
                                        placeholder="Código de Compra" required>
                                </div>
                            @else
                                <div class="col-12 d-flex justify-content-center">
                                    <img class="mr-1" src="{{ asset('img/cofre.png') }}"
                                        style="width: 28px; height:28px" alt="{{ $customerName }}">
                                    <label for="customer-name">{{ $customerName }}</label>
                                </div>
                            @endif
                            <div class="form-group">
                                <input type="number" class="form-control" id="num-ctns" name="num_ctns"
                                    placeholder="Cantidad de Cartones" step="1" min="1" max="30" required>
                            </div>
                            <span class="mt-3 card-text mb-n3" style="cursor: pointer">¿No tienes un código de
                                compra?</span>
                        </div>
                        <div class="card-footer d-flex justify-content-center">
                            <button type="button" id="btn-cancel-buy-ctns"
                                class="mdl-buy-ctns btn btn-sm btn-secondary mx-1">Cancelar</button>
                            <button type="button" id="btn-buy-ctns-confirm"
                                class="mdl-buy-ctns btn btn-success mx-1">Comprar</button>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
</div>
