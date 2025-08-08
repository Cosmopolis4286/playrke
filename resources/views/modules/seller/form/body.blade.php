<div class="row">
    <div class="form-group col-sm-12 col-md-6">
        <label for="name">Nombre*</label>
        <input type="text" id="name" name="name" class="form-control" required value="{{ old('name') }}">
    </div>
    <div class="form-group col-sm-6">
        <label for="email">Email*</label>
        <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror"
            required value="{{ old('email') }}">
        @error('email')
            <span class="invalid-feedback">{{ $message }}</span>
        @enderror
    </div>
    <div class="form-group col-sm-5">
        <label for="username">Username*</label>
        <input type="text" id="username" name="username" minlength="5" maxlength="20"
            class="form-control @error('username') is-invalid @enderror" required value="{{ old('username') }}">
        @error('username')
            <span class="invalid-feedback">{{ $message }}</span>
        @enderror
    </div>
    <div class="form-group col-sm-6 col-md-3">
        <label for="commission">%Comisión*</label>
        <div class="input-group">
            <input type="number" id="commission" name="commission" class="form-control" placeholder="%Com" required
                value="{{ old('commission', '3.00') }}" step="0.25" min="3.00" max="35">
        </div>
    </div>
    <div class="form-group col-sm-4">
        <label for="country">País*</label>
        <select id="country" name="country" class="form-control" required></select>
    </div>
    <div class="form-group col-sm-6">
        <label for="time-zone">Zona Horaria*</label>
        <select id="time-zone" name="time_zone" class="form-control" required></select>
    </div>
    <div class="form-group col-sm-6">
        <label for="phone">Teléfono*</label>
        <input type="text" id="phone" name="phone" class="form-control @error('phone') is-invalid @enderror"
            required value="{{ old('username') }}" placeholder="Ingresa el número" required value="{{ old('phone') }}">
        @error('phone')
            <span class="invalid-feedback">
                <small>{{ $message }}</small>
            </span>
        @enderror
    </div>
    <div class="form-group col-sm-6">
        <label for="coin">Moneda*</label>
        <input type="text" id="coin-text" class="form-control" disabled>
        <input type="hidden" id="coin" name="coin" value="{{ old('coin') }}">
    </div>
    <div id="cont-other-coins" class="form-group col-sm hide-elem">
        <label for="other-coins">Otras Monedas:</label>
        <select multiple id="other-coins" name="other_coins[]" placeholder="Seleccionar Monedas" data-allow-clear="1"
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
