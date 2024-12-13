<style>
    /* Modal Compra */
#container-purchase-ctns {
    display: none;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 99999;
    min-width: 310px;
    max-width: 460px;
    min-height: 80px;
}

.purchase-ctns__header {
    position: relative;
    display: flex;
    width: 100%;
    background-color: saddlebrown
}

.purchase-ctns__footer {
    border-bottom-left-radius: 7px;
    border-bottom-right-radius: 7px;
}

.purchase-ctns__body {
    position: relative;
    height: auto;
    width: 100%;
}


.purchase-ctns__body-form {
    position: relative;
    display: flex;
    padding: 1.5%;
}

#purchase-ctns__body-form-section-left {
    position: relative;
    width: 66%;
}

#purchase-ctns__body-form-section-right {
    position: relative;
    width: 34%;
    display: flex;
    justify-content: right;
}

.purchase-ctns__jackpot {
    position: relative;
    width: 66%;
    height: 100%;
    display: flex;
    padding: 1.15%;
    border-top-left-radius: 7px;
    background: linear-gradient(to bottom, #084aff, #0e43b4);
}

.purchase-ctns__jackpot-img {
    width: 25%;
    padding: 3px;
    height: auto;
    display: flex;
    justify-content: center;
    align-items: center;
}

.purchase-ctns__img {
    max-width: 100%;
    height: auto;
}

.purchase-ctns__coin {
    position: relative;
    display: flex;
    justify-content: center;
    flex-direction: column;
    width: 78%;
    height: 100%;
    margin-left: 2.5px;
    line-height: 135%;
}

#purchase-ctns__coin-value {
    display: flex;
    align-items: center;
}

#purchase-ctns__coin-text {
    display: flex;
    align-items: center;
    font-size: 15.5px;
    color: rgb(255, 255, 255);
    font-weight: 500;
}

#purchase-ctns__coin-number {
    display: flex;
    align-items: center;
    font-size: 24px;
}

#purchase-ctns__coin-label {
    display: flex;
    align-items: center;
    color: rgb(219, 219, 219);
    font-weight: 600;
    font-size: 15px;
    margin-right: 1.5%;
}

#jackpot-amount {
    color: rgb(255, 230, 0);
}

.purchase-ctns__date {
    position: relative;
    width: 34%;
    border-top-right-radius: 7px;
    height: 100%;
    padding: 1.5%;
    display: flex;
    align-items: center;
    flex-direction: column;
    color: rgb(224, 224, 224);
    font-weight: 400;
    background-color: #013bb9;
}

.purchase-ctns__date-item {
    height: 50%;
    width: 100%;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.purchase-ctns__icon {
    color: #008afc;
    margin-right: 2.75px;
}

.purchase-ctns__icon_rlg {
    color: #008afc;
    font-size: 12.2px;
    margin-right: 2.75px;
}

#text-date {
    font-size: 17px;
}

#text-time {
    font-size: 17px;
}

#purchase-ctns__form {
    position: relative;
    display: flex;
    flex-direction: column;
    width: 100%;
}

#purchase-ctns__form-title {
    text-transform: uppercase;
    width: 100%;
    font-weight: 600;
}

.purchase-ctns__game-room-info {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    padding-left: 1.5%;
    padding-right: 1.5%;
    padding-bottom: 0.25%;
    line-height: 20px;
    border-bottom: 1px solid rgba(0, 75, 187, 0.521);
    border-left: 1px solid rgba(0, 75, 187, 0.521);
    border-right: 1px solid rgba(0, 75, 187, 0.521);
}

.purchase-ctns__body-form-num-ctns {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: left;
    gap: 5.5%;
}

.quantity {
    display: flex;
    justify-content: center;
    align-items: center;
}

.quant {
    background-color: #c4e7ff;
    display: flex;
    justify-content: center;
    align-items: center;
    cursor: pointer;
    border-radius: 50%;
    height: 23px;
    width: 23px;
    font-weight: 500;
}

.quantity-down {
    padding-top: 2.5px;
}

.quantity-up {
    padding-top: 1.5px;
    padding-left: 1px;
}

.quantity input {
    width: 45px;
    text-align: center;
    font-size: 24.5px;
    padding-top: 2px;
    background-color: transparent;
    border: transparent;
}

.quantity input:focus {
    outline: 0;
}

input[type=number]::-webkit-inner-spin-button,
input[type=number]::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

input[type=number] {
    -moz-appearance: textfield;
    -webkit-appearance: none;
    appearance: none;
}

</style>

<div id="container-purchase-ctns">
    <div class="purchase-ctns__header">
        <div class="purchase-ctns__jackpot">
            <div class="purchase-ctns__jackpot-img">
                <img class="purchase-ctns__img" src="{{ asset('img/cofre.png') }}" alt="">
            </div>
            <div class="purchase-ctns__coin">
                <div id="purchase-ctns__coin-text"></div>
                <div id="purchase-ctns__coin-number">
                    <span id="jackpot-amount"></span>
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
    {{-- <div class="purchase-ctns__body bg-light ">
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
            <div id="purchase-ctns__body-form-section-right"></div>
        </div>
        <div class="purchase-ctns__footer card-footer bg-light text-center">
            <button type="button" id="btn-container-purchase-close" class="mr-1 btn btn-secondary">Cancelar</button>
            <button type="button" id="btn-purchase-ctns" class="btn btn-primary">Comprar
                <span id="loader-btn-purchase-ctns" class="spinner-border spinner-border-sm" role="status"
                    aria-hidden="true" style="display: none"></span>
            </button>
        </div>
    </div> --}}
</div>
