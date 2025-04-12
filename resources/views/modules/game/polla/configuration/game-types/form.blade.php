<div class="row">
    @if (!isset($automaticRoom))
        <div class="form-group col-sm-6 col-md-4">
            <label for="date">Fecha*</label>
            <input type="date" id="date" name="date" class="form-control form-info" required
                min="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}">
        </div>
    @endif
    <div class="form-group col-md-8">
        <label for="game-type">Tipo de Juego*</label>
        <select id="game-type" name="game_type" class="form-control form-info" required></select>
    </div>   
    <div class="form-group col-sm-6 col-md-4">
        <label for="game-mode">Modo de Juego*</label>
        <select id="game-mode" name="game_mode" class="form-control form-info"></select>
    </div>
    <div class="form-group col-sm-6 col-md-4">
        <label for="jackpot-type">Tipo Bote*</label>
        <select id="jackpot-type" name="jackpot_type" class="form-control form-info" required></select>
    </div>
    <div id="cont-fixed-jackpot-value" class="form-group col-sm-6 col-md-4 hide-elem">
        <label for="fixed-jackpot-value">Valor Fijo/Extra*</label>
        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text">$</span>
            </div>
            <input type="number" id="fixed-jackpot-value" name="fixed_jackpot_value" class="form-control form-info"
                step="0.05" min="20" max="10000">
        </div>
    </div>
    <div class="form-group col-sm-6 col-md-4">
        <label for="room-commn">%Sala*</label>
        <input type="number" id="room-commn" name="room_commn" class="form-control form-info" value="10"
            step="0.05" min="5" max="50" required>
    </div>
    <div class="form-group col-sm-6 col-md-4">
        <label for="ticket-price">Precio Ticket*</label>
        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text">$</span>
            </div>
            <input type="number" id="ticket-price" name="ticket_price" class="form-control form-info"
                placeholder="Precio Cartón" min="0.35" max="50" value="1" step="0.05" required
                title="Precio Base">
            <div class="input-group-append">
                <button id="btn-ticket-price-config" class="btn btn-outline-secondary" type="button" data-active="0"
                    data-toggle="popover" data-trigger="hover" data-content="Ajustar precio por monedas">
                    <i class="fa-solid fa-sliders"></i>
                </button>
            </div>
        </div>
    </div>
    <div id="container-ticket-price-config" class="form-group col-12 hide-elem">
        <div class="container"
            style="max-height: 155px; overflow-y: auto; border: 1px solid #ccc; padding: 8px 15px; border-radius: 4px;">
            <label for="container-price-config" class="form-label">Ajustar Precio del Ticket</label>
            <div id="container-spinner" class="mt-1">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only"></span>
                </div>
            </div>
            <div id="container-price-config" class="row">
            </div>
        </div>
    </div>
</div>
