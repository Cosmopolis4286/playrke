<div id="container-purchase-ctns">
    <div class="purchase-ctns__header">
        <div class="purchase-ctns__prize_pool">
            <div class="purchase-ctns__prize_pool-img">
                <img class="purchase-ctns__img" src="{{ asset('img/cofre.png') }}" alt="">
            </div>
            <div class="purchase-ctns__coin">
                <div id="purchase-ctns__coin-text"></div>
                <div id="purchase-ctns__coin-number">
                    <span id="prize-pool-amount"></span>
                    <small id="purchase-ctns__coin-label" class="ml-1 mt-1 purchase-ctns__coin-label"></small>
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
    <div class="purchase-ctns__body">
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
                        <div id="quantity-down" class="quant quantity-down">
                            <i class="fa-solid fa-minus"></i>
                        </div>
                        <input id="num-ctns" type="number" step="1" max="30" min="1" value="1"
                            maxlength="2">
                        <div id="quantity-up" class="quant quantity-up">
                            <i class="fa-solid fa-plus"></i>
                        </div>
                    </div>
                </div>
                <span class="form-text-total">Total:</span>
                <span id="total-amount"></span>
                <small class="purchase-ctns__coin-label"></small>
            </div>
            <div id="purchase-ctns__body-form-section-right">
                <div>
                    <div class="text-muted" style="cursor: pointer" data-toggle="modal"
                        data-target="#modal-bingo-rules">
                        <i class="fa-solid fa-circle-info"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="purchase-ctns__footer card-footer">
            <button type="button" id="btn-container-purchase-close" class="mr-1 btn btn-secondary">Cancelar</button>
            <button type="button" id="btn-purchase-ctns" class="btn btn-primary">Comprar
                <span id="loader-btn-purchase-ctns" class="spinner-border spinner-border-sm" role="status"
                    aria-hidden="true" style="display: none"></span>
            </button>
        </div>
    </div>
</div>
