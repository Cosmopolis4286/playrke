<div class="row">
    <input type="hidden" name="creator" value="{{ json_encode($creator) }}">
    <div id="container-allowed-currencies" class="form-group col-sm-6">
        <label for="allowed-currencies">Monedas Permitidas*</label>
        <select multiple id="allowed-currencies" name="allowed_currencies[]" placeholder="  Seleccionar Monedas" data-allow-clear="1"
            class="select2 form-control">
        </select>
    </div>
    <div class="form-group col-sm-6 col-md-4 col-lg-3">
        <label for="ctn-price">Precio CTN*</label>
        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text">$</span>
            </div>
            <input type="number" id="ctn-price" name="card_price" class="form-control" placeholder="Precio Cartón"
                min="0.35" max="50" value="1" step="0.05" required title="Precio Base">
            <div class="input-group-append">
                <button id="btn-ctn-price-config" class="btn btn-outline-secondary" type="button" data-active="0"
                    data-toggle="popover" data-trigger="hover" data-content="Ajustar precio por monedas">
                    <i class="fa-solid fa-sliders"></i>
                </button>
            </div>
        </div>
    </div>
    <div class="form-group col-sm-6 col-md-4 col-lg-3">
        <label for="room-commn">%Sala*</label>
        <input type="number" id="room-commn" name="room_commission" class="form-control prize-pool-pct" value="20"
            step="0.05" min="10" max="50" required>
    </div>
    <div id="container-ctn-price-config" class="form-group col-12">
        <div class="border rounded p-3" style="max-height: 155px; overflow-y: auto;">
            <div class="d-flex">
                <h6 class="mb-3 mr-1">Ajustar precio del cartón</h6>
                <div id="spinner-ctn-price-config" class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="sr-only">Cargando...</span>
                </div>
            </div>
            <div id="content-ctn-price-config" class="row"></div>
        </div>
    </div>
</div>
