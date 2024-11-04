<div class="row">
    <div class="form-group col-sm-6 col-md-4">
        <label for="date">Fecha*</label>
        <input type="date" id="date" name="date" class="form-control form-info" required
            min="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}">
    </div>
    <div class="form-group col-sm-6 col-md-4">
        <label for="time">Hora*</label>
        <input type="time" id="time" name="time" class="form-control form-info" required>
    </div>
    <div id="container-room-type" class="form-group col-sm-6 col-md-4">
        <label for="room-type">Tipo de Sala*</label>
        <select id="room-type" name="room_type" class="form-control form-info"
            aria-label="Seleccionar tipo de sala"></select>
    </div>
    <div class="form-group col-sm-6 col-md-4">
        <label for="game-mode">Modo de Juego*</label>
        <select id="game-mode" name="game_mode" class="form-control form-info"></select>
    </div>
    <input type="hidden" name="user" value="{{ json_encode($creator) }}">
    <div class="form-group col-sm-6 col-md-4">
        <label for="ctn-price">Precio CTN*</label>
        <input type="number" id="ctn-price" name="ctn_price" class="form-control form-info"
            placeholder="Ingresar Precio" min="1" value="1" step="0.05" required>
    </div>
    {{-- <div class="form-group col-sm-6 col-md-4">
        <label for="small-prizes">%Cantos</label>
        <input type="number" value="0" id="small-prizes" name="small_prizes" class="form-control form-info"
            placeholder="Ingrese monto a pagar" required>
    </div> --}}
    {{-- @isset($customer) --}}
    <div class="form-group col-sm-6 col-md-4">
        <label for="serials-commission">%Com. X Seriales</label>
        <input type="number" value="1" min="1" max="6" step="0.01" id="serials-commission"
            name="serials_commission" class="form-control form-info" placeholder="Ingrese valor">
    </div>
    {{-- @endisset --}}
    <div class="form-group col-sm-6 col-md-4">
        <label for="val-serials">%Seriales</label>
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
            placeholder="%Com" required value="15" step="0.05" min="15" max="50">
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
</div>
