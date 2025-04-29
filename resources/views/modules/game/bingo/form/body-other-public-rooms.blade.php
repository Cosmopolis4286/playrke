<div class="row">
    <input type="hidden" name="creator" value="{{ json_encode($creator) }}">
    <div class="form-group col-sm-6 col-md-4">
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
</div>
