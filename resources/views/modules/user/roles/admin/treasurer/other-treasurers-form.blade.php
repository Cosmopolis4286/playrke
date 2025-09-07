<div class="row">
    <div class="form-group col-sm-6">
        <label for="username">Usuario</label>
        <input type="text" id="username" class="form-control" disabled>
    </div>
    <div class="form-group col-sm-6">
        <label for="country">País</label>
        <input id="country" class="form-control" disabled>
    </div>
    <div class="form-group col-sm-6">
        <label for="other-coins">Monedas Permitidas:</label>
        <select multiple id="other-coins" name="other_currencies[]" placeholder="Seleccionar Monedas" data-allow-clear="1"
            class="select2 form-control">
        </select>
    </div>
    <div class="form-group col-sm-6">
        <label for="allowed-games">Juegos Permitidos:</label>
        <select multiple id="allowed-games" name="allowed_games[]" placeholder="Seleccionar Juegos" data-allow-clear="1"
            class="select2 form-control">
        </select>
    </div>
</div>
