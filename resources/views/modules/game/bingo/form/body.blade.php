<div class="row">
    @php
        $now = \Carbon\Carbon::now($timeZone->name);
    @endphp
    <div class="form-group col-sm-6 col-md-4 col-lg-3">
        <label for="time-zone">Zona Horaria</label>
        <input type="text" class="form-control" value="{{ $timeZone->utc }}" disabled>
    </div>
    @if (!isset($automaticRoom))
        <div class="form-group col-sm-6 col-md-4 col-lg-3">
            <label for="date">Fecha*</label>
            <input type="date" id="date" name="date" class="form-control" required
                min="{{ $now->format('Y-m-d') }}" value="{{ $now->format('Y-m-d') }}">
        </div>
    @endif
    <div class="form-group col-sm-6 col-md-4 col-lg-3">
        <label for="time">Hora*</label>
        <input type="time" id="time" name="time" class="form-control" required
            value="{{ $now->format('H:i') }}">
    </div>
    @if (isset($automaticRoom) && $creator['type'] !== 'customer')
        <div class="form-group col-sm-6 col-md-4 col-lg-3">
            <label for="room-type">Tipo de Sala*</label>
            <select id="room-type" name="room_type" class="form-control" aria-label="Seleccionar tipo de sala"></select>
        </div>
    @else
        <input type="hidden" id="room-type" name="room_type" class="form-control" required>
    @endif
    <div class="form-group col-sm-6 col-md-4 col-lg-3">
        <label for="game-mode">Modo de Juego*</label>
        <select id="game-mode" name="game_mode" class="form-control"></select>
    </div>
    {{-- <div class="form-group col-sm-6 col-md-4 col-lg-3">
        <label for="in-live">En Vivo*</label>
        <select id="in-live" name="in_live" class="form-control not-empty">
            <option value="Y">SI</option>
            <option value="N" selected>NO</option>
        </select>
    </div> --}}
    <div class="form-group col-sm-6 col-md-4 col-lg-3">
        <label for="prize-pool-type">Tipo Bote*</label>
        <select id="prize-pool-type" name="prize_pool_type" class="form-control" required></select>
    </div>
    <div id="container-amount-added-prize-pool" class="form-group col-sm-6 col-md-4 col-lg-3">
        <label for="amount-added-prize-pool">Valor Fijo/Extra*</label>
        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text">$</span>
            </div>
            <input type="number" id="amount-added-prize-pool" name="amount_added_prize_pool" class="form-control"
                step="0.05" min="20" max="10000">
        </div>
    </div>
    <input type="hidden" name="creator" value="{{ json_encode($creator) }}">
    <div class="form-group col-sm-6 col-md-4 col-lg-3">
        <label for="room-commn">%Sala*</label>
        <input type="number" id="room-commn" name="room_commn" class="form-control prize-pool-pct" value="20"
            step="0.05" min="10" max="50" required>
    </div>
    <div class="form-group col-sm-6 col-md-4 col-lg-3">
        <label for="jackpot-pct">%Jackpot</label>
        <input type="number" id="jackpot-pct" name="jackpot_pct" class="form-control prize-pool-pct" value=""
            step="0.5" min="1" max="15">
    </div>
    <div class="form-group col-sm-6 col-md-4 col-lg-3">
        <label for="time-chip">Tiempo Ficha*</label>
        <div class="input-group">
            <input type="number" step="1" value="5" min="3" max="10" id="time-chip"
                name="time_chip" class="form-control" required>
            <div class="input-group-append">
                <span class="input-group-text">SEG</span>
            </div>
        </div>
    </div>
    <div class="form-group col-sm-6 col-md-4 col-lg-3">
        <label for="rounds">Rondas*</label>
        <div class="input-group">
            <input type="number" step="1" value="1" min="1" max="15" id="rounds"
                name="rounds" class="form-control" required>
            <div class="content-rounds-pct input-group-append">
                <button id="btn-rounds-config" class="btn btn-outline-secondary" type="button" data-active="0"
                    data-toggle="popover" data-trigger="hover" data-content="Ajustar precio por monedas">
                    <i class="fa-solid fa-sliders"></i>
                </button>
            </div>
        </div>
    </div>
    <div id="container-rounds-config" class="form-group col-12">
        <div class="border rounded p-3" style="max-height: 155px; overflow-y: auto;">
            <div class="d-flex">
                <h6 class="mb-3 mr-1">Ajustar valores de las rondas</h6>
                <div id="spinner-rounds-config" class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="sr-only">Cargando...</span>
                </div>
            </div>
            <div id="content-rounds-config" class="row"></div>
        </div>
    </div>
    <div class="content-rounds-pct form-group col-sm-6 col-md-4 col-lg-3">
        <label for="rounds-pct">%Rondas*</label>
        <input type="number" id="rounds-pct" name="rounds_pct" class="form-control prize-pool-pct" value=""
            step="1" min="3" max="30">
    </div>
    <div class="form-group col-sm-6 col-md-4 col-lg-3">
        <label for="serials-pct">%Bote Seriales</label>
        <input type="number" value="5" min="2" max="35" step="0.25" id="serials-pct"
            name="serials_pct" class="form-control prize-pool-pct" placeholder="Ingrese valor" required>
    </div>
    <div class="form-group col-sm-6 col-md-4 col-lg-3">
        <label for="num-serials">#Max. Seriales</label>
        <input type="number" step="1" value="5" min="3" max="30" id="num-serials"
            name="num_serials" class="form-control" placeholder="Ingrese la cantidad" required>
    </div>
    <div class="form-group col-sm-6 col-md-4 col-lg-3">
        <label for="ctn-price">Precio CTN*</label>
        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text">$</span>
            </div>
            <input type="number" id="ctn-price" name="ctn_price" class="form-control" placeholder="Precio Cartón"
                min="0.35" max="50" value="1" step="0.05" required title="Precio Base">
            <div class="input-group-append">
                <button id="btn-ctn-price-config" class="btn btn-outline-secondary" type="button" data-active="0"
                    data-toggle="popover" data-trigger="hover" data-content="Ajustar precio por monedas">
                    <i class="fa-solid fa-sliders"></i>
                </button>
            </div>
        </div>
    </div>
    <div id="container-ctn-price-config" class="form-group col-12">
        <div class="border rounded p-3" style="max-height: 155px; overflow-y: auto;">
            <div class="d-flex">
                <h6 class="mb-3 mr-1">Ajustar precio del cartón</h6>
                <div id="spinner-ctn-price-config" class="spinner-border spinner-border-sm text-primary"
                    role="status">
                    <span class="sr-only">Cargando...</span>
                </div>
            </div>
            <div id="content-ctn-price-config" class="row"></div>
        </div>
    </div>
    {{-- <div id="container-plays-pct" class="form-group col-sm-6 col-md-4">
        <label for="plays-pct">%Bote Cantos</label>
        <input type="number" value="" min="2" max="35" step="0.25" id="plays-pct"
            name="plays-pct" class="form-control prize-pool-pct" placeholder="Ingrese valor" required>
    </div> --}}
</div>
