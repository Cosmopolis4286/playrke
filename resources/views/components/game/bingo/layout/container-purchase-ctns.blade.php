<div id="container-purchase-ctns">
    <div class="container p-1">
        <div class="purchase-ctns__header">
            <div class="purchase-ctns__jackpot">
                <div class="purchase-ctns__jackpot-img">
                    <img class="purchase-ctns__img" src="{{ asset('img/cofre.png') }}" alt="">
                </div>
                <div class="purchase-ctns__coin">
                    <div id="purchase-ctns__coin-value">
                        <div class="purchase-ctns__coin-text">Bote:</div>
                        <small id="purchase-ctns__coin-label">USDT</small>
                    </div>
                    <div id="purchase-ctns__coin-number">
                        <span id="jackpot-amount">1</span>
                    </div>
                </div>
            </div>
            <div class="purchase-ctns__date">
                <div class="purchase-ctns__date-item">
                    <i class="purchase-ctns__icon fa-regular fa-calendar-days"></i>
                    <span id="text-date"></span>
                </div>
                <div class="purchase-ctns__date-item">
                    <i class="purchase-ctns__icon fa-solid fa-clock"></i>
                    <span id="text-time"></span>
                </div>
            </div>
        </div>
        <div class="purchase-ctns__body bg-light p-1">
            @isset($record)
            <div class="purchase-ctns__game-room-info">
                <div id="game-room-info__details">
                    <i class="fa-solid fa-circle-info"></i>
                    Info. del Juego
                </div>
                <div class="game-room-info__players">
                    <span id="game-room-info__players-count"></span>
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
            @endisset
            <div class="purchase-ctns__body-form">
                <div id="purchase-ctns__body-form-section-left">
                    <div id="purchase-ctns__form-title">Compra tus cartones</div>
                    <div class="purchase-ctns__body-form-num-ctns">
                        <span>Cantidad</span>
                        <div class="quantity">
                            <div class="quant quantity-down">
                                <i class="fa-solid fa-minus"></i>
                            </div>
                            <input id="num-ctns" type="number" step="1" max="25" min="1"
                                value="1">
                            <div class="quant quantity-up">
                                <i class="fa-solid fa-plus"></i>
                            </div>
                        </div>
                    </div>
                    <span class="form-text-total">Total:</span>
                    <small>USDT</small><span id="total-amount"></span>
                </div>
                <div id="purchase-ctns__body-form-section-right"></div>
            </div>
        </div>
        <div class="card-footer bg-light text-center">
            <button type="button" id="btn-container-purchase-close"
                class="mr-1 btn btn-secondary">Cancelar</button>
            <button type="button" id="btn-purchase-ctns" class="btn btn-primary">Comprar</button>
        </div>
    </div>
</div>