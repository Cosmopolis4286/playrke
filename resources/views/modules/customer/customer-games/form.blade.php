<div class="row">
    <!-- Campo oculto para game_id -->
    <input type="hidden" name="game_id" value="BINGO">

    <!-- Campo para ID del Juego -->
    <div class="form-group col-12">
        <label for="customer-game-id" class="form-label">ID del Juego <span class="text-danger">*</span></label>
        <input type="text" id="customer-game-id" name="customer_game_id" class="form-control form-info"
            value="{{ old('customer_game_id', $customerGameID->customer_game_id ?? '') }}" required
            aria-required="true">
    </div>

    <!-- Selector de Status -->
    <div class="form-group col-12">
        <label for="status" class="form-label">Status</label>
        <select id="status" name="status" class="form-control form-info" aria-label="Seleccionar Status">
            <option value="1" @selected(old('room_type', $customerGameID->status ?? null) == 1)>Activo</option>
            <option value="0" @selected(old('room_type', $customerGameID->status ?? null) == 0)>Inactivo</option>
        </select>
    </div>

    <!-- Campo oculto para customer -->
    <input type="hidden" name="customer" value="{{ $creatorID }}">
</div>
