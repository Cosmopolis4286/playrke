<div class="row">
    <div id="container-code" class="form-group col-sm-6 col-md-4">
        <label for="code">Codigo</label>
        <input id="code" class="form-control" name="code" readonly>
    </div>
    <div class="form-group col-sm-6 col-md-4">
        <label for="kit">Es un kit*</label>
        <select id="kit" class="form-control" required></select>
    </div>
    <div class="form-group col-sm-6 col-md-4">
        <label for="num-items">#Itens*</label>
        <input type="number" step="1" min="1" max="10" id="num-items" class="form-control"
            name="num_items" value="1" required>
    </div>
    <div class="form-group col-sm-6 col-md-4">
        <label for="price">Precio*</label>
        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text">$</span>
            </div>
            <input type="number" step="0.05" min="1" id="price" class="form-control" name="price"
                required>
            <input type="hidden" id="creator" name="creator" value="{{ json_encode($creator) }}">
        </div>
    </div>
    <div class="form-group col-12 col-md">
        <label for="description">Descripcion*</label>
        <textarea class="form-control" name="description" id="description" cols="15" rows="3"></textarea>
    </div>
</div>
