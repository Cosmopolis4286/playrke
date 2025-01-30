<div class="row">
    @if (!isset($automaticRoom))
        <div class="form-group col-sm-6 col-md-4">
            <label for="date">Fecha*</label>
            <input type="date" id="date" name="date" class="form-control form-info" required
                min="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}">
        </div>
    @endif
    <div class="form-group col-sm-6 col-md-4">
        <label for="time">Hora*</label>
        <input type="time" id="time" name="time" class="form-control form-info" required>
    </div>
    @if ($creator['type'] !== 'customer' || !empty($roomType))
        <div class="form-group col-sm-6 col-md-4">
            <label for="room-type">Tipo de Sala*</label>
            <select id="room-type" name="room_type" class="form-control form-info"
                aria-label="Seleccionar tipo de sala"></select>
        </div>
    @endif
    <div class="form-group col-sm-6 col-md-4">
        <label for="game-mode">Modo de Juego*</label>
        <select id="game-mode" name="game_mode" class="form-control form-info"></select>
    </div>
    <div class="form-group col-sm-6 col-md-4">
        <label for="jackpot-type">Tipo Bote*</label>
        <select id="jackpot-type" name="jackpot_type" class="form-control form-info" required></select>
    </div>
    <div id="cont-fixed-jackpot-value" class="form-group col-sm-6 col-md-4 hide-elem">
        <label for="fixed-jackpot-value">Valor Fijo*</label>
        <input type="number" id="fixed-jackpot-value" name="fixed_jackpot_value" class="form-control form-info"
            placeholder="Ingresar Monto" step="0.05" min="50" max="5000">
    </div>
    <input type="hidden" name="creator" value="{{ json_encode($creator) }}">
    <div class="form-group col-sm-6 col-md-4">
        <label for="ctn-price">Precio CTN*</label>
        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text">$</span>
            </div>
            <input type="number" id="ctn-price" name="ctn_price" class="form-control form-info"
                placeholder="Precio Cartón" min="0.35" value="1" step="0.05" required title="Precio Base">
            <div class="input-group-append">
                <button id="btn-ctn-price-config" class="btn btn-outline-secondary" type="button"
                    data-active="0" data-toggle="popover" data-trigger="hover"
                    data-content="Ajustar precio por monedas">
                    <i class="fa-solid fa-sliders"></i>
                </button>
            </div>
        </div>
    </div>
    <div id="container-ctn-price-config" class="form-group col-12 hide-elem">
        <div class="container"
            style="max-height: 155px; overflow-y: auto; border: 1px solid #ccc; padding: 8px 15px; border-radius: 4px;">
            <label for="container-price-config" class="form-label">Ajustar Precio del Cartón</label>
            <div id="container-spinner" class="mt-1">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only"></span>
                </div>
            </div>
            <div id="container-price-config" class="row">
            </div>
        </div>
    </div>
    {{-- <div class="form-group col-sm-6 col-md-4">
        <label for="small-prizes">%Cantos</label>
        <input type="number" value="0" id="small-prizes" name="small_prizes" class="form-control form-info"
            placeholder="Ingrese monto a pagar" required>
    </div> --}}
    <div class="form-group col-sm-6 col-md-4">
        <label for="serials-commission">%Com. Seriales</label>
        <input type="number" value="0" min="0" max="5" step="0.01" id="serials-commission"
            name="serials_commission" class="form-control form-info" placeholder="Ingrese valor">
    </div>
    <div class="form-group col-sm-6 col-md-4">
        <label for="val-serials">%Bote Seriales</label>
        <input type="number" value="2" min="2" max="15" step="0.05" id="val-serials"
            name="val_serials" class="form-control form-info" placeholder="Ingrese valor" required>
    </div>
    <div class="form-group col-sm-6 col-md-4">
        <label for="num-serials">#Max. Seriales</label>
        <input type="number" step="1" value="5" min="5" max="15" id="num-serials"
            name="num_serials" class="form-control form-info" placeholder="Ingrese la cantidad" required>
    </div>
    <div class="form-group col-sm-6 col-md-4">
        <label for="commerce-commission">%Comercio*</label>
        <input type="number" id="commerce-commission" name="room_commission" class="form-control form-info"
            value="10" step="0.05" min="10" max="50" required>
    </div>
</div>
