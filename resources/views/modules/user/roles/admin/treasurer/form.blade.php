<div class="row">
    <div id="cont-select-user-role" class="form-group col-6">
        <label for="select-user-role">Promover Usuario</label>
        <div class="input-group">
            <div class="input-group-prepend">
                <div class="input-group-text">
                    <input type="checkbox" id="checkbox-promote-user" aria-label="Activar promoción de usuario">
                </div>
            </div>
            <select class="form-control" id="select-user-role" name="user_role" disabled></select>
        </div>
    </div>
    <div id="cont-select-user-to-promote" class="form-group col-sm-6">
        <label id="label-user-promote" for="user-to-promote"></label>
        <select class="form-control" id="select-user-to-promote" name="user_to_promote" disabled></select>
    </div>
    <div class="form-group col-sm-12 col-md-6 new-treasurer">
        <label for="name">Nombre*</label>
        <input type="text" id="name" name="name" class="form-control" required value="{{ old('moduleId') }}">
    </div>
    <div class="form-group col-sm-6 new-treasurer">
        <label for="email">Email*</label>
        <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror"
            required value="{{ old('email') }}">
        @error('email')
            <span class="invalid-feedback">{{ $message }}</span>
        @enderror
    </div>
    <div class="form-group col-sm-6 new-treasurer">
        <label for="username">Username*</label>
        <input type="text" id="username" name="username" minlength="5" maxlength="20"
            class="form-control @error('username') is-invalid @enderror" required value="{{ old('username') }}">
        @error('username')
            <span class="invalid-feedback">{{ $message }}</span>
        @enderror
    </div>
    <div class="form-group col-sm-6 new-treasurer">
        <label for="country">País*</label>
        <select id="country" name="country" class="form-control" required></select>
    </div>
    <div class="form-group col-sm-6 new-treasurer">
        <label for="time-zone">Zona Horaria*</label>
        <select id="time-zone" name="time_zone" class="form-control" required></select>
    </div>
    <div class="form-group col-sm-6 new-treasurer">
        <label for="phone">Teléfono*</label>
        <input type="text" id="phone" name="phone" class="form-control @error('phone') is-invalid @enderror"
            required value="{{ old('username') }}" placeholder="Ingresa el número" required value="{{ old('phone') }}">
        @error('phone')
            <span class="invalid-feedback">
                <small>{{ $message }}</small>
            </span>
        @enderror
    </div>
    <div class="form-group col-sm-6 new-treasurer">
        <label for="currency">Moneda*</label>
        <input type="text" id="currency-text" class="form-control" disabled />
        <input type="hidden" id="currency" name="currency" value="{{ old('currency') }}" />
    </div>
    <div id="cont-other-coins" class="form-group col-sm-6 hide-elem">
        <label id="label-other-coins" for="other-coins"></label>
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
