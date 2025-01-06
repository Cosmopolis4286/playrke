{{-- resources/views/includes/modal-info-draw.blade.php  --}}
<div class="modal fade" id="modal-info-draw" tabindex="-1" role="dialog" aria-labelledby="modal-info-drawLabel"
    aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-sm mx-auto" role="document" style="max-width: 325px">
        <div class="modal-content">
            <div class="modal-header">
                <div id="modal-info-draw-title"></div>
            </div>
            <div class="modal-body pb-2">
                <div class="d-flex justify-content-center">
                    <button type="button" id="btn-buy-ctns" class="text-center mb-2 btn btn-danger">
                        Comprar Cartones</button>
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
                            @if ($creatorType !== 'customer')
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
                                            accept="image/png, image/jpeg" required>
                                    </div>
                                </div>
                                <hr>
                            @endif
                            @if ($creatorType === 'customer')
                                <div class="col-12 mb-2">
                                    {{-- <img class="mr-1" src="{{ asset('img/cofre.png') }}"
                                style="width: 28px; height:28px" alt="{{ $customerName }}"> --}}
                                    <small class="card-text"
                                        style="text-transform: uppercase">{{ $customerName }}</small>
                                </div>
                            @endif
                            <div class="alert alert-primary py-1" role="alert">
                                <span><b>Precio Cartón:</b></span>
                                <span id="ctn-price-coin-symbol"></span>
                                <span id="ctn-price-amount"></span>
                            </div>
                            @isset($saleLink)
                                <div class="form-group">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <sapn class="input-group-text" for="coin">Moneda</span>
                                        </div>
                                        @if (!$player)
                                            <select class="custom-select" id="coin">
                                                <option value="" disabled selected>Seleccionar</option>
                                                @foreach ($coinsAvailable as $item)
                                                    <option value="{{ encrypt($item->id) }}">
                                                        {{ $item->long_name }} ({{ $item->symbol }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        @else
                                            <div class="form-control text-left">{{ $coinsAvailable->long_name }}
                                                ({{ $coinsAvailable->symbol }})</div>
                                            <input type="hidden" id="coin" value="{{ encrypt($coinsAvailable->id) }}">
                                        @endif
                                    </div>
                                </div>
                            @endisset
                            <div class="form-group">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text" id="basic-addon1">Cantidad</span>
                                    </div>
                                    <input type="number" class="form-control" id="num-ctns" title="Cantidad a Comprar"
                                        step="1" value="1" min="1" max="35" required>
                                </div>
                                <div class="ml-1 d-flex mt-1 mb-n2">
                                    <b class="mr-1">Total:</b><span id="total-amount"></span>
                                </div>
                            </div>
                            @if ($creatorType === 'commerce')
                                <div class="form-group">
                                    <input type="text" class="form-control" id="purchase-code"
                                        placeholder="Código de Compra" required maxlength="8">
                                    <span class="mt-2 badge badge-light" style="cursor: pointer; font-size: 11.5px;">
                                        ¿No tienes un código de compra?</span>
                                </div>
                            @endif
                        </div>
                        <div class="card-footer d-flex justify-content-center">
                            <button type="button" id="btn-cancel-buy-ctns"
                                class="btn btn-sm btn-secondary mx-1">Cancelar</button>
                            <button id="btn-buy-ctns-confirm" class="mdl-buy-ctns btn btn-success mx-1"
                                type="button">Comprar
                                <span id="spinner-btn-buy-ctns-confirm" class="spinner-grow spinner-grow-sm hide-elem"
                                    role="status" aria-hidden="true"></span>
                            </button>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
</div>
